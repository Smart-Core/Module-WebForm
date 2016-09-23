<?php

namespace SmartCore\Module\WebForm\Form\Type;

use SmartCore\Module\WebForm\Entity\WebFormField;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WebFormFieldType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', null, ['attr' => ['autofocus'   => 'autofocus', 'placeholder' => 'Произвольная строка']])
            ->add('name',  null, ['attr' => ['placeholder' => 'Латинские буквы в нижем регистре и символы подчеркивания.']])
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Text'        => 'text',
                    'Textarea'    => 'textarea',
                    'Integer'     => 'integer',
                    'Email'       => 'email',
                    'URL'         => 'url',
                    'Date'        => 'date',
                    'Datetime'    => 'datetime',
                    'Checkbox'    => 'checkbox',
                    'Image'       => 'image',
                    'Choice'      => 'choice',
                    'Multiselect' => 'multiselect',
                ],
            ])
            ->add('params_yaml',   null, ['attr' => ['data-editor' => 'yaml']])
            ->add('position')
            ->add('is_enabled',    null, ['required' => false])
            ->add('is_required',   null, ['required' => false])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => WebFormField::class,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'smart_module_webform_field';
    }
}
