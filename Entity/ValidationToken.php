<?php
declare(strict_types=1);

namespace Themes\AbstractUserTheme\Entity;

use Doctrine\ORM\Mapping as ORM;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;
use RZ\Roadiz\Core\Entities\User;

/**
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\EntityRepository")
 * @ORM\Table(name="user_validation_token")
 */
class ValidationToken extends AbstractEntity
{
    /**
     * @var User|null
     * @ORM\OneToOne(targetEntity="RZ\Roadiz\Core\Entities\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $user = null;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true, name="token")
     */
    protected $validationToken = null;

    /**
     * @var \DateTime|null
     * @ORM\Column(type="datetime", nullable=true, name="expires_at")
     */
    protected $validationTokenExpiresAt = null;

    /**
     * @var boolean
     * @ORM\Column(type="boolean")
     */
    protected $validated = false;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=5, name="country_code", nullable=true)
     */
    protected $countryCode = null;

    /**
     * ValidationToken constructor.
     *
     * @param User|null $user
     */
    public function __construct(?User $user)
    {
        $this->user = $user;
    }

    /**
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param User|null $user
     *
     * @return ValidationToken
     */
    public function setUser(?User $user): ValidationToken
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getValidationToken(): ?string
    {
        return $this->validationToken;
    }

    /**
     * @param string|null $validationToken
     *
     * @return ValidationToken
     */
    public function setValidationToken(?string $validationToken): ValidationToken
    {
        $this->validationToken = $validationToken;

        return $this;
    }

    /**
     * @return bool
     */
    public function isValidated(): bool
    {
        return $this->validated;
    }

    /**
     * @param bool $validated
     *
     * @return ValidationToken
     */
    public function setValidated(bool $validated): ValidationToken
    {
        $this->validated = $validated;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    /**
     * @param string|null $countryCode
     *
     * @return ValidationToken
     */
    public function setCountryCode(?string $countryCode): ValidationToken
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getValidationTokenExpiresAt(): ?\DateTime
    {
        return $this->validationTokenExpiresAt;
    }

    /**
     * @param \DateTime|null $validationTokenExpiresAt
     *
     * @return ValidationToken
     */
    public function setValidationTokenExpiresAt(?\DateTime $validationTokenExpiresAt): ValidationToken
    {
        $this->validationTokenExpiresAt = $validationTokenExpiresAt;

        return $this;
    }

    /**
     * @return bool
     */
    public function isValidationTokenValid(): bool
    {
        $now = new \DateTime();
        return null !== $this->validationToken && $this->validationTokenExpiresAt > $now;
    }
}
