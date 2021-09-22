<?php
declare(strict_types=1);

namespace Themes\AbstractUserTheme\Controllers;

use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Themes\AbstractUserTheme\Entity\ValidationToken;
use Themes\AbstractUserTheme\Event\UserValidatedEvent;
use Themes\AbstractUserTheme\Form\VerifyTokenType;
use Themes\AbstractUserTheme\Validator\AccountValidatorInterface;
use Themes\AbstractUserTheme\Validator\InvalidValidationTokenException;
use Twig\Error\RuntimeError;

trait VerifyValidationTokenControllerTrait
{
    use ManualSeoTrait;

    abstract protected function getValidationToken(): ?ValidationToken;

    protected function getPageTitle(): string
    {
        return $this->get('translator')->trans('user_verify.page_title');
    }

    /**
     * @param Request $request
     * @param string  $_locale
     *
     * @return Response
     * @throws RuntimeError
     */
    public function verifyUserTokenAction(Request $request, $_locale = 'fr')
    {
        $this->denyAccessUnlessGranted(static::$firewallRole);
        $this->prepareThemeAssignation(null, $this->bindLocaleFromRoute($request, $_locale));

        $user = $this->getUser();
        if (!($user instanceof User)) {
            throw $this->createAccessDeniedException();
        }

        $validationToken = $this->getValidationToken();
        /** @var AccountValidatorInterface $accountValidator */
        $accountValidator = $this->get(AccountValidatorInterface::class);

        /*
         * Validation token is already validated…
         */
        if (null !== $validationToken && $validationToken->isValidated()) {
            return $this->redirect($this->getAccountRedirectedUrl($_locale));
        }

        /** @var Form $verifyTokenForm */
        $verifyTokenForm = $this->createForm(VerifyTokenType::class);
        $verifyTokenForm->handleRequest($request);

        if ($verifyTokenForm->isSubmitted() && $verifyTokenForm->isValid()) {
            try {
                $accountValidator->validate(
                    $validationToken,
                    $verifyTokenForm->get('token')->getData()
                );
                $this->em()->flush();

                $this->dispatchEvent(new UserValidatedEvent(
                    $user,
                    $this->em(),
                    $this->get('securityTokenStorage')
                ));

                return $this->redirect($this->getAccountRedirectedUrl($_locale));
            } catch (InvalidValidationTokenException $e) {
                $verifyTokenForm->addError(new FormError($e->getMessage()));
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
