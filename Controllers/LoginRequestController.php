<?php
declare(strict_types=1);

namespace Themes\AbstractUserTheme\Controllers;

use RZ\Roadiz\CMS\Forms\LoginRequestForm;
use RZ\Roadiz\CMS\Traits\LoginRequestTrait;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Themes\AbstractUserTheme\AbstractUserThemeApp;

class LoginRequestController extends AbstractUserThemeApp
{
    use LoginRequestTrait;

    /**
     * @param Request $request
     * @param string  $_locale
     *
     * @return Response
     * @throws \Twig_Error_Runtime
     */
    public function requestAction(Request $request, $_locale = "en")
    {
        $this->prepareThemeAssignation(null, $this->bindLocaleFromRoute($request, $_locale));

        $this->assignation['pageMeta'] = [
            'title' => $this->get('translator')->trans('login_request.page_title') . ' — ' . $this->get('settingsBag')->get('site_name'),
        ];

        $form = $this->createForm(LoginRequestForm::class, null, [
            'entityManager' => $this->get('em'),
        ]);

        $form->handleRequest($request);

        if ($form->isValid()) {
            if (true === $this->sendConfirmationEmail(
                $form,
                $this->get('em'),
                $this->get('logger'),
                $this->get('urlGenerator')
            )) {
                return $this->redirect($this->getRedirectedUrl($_locale));
            }
            $form->addError(new FormError($this->get('translator')->trans('login_request.cant_send_confirmation_email')));
        }

        $this->assignation['form'] = $form->createView();

        return $this->render($this->getTemplatePath(), $this->assignation);
    }

    /**
     * @param string $_locale
     *
     * @return RedirectResponse
     */
    protected function getRedirectedUrl(string $_locale): string
    {
        return $this->generateUrl('themeWaitPassword', ['_locale' => $_locale]);
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
     * @return string
     */
    protected function getTemplatePath(): string
    {
        return 'account/login/request.html.twig';
    }

    /**
     * @return string
     */
    protected function getConfirmTemplatePath(): string
    {
        return 'account/login/requestConfirm.html.twig';
    }
}
