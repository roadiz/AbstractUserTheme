<?php
declare(strict_types=1);

namespace Themes\AbstractUserTheme\Controllers;

use Symfony\Component\HttpFoundation\Request;
use Themes\AbstractUserTheme\AbstractUserThemeApp;

class LoginController extends AbstractUserThemeApp
{
    /**
     * @param Request $request
     * @param string  $_locale
     *
     * @return \Symfony\Component\HttpFoundation\Response
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

        if ($request->query->has('_target_path') &&
            1 === preg_match('#^\/#', $request->query->get('_target_path'))) {
            $this->assignation['_target_path'] = $request->query->get('_target_path');
        } else {
            $this->assignation['_target_path'] = $this->generateUrl('themeAccount', ['_locale' => $_locale]);
        }

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
