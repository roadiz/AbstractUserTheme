<?php
declare(strict_types=1);

namespace Themes\AbstractUserTheme\Validator;

use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use MessageBird\Client;
use MessageBird\Exceptions\RequestException;
use MessageBird\Objects\Message;
use MessageBird\Objects\Verify;
use Psr\Log\LoggerInterface;
use RZ\Roadiz\Core\Entities\User;
use RZ\Roadiz\Utils\EmailManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormError;
use Themes\AbstractUserTheme\Entity\ValidationToken;
use Themes\AbstractUserTheme\Event\UserValidatedEvent;
use Themes\AbstractUserTheme\Security\ValidationTokenGenerator;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class AccountValidator implements AccountValidatorInterface
{
    /**
     * @var bool
     */
    protected $needUserValidation = true;
    /**
     * @var string|null
     */
    protected $messageBirdAccessKey = null;
    /**
     * @var EmailManager
     */
    protected $emailManager;
    /**
     * @var string
     */
    protected $siteName = 'Roadiz';
    /**
     * @var Environment
     */
    protected $templating;
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * AccountValidator constructor.
     * @param bool $needUserValidation
     * @param string|null $messageBirdAccessKey
     * @param EmailManager $emailManager
     * @param string $siteName
     * @param Environment $templating
     * @param LoggerInterface $logger
     */
    public function __construct(
        bool $needUserValidation,
        ?string $messageBirdAccessKey,
        EmailManager $emailManager,
        string $siteName,
        Environment $templating,
        LoggerInterface $logger
    ) {
        $this->needUserValidation = $needUserValidation;
        $this->messageBirdAccessKey = $messageBirdAccessKey;
        $this->emailManager = $emailManager;
        $this->siteName = $siteName;
        $this->templating = $templating;
        $this->logger = $logger;
    }

    /**
     * @param ValidationToken|null $validationToken
     * @param mixed $token
     * @return ValidationToken
     * @throws InvalidValidationTokenException
     * @throws \Exception
     */
    public function validate(?ValidationToken $validationToken, $token): ValidationToken
    {
        if (null === $validationToken) {
            throw new InvalidValidationTokenException('user_verify.token_is_null');
        } elseif ($validationToken->isValidated()) {
            $this->resetValidationToken($validationToken);
        } elseif (!is_string($token)) {
            throw new InvalidValidationTokenException('user_verify.token_is_not_string');
        } elseif (empty($token)) {
            throw new InvalidValidationTokenException('user_verify.token_is_empty');
        } elseif (!$validationToken->isValidationTokenValid()) {
            throw new InvalidValidationTokenException('user_verify.token_has_expired');
        } elseif ($token !== $validationToken->getValidationToken()) {
            throw new InvalidValidationTokenException('user_verify.token_does_not_match');
        }

        $this->resetValidationToken($validationToken);
        $validationToken->setValidated(true);
        return $validationToken;
    }

    /**
     * @param ValidationToken $validationToken
     * @param User $user
     * @param PhoneNumber $phoneNumber
     * @return ValidationToken
     */
    public function parsePhoneNumber(
        ValidationToken $validationToken,
        User $user,
        PhoneNumber $phoneNumber
    ): ValidationToken {
        $phoneUtils = PhoneNumberUtil::getInstance();
        $formattedPhone = $phoneUtils->format($phoneNumber, PhoneNumberFormat::E164);
        $user->setPhone($formattedPhone);
        // Dont set local to get ISO country code and not Country name.
        $isoCode = $phoneUtils->getRegionCodeForNumber($phoneNumber);
        if ('' !== $isoCode) {
            $validationToken->setCountryCode($isoCode);
        }

        return $validationToken;
    }


    /**
     * @return bool
     */
    public function canByPassValidation(): bool
    {
        return !$this->useSmsValidationMethod() || !$this->needUserValidation;
    }

    /**
     * @return bool
     */
    public function canByPassValidationRequest(): bool
    {
        return !$this->useSmsValidationMethod() && $this->needUserValidation;
    }

    /**
     * @return bool
     */
    public function useSmsValidationMethod(): bool
    {
        return null !== $this->messageBirdAccessKey && '' != $this->messageBirdAccessKey;
    }

    /**
     * @param ValidationToken $validationToken
     * @return ValidationToken
     * @throws \Exception
     */
    protected function populateValidationToken(ValidationToken $validationToken): ValidationToken
    {
        $tokenGenerator = new ValidationTokenGenerator();
        $validationToken->setValidationToken($tokenGenerator->generatePassword(10));
        $expiresAt = new \DateTime();
        $expiresAt->add(new \DateInterval('PT' . $this->getTokenValidity() . 'S'));
        $validationToken->setValidationTokenExpiresAt($expiresAt);

        return $validationToken;
    }

    /**
     * @param ValidationToken $validationToken
     * @return ValidationToken
     */
    public function resetValidationToken(ValidationToken $validationToken): ValidationToken
    {
        $validationToken->setValidationToken(null);
        $validationToken->setValidationTokenExpiresAt(null);

        return $validationToken;
    }

    /**
     * @param User $user
     * @param ValidationToken $validationToken
     *
     * @return void
     * @throws \MessageBird\Exceptions\HttpException
     * @throws RequestException
     * @throws \MessageBird\Exceptions\ServerException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws \Exception
     */
    public function sendValidationToken(User $user, ValidationToken $validationToken): void
    {
        $this->populateValidationToken($validationToken);

        $data['user'] = $user;
        $data['validationToken'] = $validationToken;
        $data['email'] = $user->getEmail();

        /*
         * SMS gateway
         */
        if ($this->useSmsValidationMethod() && null !== $user->getPhone()) {
            $data['sms_verification'] = true;
            $this->sendValidationSms($user, $data);
        } else {
            $data['email_verification'] = true;
            $this->sendValidationEmail($user, $data);
        }
    }

    /**
     * @param User $user
     * @param array $data
     * @return \MessageBird\Objects\Balance|\MessageBird\Objects\Hlr|\MessageBird\Objects\Lookup|Message|\MessageBird\Objects\Verify|\MessageBird\Objects\VoiceMessage
     * @throws LoaderError
     * @throws RequestException
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws \MessageBird\Exceptions\HttpException
     * @throws \MessageBird\Exceptions\ServerException
     */
    protected function sendValidationSms(User $user, array $data)
    {
        $MessageBird = new Client($this->messageBirdAccessKey);
        $message = new Message();
        $message->originator = $this->getSmsOriginator();
        $message->validity = $this->getTokenValidity();
        $message->recipients = [
            // Remove + sign from international phone number
            str_replace('+', '', $user->getPhone() ?? '')
        ];
        $message->body = $this->templating->render($this->getSmsTemplatePath(), $data);
        $messageReturn = $MessageBird->messages->create($message);
        if ($messageReturn instanceof Verify) {
            $this->logger->debug('MessageBird trace: ' . $messageReturn->getMessage());
        }
        return $messageReturn;
    }

    /**
     * @param User $user
     * @param array $data
     * @return int
     * @throws \Exception
     */
    protected function sendValidationEmail(User $user, array $data)
    {
        $this->emailManager->setReceiver($user->getEmail() ?? '');
        $this->emailManager->setSubject($this->emailManager->getTranslator()->trans($this->getValidationEmailSubject()));
        $this->emailManager->setEmailTemplate($this->getEmailTemplatePath());
        $this->emailManager->setAssignation($data);

        $this->emailManager->createMessage();
        return $this->emailManager->send();
    }

    /**
     * @return string Max 11 characters
     */
    protected function getSmsOriginator(): string
    {
        return substr($this->siteName, 0, 11);
    }

    /**
     * @return int
     */
    protected function getTokenValidity(): int
    {
        return 60*5;
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
}
