<?php
declare(strict_types=1);

namespace Themes\AbstractUserTheme\Controllers;

use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use MessageBird\Client;
use MessageBird\Objects\Message;
use RZ\Roadiz\Core\Entities\User;
use RZ\Roadiz\Utils\EmailManager;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Themes\AbstractUserTheme\AbstractUserThemeApp;
use Themes\AbstractUserTheme\Entity\ValidationToken;
use Themes\AbstractUserTheme\Form\UserVerifyType;
use Themes\AbstractUserTheme\Security\ValidationTokenGenerator;

class VerifyAccountController extends AbstractUserThemeApp
{
    /**
     * @param Request $request
     * @param string  $_locale
     *
     * @return Response
     * @throws \Twig_Error_Runtime
     */
    public function verifyUserAction(Request $request, $_locale = 'en')
    {
        $this->validateAccessForRole(static::$firewallRole);
        $this->prepareThemeAssignation(null, $this->bindLocaleFromRoute($request, $_locale));

        $this->assignation['pageMeta'] = [
            'title' => $this->get('translator')->trans('user_verify.page_title') . ' — ' . $this->get('settingsBag')->get('site_name'),
        ];

        $user = $this->getUser();
        if (!($user instanceof User)) {
            throw $this->createAccessDeniedException();
        }

        $validationToken = $this->getValidationToken();

        if (null !== $validationToken && $validationToken->isValidated()) {
            return $this->redirect($this->getAccountRedirectedUrl($_locale));
        }

        if ($this->useSmsValidationMethod()) {
            /** @var Form $verifyForm */
            $verifyForm = $this->createForm(UserVerifyType::class);
            $this->assignation['sms_verification'] = true;
        } else {
            /** @var Form $verifyForm */
            $verifyForm = $this->createForm(FormType::class);
            $this->assignation['email_verification'] = true;
        }
        $verifyForm->handleRequest($request);

        if ($verifyForm->isValid()) {
            if (null === $validationToken) {
                $validationToken = new ValidationToken($user);
            }
            if (!$validationToken->isValidated()) {
                if ($this->useSmsValidationMethod() && $verifyForm->has('phone')) {
                    /** @var PhoneNumber $phoneNumber */
                    $phoneNumber = $verifyForm->get('phone')->getData();
                    $phoneUtils = PhoneNumberUtil::getInstance();
                    $formattedPhone = $phoneUtils->format($phoneNumber, PhoneNumberFormat::E164);
                    $user->setPhone($formattedPhone);
                    // Dont set local to get ISO country code and not Country name.
                    $isoCode = $phoneUtils->getRegionCodeForNumber($phoneNumber);

                    if ('' !== $isoCode) {
                        $validationToken->setCountryCode($isoCode);
                    }
                }

                $tokenGenerator = new ValidationTokenGenerator();
                $validationToken->setValidationToken($tokenGenerator->generatePassword(10));
                $expiresAt = new \DateTime();
                $expiresAt->add(new \DateInterval('PT' . $this->getSmsValidity() . 'S'));
                $validationToken->setValidationTokenExpiresAt($expiresAt);

                try {
                    $this->sendValidationToken($user, $validationToken);
                    $this->get('em')->merge($validationToken);
                    $this->get('em')->flush();
                    return $this->redirect($this->getRedirectedUrl($_locale));
                } catch (\Exception $e) {
                    $validationToken->setValidationToken(null);
                    $validationToken->setValidationTokenExpiresAt(null);
                    $this->get('em')->flush();
                    $verifyForm->addError(new FormError($e->getMessage()));
                }
            } else {
                $verifyForm->addError(new FormError('user_verify.validation_token_has_already_been_sent'));
            }
        }

        $this->assignation['form'] = $verifyForm->createView();

        return $this->render($this->getTemplatePath(), $this->assignation);
    }

    /**
     * @param User            $user
     * @param ValidationToken $validationToken
     *
     * @return void
     * @throws \MessageBird\Exceptions\HttpException
     * @throws \MessageBird\Exceptions\RequestException
     * @throws \MessageBird\Exceptions\ServerException
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    protected function sendValidationToken(User $user, ValidationToken $validationToken)
    {
        $this->assignation['user'] = $user;
        $this->assignation['validationToken'] = $validationToken;
        $this->assignation['email'] = $user->getEmail();

        /*
         * SMS gateway
         */
        if ($this->useSmsValidationMethod()) {
            $this->sendValidationSms($user);
        } else {
            $this->sendValidationEmail($user);
        }
    }

    /**
     * @param string $_locale
     *
     * @return RedirectResponse
     */
    protected function getRedirectedUrl(string $_locale): string
    {
        return $this->generateUrl('themeVerifyUserTokenPage', ['_locale' => $_locale]);
    }

    /**
     * @return bool
     */
    protected function useSmsValidationMethod(): bool
    {
        return false !== $this->get('settingsBag')->get('messagebird_access_key') &&
            '' != $this->get('settingsBag')->get('messagebird_access_key');
    }

    /**
     * @return string Max 11 characters
     */
    protected function getSmsOriginator(): string
    {
        return substr($this->get('settingsBag')->get('site_name'), 0, 11);
    }

    /**
     * @return int
     */
    protected function getSmsValidity(): int
    {
        return 60*5;
    }

    /**
     * @param User $user
     *
     * @return \MessageBird\Objects\Balance|\MessageBird\Objects\Hlr|\MessageBird\Objects\Lookup|Message|\MessageBird\Objects\Verify|\MessageBird\Objects\VoiceMessage
     * @throws \MessageBird\Exceptions\HttpException
     * @throws \MessageBird\Exceptions\RequestException
     * @throws \MessageBird\Exceptions\ServerException
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    protected function sendValidationSms(User $user)
    {
        $MessageBird = new Client($this->get('settingsBag')->get('messagebird_access_key'));
        $message = new Message();
        $message->originator = $this->getSmsOriginator();
        $message->validity = $this->getSmsValidity();
        $message->recipients = [
            // Remove + sign from international phone number
            str_replace('+', '', $user->getPhone())
        ];
        $message->body = $this->getTwig()->render($this->getSmsTemplatePath(), $this->assignation);
        $messageReturn = $MessageBird->messages->create($message);
        $this->get('logger')->debug('MessageBird trace: ' . $messageReturn->getMessage());
        return $messageReturn;
    }

    /**
     * @param User $user
     */
    protected function sendValidationEmail(User $user)
    {
        /** @var EmailManager $emailManager */
        $emailManager = $this->get('emailManager');
        $emailManager->setReceiver($user->getEmail());
        $emailManager->setSubject($this->getTranslator()->trans($this->getValidationEmailSubject()));
        $emailManager->setEmailTemplate($this->getEmailTemplatePath());
        $emailManager->setAssignation($this->assignation);

        $emailManager->createMessage();
        $emailManager->send();
    }

    /**
     * @return string
     */
    protected function getValidationEmailSubject(): string
    {
        return 'user_verify.email.subject';
    }

    /**
     * @return string
     */
    protected function getSmsTemplatePath(): string
    {
        return 'account/email/token-sms.txt.twig';
    }
    /**
     * @return string
     */
    protected function getEmailTemplatePath(): string
    {
        return 'account/email/token.html.twig';
    }

    /**
     * @return string
     */
    protected function getTemplatePath(): string
    {
        return 'account/verify/verify.html.twig';
    }
}
