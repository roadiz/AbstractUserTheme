<?php
declare(strict_types=1);

namespace Themes\AbstractUserTheme\Controllers;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait LoginControllerTrait
{
    use ManualSeoTrait;

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

        return $this->render($this->getTemplatePath(), $this->assignation, null, '/');
    }

    /**
     * @return string
     */
    protected function getPageTitle(): string
    {
        return $this->get('translator')->trans('user.sign_in.page_title');
    }

    /**
     * @return string
     */
    protected function getTemplatePath(): string
    {
        return 'account/login/login.html.twig';
    }
}
