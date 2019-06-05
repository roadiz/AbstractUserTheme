<?php
declare(strict_types=1);

namespace Themes\AbstractUserTheme\Controllers;

use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Themes\AbstractUserTheme\AbstractUserThemeApp;
use Themes\AbstractUserTheme\Event\FilterUserEvent;
use Themes\AbstractUserTheme\Event\UserEvents;

class DeleteAccountController extends AbstractUserThemeApp
{
    /**
     * @param Request $request
     * @param string  $_locale
     *
     * @return Response
     * @throws \Twig_Error_Runtime
     */
    public function deleteAction(Request $request, $_locale = "en")
    {
        $this->validateAccessForRole(static::$firewallRole);
        $this->prepareThemeAssignation(null, $this->bindLocaleFromRoute($request, $_locale));

        $this->assignation['pageMeta'] = [
            'title' => $this->get('translator')->trans('user.delete.page_title') . ' — ' . $this->get('settingsBag')->get('site_name'),
        ];

        $user = $this->getUser();
        if (null === $user || !$this->isGranted(static::$firewallRole) || !($user instanceof User)) {
            throw $this->createAccessDeniedException();
        }

        /** @var Form $form */
        $form = $this->createForm(FormType::class);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $beforeEvent = new FilterUserEvent($user, $this->get('em'), $this->get('securityTokenStorage'));
            /** @var EventDispatcherInterface $eventDispatcher */
            $eventDispatcher = $this->get('dispatcher');
            $eventDispatcher->dispatch(UserEvents::USER_BEFORE_DELETE, $beforeEvent);

            $msg = $this->getTranslator()->trans('user.%name%.deleted_his_account', [
                '%name%' => $user->getEmail(),
            ]);
            $this->get('logger')->info($msg);
            $this->get('em')->remove($user);
            $this->get('em')->flush();
            $this->get('securityTokenStorage')->setToken(null);
            $request->getSession()->invalidate();

            $beforeEvent = new FilterUserEvent($user, $this->get('em'), $this->get('securityTokenStorage'));
            /** @var EventDispatcherInterface $eventDispatcher */
            $eventDispatcher = $this->get('dispatcher');
            $eventDispatcher->dispatch(UserEvents::USER_AFTER_DELETE, $beforeEvent);

            return $this->redirect($this->getRedirectedUrl($_locale));
        }

        $this->assignation['form'] = $form->createView();

        return $this->render($this->getTemplatePath(), $this->assignation);
    }

    /**
     * @return Response
     * @throws \Twig_Error_Runtime
     */
    public function confirmAction()
    {
        return $this->render($this->getConfirmTemplatePath(), $this->assignation);
    }

    /**
     * @param string $_locale
     *
     * @return RedirectResponse
     */
    protected function getRedirectedUrl(string $_locale): string
    {
        return $this->generateUrl('themeDeleteSuccessUser', ['_locale' => $_locale]);
    }

    /**
     * @return string
     */
    protected function getTemplatePath(): string
    {
        return 'account/delete.html.twig';
    }

    /**
     * @return string
     */
    protected function getConfirmTemplatePath(): string
    {
        return 'account/deleteConfirm.html.twig';
    }
}
