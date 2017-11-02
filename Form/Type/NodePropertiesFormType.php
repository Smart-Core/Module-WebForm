<?php

namespace SmartCore\Module\WebForm\Form\Type;

use SmartCore\Bundle\CMSBundle\Module\AbstractNodePropertiesFormType;
use SmartCore\Module\WebForm\Entity\WebForm;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class NodePropertiesFormType extends AbstractNodePropertiesFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $webforms = [];
        foreach ($this->em->getRepository(WebForm::class)->findAll() as $webform) {
            $webforms[(string) $webform] = $webform->getId();
        }

        $builder
            ->add('webform_id', ChoiceType::class, [
                'choices'  => $webforms,
                'required' => false,
                'label'    => 'WebForms',
            ])
        ;
    }

    public function getBlockPrefix()
    {
        return 'web_form_node_properties';
    }
}
