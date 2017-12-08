<?php

declare(strict_types=1);

namespace SmartCore\Module\WebForm\Twig;

use SmartCore\Module\WebForm\Entity\Message;
use SmartCore\Module\WebForm\Entity\WebForm;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class WebFormExtension extends \Twig_Extension
{
    use ContainerAwareTrait;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of functions
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('module_webform_count_new',  [$this, 'getNewMessagesCount']),
            new \Twig_SimpleFunction('module_webform_count_inprogress',  [$this, 'getInProgressCount']),
        ];
    }

    /**
     * @param WebForm $webForm
     *
     * @return int
     */
    public function getNewMessagesCount(WebForm $webForm)
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->container->get('doctrine.orm.entity_manager');

        return $em->getRepository('WebFormModuleBundle:Message')->getCountByStatus($webForm, Message::STATUS_NEW);
    }

    /**
     * @param WebForm $webForm
     *
     * @return int
     */
    public function getInProgressCount(WebForm $webForm)
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->container->get('doctrine.orm.entity_manager');

        return $em->getRepository('WebFormModuleBundle:Message')->getCountByStatus($webForm, Message::STATUS_IN_PROGRESS);
    }
}
