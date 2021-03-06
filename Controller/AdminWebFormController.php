<?php

namespace SmartCore\Module\WebForm\Controller;

use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Exception\NotValidCurrentPageException;
use Pagerfanta\Pagerfanta;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Smart\CoreBundle\Controller\Controller;
use SmartCore\Module\WebForm\Entity\Message;
use SmartCore\Module\WebForm\Entity\WebForm;
use SmartCore\Module\WebForm\Entity\WebFormField;
use SmartCore\Module\WebForm\Form\Type\MessageType;
use SmartCore\Module\WebForm\Form\Type\WebFormFieldType;
use SmartCore\Module\WebForm\Form\Type\WebFormSettingsType;
use SmartCore\Module\WebForm\Form\Type\WebFormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;

class AdminWebFormController extends Controller
{
    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $form = $this->createForm(WebFormType::class);
        $form->add('create', SubmitType::class, ['attr' => ['class' => 'btn-primary']]);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                if ($form->get('create')->isClicked()) {
                    $webForm = $form->getData();
                    $webForm->setUser($this->getUser());
                    $this->persist($webForm, true);

                    $this->addFlash('success', 'Веб-форма создана.');
                }

                return $this->redirectToRoute('web_form.admin');
            }
        }

        return $this->render('@WebFormModule/Admin/index.html.twig', [
            'form' => $form->createView(),
            'web_forms' => $em = $this->getDoctrine()->getManager()->getRepository(WebForm::class)->findAll(),
        ]);
    }

    /**
     * @param Request $request
     * @param WebForm $webForm
     *
     * ParamConverter("webForm", options={"mapping": {"name": "name"}})
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function fieldsAction(Request $request, WebForm $webForm)
    {
        $webFormField = new WebFormField();
        $webFormField
            ->setWebForm($webForm)
            ->setUser($this->getUser())
        ;

        $form = $this->createForm(WebFormFieldType::class, $webFormField);
        $form->add('create', SubmitType::class, ['attr' => ['class' => 'btn-primary']]);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                if ($form->get('create')->isClicked()) {
                    $this->persist($form->getData(), true);
                    $this->addFlash('success', 'Поле создано.');
                }

                return $this->redirectToRoute('web_form.admin_fields', ['name' => $webForm->getName()]);
            }
        }

        $em = $this->get('doctrine.orm.entity_manager');

        return $this->render('@WebFormModule/Admin/fields.html.twig', [
            'form'            => $form->createView(),
            'nodePath'        => $this->getNodePath($webForm),
            'web_form'        => $webForm,
            'web_form_fields' => $em->getRepository(WebFormField::class)->findBy(['web_form' => $webForm], ['position' => 'ASC']),
        ]);
    }

    /**
     * @param Request $request
     * @param WebForm $webForm
     * @param WebFormField $webFormField
     *
     * @ParamConverter("webForm", options={"mapping": {"name": "name"}})
     * @ParamConverter("webFormField", options={"mapping": {"id": "id"}})
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function fieldEditAction(Request $request, WebForm $webForm, WebFormField $webFormField)
    {
        $form = $this->createForm(WebFormFieldType::class, $webFormField);
        $form
            ->add('update', SubmitType::class, ['attr' => ['class' => 'btn-primary']])
            ->add('delete', SubmitType::class, ['attr' => ['class' => 'btn-danger', 'onclick' => "return confirm('Вы уверены, что хотите удалить поле?')"]])
            ->add('cancel', SubmitType::class, ['attr' => ['class' => 'btn-default', 'formnovalidate' => 'formnovalidate']])
        ;

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->get('cancel')->isClicked()) {
                return $this->redirectToRoute('web_form.admin_fields', ['name' => $webForm->getName()]);
            }

            if ($form->get('delete')->isClicked()) {
                $this->remove($form->getData(), true);
                $this->addFlash('success', 'Поле удалено.');

                return $this->redirectToRoute('web_form.admin_fields', ['name' => $webForm->getName()]);
            }

            if ($form->isValid() and $form->get('update')->isClicked()) {
                $this->persist($form->getData(), true);
                $this->addFlash('success', 'Поле обновлено.');

                return $this->redirectToRoute('web_form.admin_fields', ['name' => $webForm->getName()]);
            }
        }

        return $this->render('@WebFormModule/Admin/field_edit.html.twig', [
            'form'           => $form->createView(),
            'web_form'       => $webForm,
            'web_form_field' => $webFormField,
        ]);
    }

    /**
     * @param Request $request
     * @param WebForm $webForm
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function settingsAction(Request $request, WebForm $webForm)
    {
        $form = $this->createForm(WebFormSettingsType::class, $webForm);
        $form
            ->add('update', SubmitType::class, ['attr' => ['class' => 'btn-primary']])
            ->add('cancel', SubmitType::class, ['attr' => ['class' => 'btn-default', 'formnovalidate' => 'formnovalidate']])
        ;

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->get('cancel')->isClicked()) {
                return $this->redirectToRoute('web_form.admin_new_messages', ['name' => $webForm->getName()]);
            }

            if ($form->isValid() and $form->get('update')->isClicked()) {
                $this->persist($form->getData(), true);
                $this->addFlash('success', 'Настройки обновлены.');

                return $this->redirectToRoute('web_form.admin_settings', ['name' => $webForm->getName()]);
            }
        }

        return $this->render('@WebFormModule/Admin/settings.html.twig', [
            'form'      => $form->createView(),
            'nodePath'  => $this->getNodePath($webForm),
            'web_form'  => $webForm,
        ]);
    }

    /**
     * @param Request $request
     * @param WebForm $webForm
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function messagesAction(Request $request, WebForm $webForm, $status)
    {
        $em = $this->get('doctrine.orm.entity_manager');

        $pagerfanta = new Pagerfanta(new DoctrineORMAdapter(
            $em->getRepository(Message::class)->getFindByStatusQuery($webForm, $status)
        ));
        $pagerfanta->setMaxPerPage(20);

        try {
            $pagerfanta->setCurrentPage($request->query->get('page', 1));
        } catch (NotValidCurrentPageException $e) {
            return $this->redirectToRoute('web_form.admin_new_messages', ['name' => $webForm->getName()]);
        }

        switch ($status) {
            case Message::STATUS_IN_PROGRESS:
                $title = 'In progress';
                break;
            case Message::STATUS_FINISHED:
                $title = 'Finished';
                break;
            case Message::STATUS_REJECTED:
                $title = 'Rejected';
                break;
            default:
                $title = 'New messages';
        }

        return $this->render('@WebFormModule/Admin/messages.html.twig', [
            'web_form'   => $webForm,
            'nodePath'   => $this->getNodePath($webForm),
            'pagerfanta' => $pagerfanta,
            'title'      => $title,
        ]);
    }

    /**
     * @param Request $request
     * @param WebForm $webForm
     * @param Message $message
     *
     * @ParamConverter("webForm", options={"mapping": {"name": "name"}})
     * @ParamConverter("Message", options={"mapping": {"id": "id"}})
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editMessageAction(Request $request, WebForm $webForm, Message $message)
    {
        $form = $this->createForm(MessageType::class, $message);
        $form
            ->add('update', SubmitType::class, ['attr' => ['class' => 'btn-primary']])
            ->add('cancel', SubmitType::class, ['attr' => ['class' => 'btn-default', 'formnovalidate' => 'formnovalidate']])
        ;

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->get('cancel')->isClicked()) {
                return $this->redirectToRoute('web_form.admin_new_messages', ['name' => $webForm->getName()]);
            }

            if ($form->isValid() and $form->get('update')->isClicked() and $form->isValid()) {
                $this->persist($form->getData(), true);
                $this->addFlash('success', 'Сообщение обновлено.');

                return $this->redirectToRoute('web_form.admin_new_messages', ['name' => $webForm->getName()]);
            }
        }

        return $this->render('@WebFormModule/Admin/edit_message.html.twig', [
            'form'      => $form->createView(),
            'web_form'  => $webForm,
        ]);
    }

    /**
     * @param WebForm $webForm
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function manageAction(WebForm $webForm)
    {
        return $this->redirectToRoute('web_form.admin_new_messages', ['name' => $webForm->getName()]);
    }

    /**
     * @param \SmartCore\Module\WebForm\Entity\WebForm $webForm
     *
     * @return null|string
     *
     * @throws \Exception
     */
    protected function getNodePath(WebForm $webForm)
    {
        foreach ($this->get('cms.node')->findByModule('WebForm') as $node) {
            if ($node->getParam('webform_id') === (int) $webForm->getId()) {
                return $this->get('cms.folder')->getUri($node);
            }
        }

        return null;
    }
}
