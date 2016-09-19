<?php

namespace SmartCore\Module\WebForm\Form\Type;

use SmartCore\Bundle\CMSBundle\Module\AbstractNodePropertiesFormType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class NodePropertiesFormType extends AbstractNodePropertiesFormType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $webforms = [];
        foreach ($this->em->getRepository('WebFormModule:WebForm')->findAll() as $webform) {
            $webforms[$webform->getId()] = (string) $webform;
        }

        $builder
            ->add('webform_id', ChoiceType::class, [
                'choices'  => $webforms,
                'required' => false,
                'label'    => 'WebForms',
            ])
        ;
    }

    public function getName()
    {
        return 'web_form_node_properties';
    }
}
