<?php
declare(strict_types=1);

namespace Themes\AbstractUserTheme\Controllers;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Themes\AbstractUserTheme\AbstractUserThemeApp;

class LoginController extends AbstractUserThemeApp
{
    /**
     * @param Request $request
     * @param string  $_locale
     *
     * @return Response
     * @throws \Twig_Error_Runtime
     */
    public function loginAction(Request $request, $_locale = 'en')
    {
        $this->prepareThemeAssignation(null, $this->bindLocaleFromRoute($request));

        $helper = $this->get('securityAuthenticationUtils');
        $this->assignation['last_username'] = $helper->getLastUsername();
        $this->assignation['error'] = $helper->getLastAuthenticationError();

        $this->assignation['pageMeta'] = [
            'title' => $this->get('translator')->trans('user.sign_in.page_title') . ' — ' . $this->get('settingsBag')->get('site_name'),
        ];
        return $this->render($this->getTemplatePath(), $this->assignation);
    }

    /**
     * @return string
     */
    protected function getTemplatePath(): string
    {
        return 'account/login/login.html.twig';
    }
}
