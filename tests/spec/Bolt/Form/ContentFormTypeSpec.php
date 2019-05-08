<?php /** @noinspection PhpParamsInspection */

namespace spec\Bolt\Form;

use Bolt\Configuration\Content\ContentType;
use Bolt\Configuration\Content\FieldType;
use Bolt\Entity\Content;
use Bolt\Form\ContentFormType;
use Bolt\Form\Field\FieldValueModelTransformer;
use Doctrine\Common\Collections\ArrayCollection;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Validator\Constraints;

/**
 * @mixin ContentFormType
 */
class ContentFormTypeSpec extends ObjectBehavior
{
    function let(FieldValueModelTransformer $fieldValueModelTransformer)
    {
        $fieldValueModelTransformer
            ->forField(Argument::type(FieldType::class))
            ->willReturn($fieldValueModelTransformer);
        $this->beConstructedWith($fieldValueModelTransformer);
    }

    private function mockContentDefinition(): ContentType
    {
        $field1 = new FieldType();
        $field1->put('name', 'field1');
        $field1->put('group', null);
        $field1->put('type', 'text');
        $field1->put('required', true);
        $field1->put('pattern', '^.+$');
        $field1->put('minLength', null);
        $field1->put('maxLength', 40);
        $field1->put('min', null);
        $field1->put('max', null);
        $field1->put('customValidation', null);

        $field2 = new FieldType();
        $field2->put('name', 'field2');
        $field2->put('group', 'group1');
        $field2->put('type', 'textarea');
        $field2->put('required', false);
        $field2->put('pattern', null);
        $field2->put('minLength', 10);
        $field2->put('maxLength', 50);
        $field2->put('min', null);
        $field2->put('max', null);
        $field2->put('customValidation', null);

        $field3 = new FieldType();
        $field3->put('name', 'field3');
        $field3->put('group', null);
        $field3->put('type', 'image');
        $field3->put('required', null);
        $field3->put('pattern', null);
        $field3->put('minLength', null);
        $field3->put('maxLength', null);
        $field3->put('min', null);
        $field3->put('max', null);
        $field3->put('customValidation', null);

        $field4 = new FieldType();
        $field4->put('name', 'field4');
        $field4->put('group', 'group2');
        $field4->put('type', 'select');
        $field4->put('required', null);
        $field4->put('pattern', null);
        $field4->put('minLength', null);
        $field4->put('maxLength', null);
        $field4->put('min', 1);
        $field4->put('max', 3);
        $field4->put('customValidation', null);

        $contentType = new ContentType();
        $contentType->put('fields', [
            'field1' => $field1,
            'field2' => $field2,
            'field3' => $field3,
            'field4' => $field4
        ]);

        return $contentType;
    }

    function it_splits_fields_into_groups(FormView $view)
    {
        $getFieldDefinition = function (string $name) {
            return $this->mockContentDefinition()->get('fields')[$name];
        };

        $fieldView1 = new FormView();
        $fieldView1->vars['attr'] = ['field_definition' => $getFieldDefinition('field1')];
        $fieldView2 = new FormView();
        $fieldView2->vars['attr'] = ['field_definition' => $getFieldDefinition('field2')];
        $fieldView3 = new FormView();
        $fieldView3->vars['attr'] = ['field_definition' => $getFieldDefinition('field3')];
        $fieldView4 = new FormView();
        $fieldView4->vars['attr'] = ['field_definition' => $getFieldDefinition('field4')];

        $view->getWrappedObject()->children['fields'] = [$fieldView1, $fieldView2, $fieldView3, $fieldView4];

        $newView = $this->splitFieldsIntoGroups($view);

        $groups = $newView->children['groups'];
        $groups->shouldBeAnInstanceOf(FormView::class);

        $groups[0]->vars['label']->shouldBe('group1');
        $groups[0]->children[0]->vars['attr']['field_definition']['name']->shouldBe('field2');
        $groups[0]->children->shouldNotHaveKey(1);

        $groups[1]->vars['label']->shouldBe('group2');
        $groups[1]->children[0]->vars['attr']['field_definition']['name']->shouldBe('field4');
        $groups[1]->children->shouldNotHaveKey(1);

        $groups[2]->vars['label']->shouldBe(ContentFormType::OHTER_GROUP_SLUG);
        $groups[2]->children[0]->vars['attr']['field_definition']['name']->shouldBe('field1');
        $groups[2]->children[1]->vars['attr']['field_definition']['name']->shouldBe('field3');
        $groups[2]->children->shouldNotHaveKey(2);
    }

