<?php
declare(strict_types=1);

namespace Themes\AbstractUserTheme\Event;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class UserSignedUpEvent extends FilterUserEvent
{
    /**
     * @var FormInterface|null
     */
    protected $form;

    public function __construct(
        UserInterface $user,
        EntityManagerInterface $entityManager,
        TokenStorageInterface $tokenStorage,
        ?FormInterface $form = null
    ) {
        parent::__construct($user, $entityManager, $tokenStorage);
        $this->form = $form;
    }

    /**
     * @return FormInterface|null
     */
    public function getForm(): ?FormInterface
    {
        return $this->form;
    }
}
