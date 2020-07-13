<?php
declare(strict_types=1);

namespace Themes\AbstractUserTheme\Security\Authentication;

use RZ\Roadiz\Core\Authentication\AuthenticationSuccessHandler as BaseAuthenticationSuccessHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class AuthenticationSuccessHandler extends BaseAuthenticationSuccessHandler
{
    /**
     * @param Request $request
     * @param TokenInterface $token
     * @return Response
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        /*
         * TODO: Add customer logic here after login…
         */
        return parent::onAuthenticationSuccess($request, $token);
    }
}
