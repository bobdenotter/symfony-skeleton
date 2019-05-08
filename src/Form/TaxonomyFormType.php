<?php

declare(strict_types=1);

namespace Bolt\Form;

use Bolt\Entity\Taxonomy;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TaxonomyFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type')
            ->add('slug');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Taxonomy::class,
            'empty_data' => function (FormInterface $form): Taxonomy {
                return new Taxonomy(
                    $form->get('type')->getData(),
                    $form->get('slug')->getData()
                );
            },
        ]);
    }
}
