<?php
declare(strict_types=1);

namespace Themes\AbstractUserTheme\Event;

final class UserEvents
{
    const USER_SIGNED_UP = 'user.signed_up';
    const USER_RESET_PASSWORD = 'user.reset_password';
    const USER_BEFORE_DELETE = 'user.before_delete';
    const USER_AFTER_DELETE = 'user.after_delete';
}
