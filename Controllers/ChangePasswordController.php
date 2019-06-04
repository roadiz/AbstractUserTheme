<?php
declare(strict_types=1);

namespace Themes\AbstractUserTheme\Controllers;

use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Themes\AbstractUserTheme\AbstractUserThemeApp;
use Themes\AbstractUserTheme\Form\ChangePasswordType;

class ChangePasswordController extends AbstractUserThemeApp
{
    /**
     * @param Request $request
     * @param string  $_locale
     *
     * @return Response
     * @throws \Twig_Error_Runtime
     * @internal param $token
     */
    public function changeAction(Request $request, $_locale = 'en')
    {
        $this->validateAccessForRole(static::$firewallRole);
        $this->prepareThemeAssignation(null, $this->bindLocaleFromRoute($request, $_locale));

        $user = $this->getUser();
        if (!($user instanceof User)) {
            throw $this->createAccessDeniedException();
        }

        $this->assignation['pageMeta'] = [
            'title' => $this->get('translator')->trans('user_change_password.page_title') . ' — ' . $this->get('settingsBag')->get('site_name'),
        ];

        $form = $this->createForm(ChangePasswordType::class, $user);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->get('em')->flush();
            return $this->redirect($this->getRedirectedUrl($_locale));
        }

        $this->assignation['form'] = $form->createView();

        return $this->render($this->getTemplatePath(), $this->assignation);
    }

    /**
     * @param Request $request
     * @param string  $_locale
     *
     * @return Response
     * @throws \Twig_Error_Runtime
     */
    public function confirmChangeAction(Request $request, $_locale = 'en')
    {
        $this->validateAccessForRole(static::$firewallRole);
        $this->prepareThemeAssignation(null, $this->bindLocaleFromRoute($request, $_locale));

        return $this->render($this->getConfirmTemplatePath(), $this->assignation);
    }

    /**
     * @return string
     */
    protected function getTemplatePath(): string
    {
        return 'account/password/change.html.twig';
    }

    /**
     * @return string
     */
    protected function getConfirmTemplatePath(): string
    {
        return 'account/password/changeConfirm.html.twig';
    }

    /**
     * @param string $_locale
     *
     * @return RedirectResponse
     */
    protected function getRedirectedUrl(string $_locale): string
    {
        return $this->generateUrl('themeConfirmChangePassword', ['_locale' => $_locale]);
    }
}
