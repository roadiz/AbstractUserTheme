<?php
declare(strict_types=1);

namespace Themes\AbstractUserTheme\Controllers;

use libphonenumber\PhoneNumber;
use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Themes\AbstractUserTheme\Entity\ValidationToken;
use Themes\AbstractUserTheme\Form\UserVerifyType;
use Themes\AbstractUserTheme\Validator\AccountValidatorInterface;

trait VerifyAccountControllerTrait
{
    use ManualSeoTrait;

    abstract protected function getValidationToken(): ?ValidationToken;

    protected function getPageTitle(): string
    {
        return $this->get('translator')->trans('user_verify.page_title');
    }

    /**
     * @param Request $request
     * @param string $_locale
     *
     * @return Response
     */
    public function verifyUserAction(Request $request, $_locale = 'en')
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

        if (null !== $validationToken && $validationToken->isValidated()) {
            return $this->redirect($this->getAccountRedirectedUrl($_locale));
        }

        if ($accountValidator->useSmsValidationMethod()) {
            /** @var Form $verifyForm */
            $verifyForm = $this->createForm(UserVerifyType::class);
            $this->assignation['sms_verification'] = true;
        } else {
            /** @var Form $verifyForm */
            $verifyForm = $this->createForm(FormType::class);
            $this->assignation['email_verification'] = true;
        }
        $verifyForm->handleRequest($request);

        if ($verifyForm->isSubmitted() && $verifyForm->isValid()) {
            if (null === $validationToken) {
                $validationToken = new ValidationToken($user);
            }
            if (!$validationToken->isValidated()) {
                if ($accountValidator->useSmsValidationMethod() && $verifyForm->has('phone')) {
                    /** @var PhoneNumber $phoneNumber */
                    $phoneNumber = $verifyForm->get('phone')->getData();
                    $user->setPhone($accountValidator->parsePhoneNumber(
                        $validationToken,
                        $phoneNumber
                    ));
                }
                try {
                    $accountValidator->sendValidationToken($user, $validationToken);
                    $this->get('em')->merge($validationToken);
                    $this->get('em')->flush();
                    return $this->redirect($this->getRedirectedUrl($_locale));
                } catch (\Exception $e) {
                    $accountValidator->resetValidationToken($validationToken);
                    $this->get('em')->flush();
                    $verifyForm->addError(new FormError($e->getMessage()));
                }
            } else {
                $verifyForm->addError(new FormError('user_verify.validation_token_has_already_been_sent'));
            }
        }

        $this->assignation['form'] = $verifyForm->createView();

        return $this->render($this->getTemplatePath(), $this->assignation, null, '/');
    }

    /**
     * @param string $_locale
     *
     * @return string
     */
    protected function getRedirectedUrl(string $_locale): string
    {
        return $this->generateUrl('themeVerifyUserTokenPage', ['_locale' => $_locale]);
    }

    /**
     * @return string
     */
    protected function getTemplatePath(): string
    {
        return 'account/verify/verify.html.twig';
    }
}