    function it_injects_fields(FormInterface $form, FormInterface $fields, FormInterface $fieldForm, FormBuilderInterface $builder, Content $content)
    {
        $form->get('fields')->willReturn($fields);

        $validateArray = function (array $options, array $expectedOptions) use (&$validateArray): bool {
            foreach ($expectedOptions as $option => $value) {
                if (!isset($options[$option])) {
                    return false;
                }
                if (is_array($value)) {
                    if ($validateArray($options[$option], $value) === false) {
                        return false;
                    }
                } elseif ($value instanceof Argument\Token\TokenInterface) {
                    if ($value->scoreArgument($options[$option]) === false) {
                        return false;
                    }
                } elseif ($options[$option] !== $value) {
                    return false;
                }
            }
            return true;
        };

        $builder->create('field1', TextType::class, Argument::that(function (array $options) use ($validateArray) {
            return $validateArray($options, [
                'required' => true,
                'constraints' => [
                    Argument::type(Constraints\NotBlank::class),
                    Argument::type(Constraints\Regex::class),
                    Argument::type(Constraints\Length::class),
                ],
                'attr' => [
                    'field_definition' => Argument::type(FieldType::class),
                ],
                'property_path' => '[field1].value'
            ]);
        }))->shouldBeCalledOnce()->willReturn($builder);

        $builder->create('field2', TextareaType::class, Argument::that(function (array $options) use ($validateArray) {
            return $validateArray($options, [
                'required' => false,
                'constraints' => [
                    Argument::type(Constraints\Length::class),
                ],
                'attr' => [
                    'field_definition' => Argument::type(FieldType::class),
                ],
                'property_path' => '[field2].value',
            ]);
        }))->shouldBeCalledOnce()->willReturn($builder);

        $builder->create('field3', null, Argument::that(function (array $options) use ($validateArray) {
            return $validateArray($options, [
                'required' => true,
                'constraints' => [
                    Argument::type(Constraints\NotBlank::class),
                ],
                'attr' => [
                    'field_definition' => Argument::type(FieldType::class),
                ],
                'property_path' => '[field3].value',
            ]);
        }))->shouldBeCalledOnce()->willReturn($builder);

        $builder->create('field4', ChoiceType::class, Argument::that(function (array $options) use ($validateArray) {
            return $validateArray($options, [
                'required' => true,
                'constraints' => [
                    Argument::type(Constraints\NotBlank::class),
                    Argument::type(Constraints\Count::class),
                ],
                'attr' => [
                    'field_definition' => Argument::type(FieldType::class),
                ],
                'property_path' => '[field4].value',
            ]);
        }))->shouldBeCalledOnce()->willReturn($builder);

        $builder->addModelTransformer(Argument::type(\Bolt\Form\Field\FieldValueModelTransformer::class))
            ->shouldBeCalledTimes(4)->willReturn($builder);
        $builder->getForm()->shouldBeCalledTimes(4)->willReturn($fieldForm);
        $fields->add($fieldForm)->shouldBeCalledTimes(4);

        $content->getDefinition()->willReturn($this->mockContentDefinition());
        $content->getFields()->willReturn(new ArrayCollection());

        $this->injectFields($form, $builder, $content);
    }
}
