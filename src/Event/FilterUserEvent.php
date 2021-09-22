<?php
declare(strict_types=1);

namespace Themes\AbstractUserTheme\Event;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class FilterUserEvent
 *
 * @package Themes\AbstractUserTheme\Event
 */
abstract class FilterUserEvent extends Event
{
    protected EntityManagerInterface $entityManager;
    protected TokenStorageInterface $tokenStorage;
    private UserInterface $user;

    /**
     * @param UserInterface          $user
     * @param EntityManagerInterface $entityManager
     * @param TokenStorageInterface  $tokenStorage
     */
    public function __construct(UserInterface $user, EntityManagerInterface $entityManager, TokenStorageInterface $tokenStorage)
    {
        $this->user = $user;
        $this->entityManager = $entityManager;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @return UserInterface
     */
    public function getUser(): UserInterface
    {
        return $this->user;
    }

    /**
     * @return EntityManagerInterface
     */
    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    /**
     * @return TokenStorageInterface
     */
    public function getTokenStorage(): TokenStorageInterface
    {
        return $this->tokenStorage;
    }
}
