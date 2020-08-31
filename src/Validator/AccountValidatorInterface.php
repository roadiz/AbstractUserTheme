<?php
declare(strict_types=1);

namespace Themes\AbstractUserTheme\Validator;

use libphonenumber\PhoneNumber;
use RZ\Roadiz\Core\Entities\User;
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
     * @param User $user
     * @param PhoneNumber $phoneNumber
     * @return ValidationToken
     */
    public function parsePhoneNumber(ValidationToken $validationToken, User $user, PhoneNumber $phoneNumber): ValidationToken;

    public function canByPassValidation(): bool;

    public function canByPassValidationRequest(): bool;

    public function useSmsValidationMethod(): bool;

    public function resetValidationToken(ValidationToken $validationToken): ValidationToken;

    public function sendValidationToken(User $user, ValidationToken $validationToken): void;
}
