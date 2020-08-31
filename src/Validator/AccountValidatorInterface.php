<?php
declare(strict_types=1);

namespace Themes\AbstractUserTheme\Validator;

use libphonenumber\PhoneNumber;
use Symfony\Component\Security\Core\User\UserInterface;
use Themes\AbstractUserTheme\Entity\ValidationToken;

interface AccountValidatorInterface
{
    /**
     * @param ValidationToken|null $validationToken
     * @param string $token
     * @return ValidationToken
     * @throws InvalidValidationTokenException
     */
    public function validate(?ValidationToken $validationToken, string $token): ValidationToken;

    /**
     * @param ValidationToken $validationToken
     * @param PhoneNumber $phoneNumber
     * @return string
     */
    public function parsePhoneNumber(
        ValidationToken $validationToken,
        PhoneNumber $phoneNumber
    ): string;

    public function canByPassValidation(): bool;

    public function canByPassValidationRequest(): bool;

    public function useSmsValidationMethod(): bool;

    public function resetValidationToken(ValidationToken $validationToken): ValidationToken;

    public function sendValidationToken(UserInterface $user, ValidationToken $validationToken): void;
}
