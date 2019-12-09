<?php
declare(strict_types=1);

namespace Themes\AbstractUserTheme\Event;

/**
 * Class UserEvents
 *
 * @package Themes\AbstractUserTheme\Event
 * @deprecated
 */
final class UserEvents
{
    /**
     * @deprecated
     */
    const USER_SIGNED_UP = UserSignedUpEvent::class;
    /**
     * @deprecated
     */
    const USER_RESET_PASSWORD = UserResetPasswordEvent::class;
    /**
     * @deprecated
     */
    const USER_VALIDATED = UserValidatedEvent::class;
    /**
     * @deprecated
     */
    const USER_BEFORE_DELETE = UserBeforeDeleteEvent::class;
    /**
     * @deprecated
     */
    const USER_AFTER_DELETE = UserAfterDeleteEvent::class;
}
