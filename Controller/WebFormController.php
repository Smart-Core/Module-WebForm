<?php

namespace SmartCore\Module\WebForm\Controller;

use Genemu\Bundle\FormBundle\Form\Core\Type\CaptchaType;
use Smart\CoreBundle\Controller\Controller;
use Smart\CoreBundle\Form\TypeResolverTtait;
use SmartCore\Bundle\CMSBundle\Module\NodeTrait;
use SmartCore\Module\WebForm\Entity\Message;
use SmartCore\Module\WebForm\Entity\WebForm;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class WebFormController extends Controller
{
    use NodeTrait;
    use TypeResolverTtait;

    /** @var int|null */
    protected $webform_id;

    /**
     * @param null $options
     *
     * @return Response
     */
    public function indexAction($options = null)
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');

        $webForm = $em->find(WebForm::class, $this->webform_id);

        if (isset($options['defaults']) and is_array($options['defaults'])) {
            $form = $this->getForm($webForm, $options['defaults']);
        } else {
            $form = $this->getForm($webForm);
        }

        $feedback_data = $this->getFlash('feedback_data');

        if (!empty($feedback_data)) {
            $form->submit(new Request($feedback_data[0]));
            $form->isValid();
        }

        $this->node->addFrontControl('crm-'.md5(microtime()))
            ->setTitle('Управление веб-формой')
            ->setUri($this->generateUrl('web_form.admin_new_messages', [
                'name' => $webForm->getName(),
            ]));

        return $this->render('@WebFormModule/index.html.twig', [
            'form'     => $form->createView(),
            'node_id'  => $this->node->getId(),
            'web_form' => $webForm,
            'options'  => $options,
        ]);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function ajaxAction(Request $request)
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        $webForm = $em->find(WebForm::class, $this->webform_id);

        $form = $this->getForm($webForm);

        // @todo продумать момент с _node_id
        $data = $request->request->all();
        $node_id = null;
        foreach ($data as $key => $value) {
            if ($key == '_node_id') {
                $node_id = $data['_node_id'];
                unset($data['_node_id']);
                break;
            }

            if (is_array($value) and array_key_exists('_node_id', $value)) {
                $node_id = $data[$key]['_node_id'];
                unset($data[$key]['_node_id']);
                break;
            }
        }

        foreach ($data as $key => $value) {
            $request->request->set($key, $value);
        }

        $form->handleRequest($request);

        if ($form->isValid()) {
            $message = new Message();
            $message
                ->setData($form->getData())
                ->setUser($this->getUser())
                ->setWebForm($webForm)
                ->setIpAddress($request->server->get('REMOTE_ADDR'))
            ;
            $this->persist($message, true);

            $this->sendNoticeEmails($webForm, $message);

            return new JsonResponse([
                'status'  => 'success',
                'message' => $webForm->getFinalText() ? $webForm->getFinalText() : 'Сообщение отправлено.',
                'data'    => [],
            ], 200);
        }

        $errors = [];
        foreach ($form->getErrors(true) as $err) {
            $errors[] = $err->getMessage();
        }

        return new JsonResponse([
            'status'  => 'error',
            'message' => 'При заполнении формы допущены ошибки.',
            'data'    => [
                'request_data' => $request->request->all(),
                'form_errors'  => $errors,
                'form_errors_as_string'  => (string) $form->getErrors(true, false),
            ],
        ], 400);
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function ajaxGetFormAction(Request $request)
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        $webForm = $em->find(WebForm::class, $this->webform_id);

        $form = $this->getForm($webForm);

        // @todo продумать момент с _node_id
        $data = $request->request->all();
        $node_id = null;
        foreach ($data as $key => $value) {
            if ($key == '_node_id') {
                $node_id = $data['_node_id'];
                unset($data['_node_id']);
                break;
            }

            if (is_array($value) and array_key_exists('_node_id', $value)) {
                $node_id = $data[$key]['_node_id'];
                unset($data[$key]['_node_id']);
                break;
            }
        }

        foreach ($data as $key => $value) {
            $request->request->set($key, $value);
        }

        $form->handleRequest($request);

        return $this->render('@WebFormModule/index.html.twig', [
            'form'     => $form->createView(),
            'node_id'  => $this->node->getId(),
            'web_form' => $webForm,
        ]);
    }

    /**
     * @param  Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function postAction(Request $request)
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        $webForm = $em->find(WebForm::class, $this->webform_id);

        $form = $this->getForm($webForm);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $message = new Message();
            $message
                ->setData($form->getData())
                ->setUser($this->getUser())
                ->setWebForm($webForm)
                ->setIpAddress($request->server->get('REMOTE_ADDR'))
            ;
            $this->persist($message, true);

            $this->sendNoticeEmails($webForm, $message);

            $this->addFlash('success', $webForm->getFinalText() ? $webForm->getFinalText() : 'Сообщение отправлено.');
        } else {
            $this->addFlash('error', 'При заполнении формы допущены ошибки.');
            $this->addFlash('feedback_data', $request->request->all());
        }

        return $this->redirect($request->getRequestUri());
    }

    /**
     * @param WebForm $webForm
     * @param Message $message
     */
    protected function sendNoticeEmails(WebForm $webForm, Message $message)
    {
        if ($webForm->getSendNoticeEmails()) {
            $addresses = [];

            foreach (explode(',', $webForm->getSendNoticeEmails()) as $email) {
                $addresses[] = trim($email);
            }

            $mailer = $this->get('mailer');

            $message = \Swift_Message::newInstance()
                ->setSubject('Сообщение с веб-формы «'.$webForm->getTitle().'» ('.$this->container->getParameter('base_url').')')
                ->setFrom($webForm->getFromEmail())
                ->setTo($addresses)
                ->setBody($this->renderView('@WebFormModule/Email/notice.email.twig', ['web_form' => $webForm, 'message' => $message]))
            ;
            $mailer->send($message);
        }
    }

    /**
     * @param WebForm $webForm
     * @param array   $defaults
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function getForm(WebForm $webForm, array $defaults = [])
    {
        $fb = $this->get('form.factory')->createNamedBuilder('web_form_'.$webForm->getName());
        $fb
            //->setAttribute('id', 'web_form_'.$webForm->getName())
            ->setErrorBubbling(false)
        ;

        foreach ($webForm->getFields() as $field) {
            if (is_array($field->getParams())) {
                $options = $field->getParams();
            } else {
                $options = [];
            }

            $options['required'] = $field->getIsRequired();
            $options['label'] = $field->getTitle();

            if (isset($defaults[$field->getName()])) {
                $options['data'] = $defaults[$field->getName()];
            }

            if (isset($options['choices'])) {
                $options['choices'] = array_flip($options['choices']);
            }

            $type = $this->resolveTypeName($field->getType());

            $fb->add($field->getName(), $type, $options);
        }

        if ($webForm->isIsUseCaptcha()) {
            $fb->add('captcha', CaptchaType::class, ['mapped' => false]);
        }

        $fb->add('send', SubmitType::class, [
            'attr'  => ['class' => 'btn btn-success'],
            'label' => $webForm->getSendButtonTitle(),
        ]);

        return $fb->getForm();
    }
}
