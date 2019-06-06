<?php
declare(strict_types=1);

namespace Themes\AbstractUserTheme\Controllers;

use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Themes\AbstractUserTheme\Event\FilterUserEvent;
use Themes\AbstractUserTheme\Event\UserEvents;
use Themes\AbstractUserTheme\Form\VerifyTokenType;

trait VerifyValidationTokenControllerTrait
{
    use ManualSeoTrait;

    protected function getPageTitle(): string
    {
        return $this->get('translator')->trans('user_verify.page_title');
    }

    /**
     * @param Request $request
     * @param string  $_locale
     *
     * @return Response
     * @throws \Twig_Error_Runtime
     */
    public function verifyUserTokenAction(Request $request, $_locale = 'fr')
    {
        $this->validateAccessForRole(static::$firewallRole);
        $this->prepareThemeAssignation(null, $this->bindLocaleFromRoute($request, $_locale));

        $user = $this->getUser();
        if (!($user instanceof User)) {
            throw $this->createAccessDeniedException();
        }

        $validationToken = $this->getValidationToken();

        if (null !== $validationToken && $validationToken->isValidated()) {
            return $this->redirect($this->getAccountRedirectedUrl($_locale));
        }

        /** @var Form $verifyTokenForm */
        $verifyTokenForm = $this->createForm(VerifyTokenType::class);
        $verifyTokenForm->handleRequest($request);

        if ($verifyTokenForm->isValid()) {
            if (null === $validationToken) {
                $verifyTokenForm->addError(new FormError('user_verify.token_is_null'));
            } elseif ($validationToken->isValidated()) {
                $verifyTokenForm->addError(new FormError('user_verify.account_is_already_validated'));
            } elseif (!$validationToken->isValidationTokenValid()) {
                $verifyTokenForm->addError(new FormError('user_verify.token_has_expired'));
            } elseif ($verifyTokenForm->get('token')->getData() === $validationToken->getValidationToken()) {
                $validationToken->setValidated(true);
                $validationToken->setValidationToken(null);
                $validationToken->setValidationTokenExpiresAt(null);
                $this->get('em')->flush();

                $event = new FilterUserEvent($user, $this->get('em'), $this->get('securityTokenStorage'));
                /** @var EventDispatcherInterface $eventDispatcher */
                $eventDispatcher = $this->get('dispatcher');
                $eventDispatcher->dispatch(UserEvents::USER_VALIDATED, $event);

                return $this->redirect($this->getAccountRedirectedUrl($_locale));
            } else {
                $verifyTokenForm->addError(new FormError('user_verify.token_does_not_match'));
            }
        }
        $this->assignation['user'] = $user;
        $this->assignation['validationToken'] = $validationToken;
        $this->assignation['form'] = $verifyTokenForm->createView();

        return $this->render($this->getTemplatePath(), $this->assignation, null, '/');
    }

    /**
     * @return string
     */
    protected function getTemplatePath(): string
    {
        return 'account/verify/verifyToken.html.twig';
    }
}
