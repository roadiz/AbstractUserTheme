<?php
declare(strict_types=1);

namespace Themes\AbstractUserTheme\Controllers;

use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Themes\AbstractUserTheme\AbstractUserThemeApp;

class AccountController extends AbstractUserThemeApp
{
    /**
     * @param Request $request
     * @param string  $_locale
     *
     * @return Response
     * @throws \Twig_Error_Runtime
     */
    public function accountAction(Request $request, $_locale = 'en')
    {
        $this->validateAccessForRole(static::$firewallRole);
        $this->prepareThemeAssignation(null, $this->bindLocaleFromRoute($request, $_locale));

        $user = $this->getUser();
        if (!($user instanceof User)) {
            throw $this->createAccessDeniedException();
        }

        $validationToken = $this->getValidationToken();

        $this->assignation['user'] = $user;
        $this->assignation['validationToken'] = $validationToken;

        return $this->render($this->getTemplatePath(), $this->assignation);
    }

    /**
     * @return string
     */
    protected function getTemplatePath(): string
    {
        return 'account/index.html.twig';
    }
}
