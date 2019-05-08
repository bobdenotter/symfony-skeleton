<?php

declare(strict_types=1);

namespace Bolt\Form;

use Bolt\Configuration\Content\FieldType;
use Bolt\Entity\Content;
use Bolt\Entity\Field;
use Bolt\Enum\Statuses;
use Bolt\Form\Field\FieldTypeTransformer;
use Bolt\Form\Field\FieldValueModelTransformer;
use Bolt\Utils\Str;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints;

class ContentFormType extends AbstractType
{
    public const OHTER_GROUP_SLUG = '__other';

    /**
     * @var FieldValueModelTransformer
     */
    private $fieldValueModelTransformer;

    public function __construct(FieldValueModelTransformer $fieldValueModelTransformer)
    {
        $this->fieldValueModelTransformer = $fieldValueModelTransformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('_edit_locale', ChoiceType::class, [
                'mapped' => false,
                'label' => 'label.locale',
                'choices' => [
                    'English (en)' => 'en',
                    'Nederlands (dutch, nl)' => 'nl',
                    'Español (Spanish, es)' => 'es',
                    'français (French, fr)' => 'fr',
                    'Deutsch (German, de)' => 'de',
                    'Polski (Polish, pl)' => 'pl',
                    'Brasilian Portuguese (Brasilian Portuguese, pt_BR)' => 'pt_BR',
                    'Italiano (Italian, it)' => 'it',
                ],
            ])
            ->add('status', ChoiceType::class, [
                'choices' => Statuses::all(),
                'choice_label' => function (string $option) {
                    return ucwords($option);
                },
            ])
            ->add('modifiedAt', DateTimeType::class, [
                'required' => false,
            ])
            ->add('publishedAt', DateTimeType::class, [
                'required' => false,
            ])
            ->add('depublishedAt', DateTimeType::class, [
                'required' => false,
            ])
            ->add('fields', FormType::class) // fields are added in listener
            ->add('taxonomies', CollectionType::class, [
                'allow_add' => true,
                'allow_delete' => true,
                'entry_type' => TaxonomyFormType::class,
                'entry_options' => ['label' => false],
                'by_reference' => false,
            ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($builder): void {
            $content = $event->getData();
            $this->injectFields($event->getForm(), $builder, $content);
        });
    }

    public function injectFields(FormInterface $form, FormBuilderInterface $builder, Content $content): void
    {
        $contentFields = $content->getFields();
        foreach ($content->getDefinition()->get('fields') as $fieldName => $fieldDefinition) {
            if ($contentFields->containsKey($fieldName) === false) {
                $contentFields->set($fieldName, Field::factory($fieldDefinition, $fieldName));
            }
            /** @var FieldType $fieldType */
            $fieldType = $contentFields->get($fieldName)->getDefinition();
            $fieldFormType = FieldTypeTransformer::fieldTypeToFormClass($fieldType);
            $required = $fieldType->get('required') ?? true;
            $requirements = $this->resolveRequirements($fieldType);

            $form->get('fields')->add(
                $builder
                    ->create($fieldName, $fieldFormType, [
                        'required' => $required,
                        'constraints' => $requirements,
                        'compound' => true,
                        'property_path' => "[{$fieldName}].value",
                        'auto_initialize' => false,
                        'attr' => [
                            'field_definition' => $fieldType,
                        ],
                    ])
                    ->addModelTransformer($this->fieldValueModelTransformer->forField($fieldType))
                    ->getForm()
            );
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Content::class,
            'empty_data' => function (FormInterface $form): Content {
                return new Content(
                    $form->get('content_type')->getData(),
                    $form->get('author')->getData()
                );
            },
        ]);
    }

    /**
     * @return Constraint[]
     */
    private function resolveRequirements(FieldType $definition): array
    {
        $requirements = [];

        if ($definition->get('required') ?? true === true) {
            $requirements[] = new Constraints\NotBlank();
        }
        if ($definition->get('pattern')) {
            $requirements[] = new Constraints\Regex(['pattern' => $definition->get('pattern')]);
        }

        if ($definition->get('minLength') || $definition->get('maxLength')) {
            $lengthOptions = [];
            if ($definition->get('minLength')) {
                $lengthOptions['min'] = $definition->get('minLength');
            }
            if ($definition->get('maxLength')) {
                $lengthOptions['max'] = $definition->get('maxLength');
            }
            $requirements[] = new Constraints\Length($lengthOptions);
        }

        if ($definition->get('min') || $definition->get('max')) {
            $countOptions = [];
            if ($definition->get('min')) {
                $countOptions['min'] = $definition->get('min');
            }
            if ($definition->get('max')) {
                $countOptions['max'] = $definition->get('max');
            }
            $requirements[] = new Constraints\Count($countOptions);
        }

        if ($definition->get('customValidation')) {
            foreach ($definition->get('customValidation') as $validationConstraint => $options) {
                if (class_exists($validationConstraint) === true) {
                    $validationConstraintClass = $validationConstraint;
                } else {
                    $namespace = 'Symfony\\Component\\Validator\\Constraints\\';
                    $validationConstraintClass = $namespace . $validationConstraint;
                    assert(
                        class_exists($validationConstraintClass),
                        'Invalid validation constraint: '.$validationConstraint
                    );
                }

                $requirements[] = new $validationConstraintClass($options ?: null);
            }
        }

        return $requirements;
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $this->splitFieldsIntoGroups($view);
    }

    public function splitFieldsIntoGroups(FormView $view): FormView
    {
        $fields = $view->children['fields'];
        $groups = new FormView();
        $groupKeysCache = [];
        $otherGroupCache = [];

        foreach ($fields as $fieldView) {
            $groupName = $fieldView->vars['attr']['field_definition']->get('group');
            if ($groupName !== null) {
                $groupKey = array_search($groupName, $groupKeysCache, true);
                if ($groupKey === false) {
                    // new field group
                    $groupView = new FormView();
                    $groupView->vars['label'] = $groupName;
                    $groupView->vars['slug'] = Str::slug($groupName);

                    $groups->children[] = $groupView;
                    $groupKeysCache[] = $groupName;
                } else {
                    $groupView = $groups->children[$groupKey];
                }
                $groupView->children[] = $fieldView;
            } else {
                // no group defined, move to "other"
                $otherGroupCache[] = $fieldView;
            }
        }

        if (empty($otherGroupCache) === false) {
            // in the end add "other" group
            $groupView = new FormView();
            $groupView->vars['label'] = self::OHTER_GROUP_SLUG;
            $groupView->vars['slug'] = Str::slug(self::OHTER_GROUP_SLUG);
            $groupView->children = $otherGroupCache;
            $groups->children[] = $groupView;
        }

        $view->children['groups'] = $groups;

        return $view;
    }
}
