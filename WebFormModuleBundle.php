<?php

namespace SmartCore\Module\WebForm;

use SmartCore\Bundle\CMSBundle\Module\ModuleBundle;
use SmartCore\Module\WebForm\Entity\Message;
use SmartCore\Module\WebForm\Entity\WebForm;

class WebFormModuleBundle extends ModuleBundle
{
    protected $adminMenuBeforeCode = '<i class="fa fa-bullhorn"></i>';

    public function getRequiredParams()
    {
        return [
            'webform_id',
        ];
    }

    public function getNotifications()
    {
        $data = [];

        $em = $this->container->get('doctrine.orm.entity_manager');

        foreach ($em->getRepository(WebForm::class)->findAll() as $webForm) {
            $count = $em->getRepository('WebFormModuleBundle:Message')->getCountByStatus($webForm, Message::STATUS_NEW); // @todo fix for sf plugin

            if ($count) {
                // @todo вынести уведомления на уровень движка.
                // new Notification()
                $data[] = [
                    'title' => 'Новые сообщения в веб-форме: '.$webForm->getTitle(),
                    'descr' => '',
                    'count' => $count,
                    'badge' => 'important',
                    'icon'  => '',
                    'html'  => null,
                    'url'   => $this->container->get('router')->generate('web_form.admin_new_messages', ['name' => $webForm->getName()]),
                ];
            }
        }

        return $data;
    }
}
