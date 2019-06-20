# Abstract User theme

[![Build Status](https://travis-ci.org/roadiz/AbstractUserTheme.svg?branch=master)](https://travis-ci.org/roadiz/AbstractUserTheme)

**Middleware theme for creating public User accounts and user actions.**

## Features

- Sign-up
- Sign-in
- Account validation by email
- Account validation by SMS (requires MessageBird API access-token)
- Password change
- *Forgot my password* (password change with a token sent to user email)
- GDPR compliance
    - User deletion
    - User data download (JSON serialization)

## Usage

- Register AbstractUserTheme services 

```php
# app/AppKernel.php

/**
 * {@inheritdoc}
 */
public function register(\Pimple\Container $container)
{
    parent::register($container);

    /*
     * Add your own service providers.
     */
    $container->register(new \Themes\AbstractUserTheme\Services\AbstractUserThemeProvider());
}
```

- Extends your own theme with `AbstractUserTheme`

```php
# themes/MyAwesomeTheme/MyAwesomeThemeApp.php
namespace Themes\MyAwesomeTheme;

use Themes\AbstractUserTheme\AbstractUserThemeApp;

/**
 * MyAwesomeThemeApp class
 */
class MyAwesomeThemeApp extends AbstractUserThemeApp {

}
```

- **Do not** directly register `AbstractUserTheme` in your `app/conf/config.yml` file, all services will be wired up using inheritance.
- Add a `additional_scripts` Twig block in your main theme template to be able to inject some JS dependencies.
- Import AbstractUserTheme routes into your theme’s (if you do not want to override them all)
```yaml
# Resources/routes.yml
abstract_user_theme_routes:
    resource: abstract_routes.yml
```

## Override

### Override controller and their methods

All controller are just empty classes using `Traits` so you can easily override them by recreating you route and controller inside your own theme.

```php
<?php
declare(strict_types=1);

namespace Themes\MyAwesomeTheme\Controllers;

use Themes\AbstractUserTheme\Controllers\DeleteAccountControllerTrait;
use Themes\MyAwesomeTheme\MyAwesomeThemeApp;

class DeleteAccountController extends MyAwesomeThemeApp
{
    use DeleteAccountControllerTrait;
}
```

Then you’ll have to override *routing* configuration in order to tell Roadiz to use your custom controller for each route instead of `AbstractUserTheme` ones.

```yaml
# themes/MyAwesomeTheme/Resources/routes.yml
override_user_theme_routes:
    resource: user_routes.yml
```

```yaml
# themes/MyAwesomeTheme/Resources/routing/user_routes.yml
themeAccount:
    path: /{_locale}/account
    defaults:
        _controller: Themes\MyAwesomeTheme\Controllers\AccountController::accountAction
        _locale: en
    requirements:
        _locale: "[a-z]{2}"

```

### Override templates
You can override *Twig* templates too, just create the template file at the same location but inside your own theme. You even can override templates without overriding controller or routes.

If you want to override `account/email/token.html.twig` template, just copy this file as `themes/MyAwesomeTheme/Resources/views/account/email/token.html.twig`. *Twig* file resolver will use this file as first choice when rendering your pages and emails.

## User events

- `user.signed_up`: After user has been created and EntityManager flushed
- `user.reset_password`: After user has reset its password (in *forgot my password*) and EntityManager flushed
- `user.validated`: After user has confirmed its account and EntityManager flushed
- `user.before_delete`: When user has deleted its account **before** entity is removed and EntityManager flushed (useful to remove references and related entities).
- `user.after_delete`: When user has deleted its account and **after** EntityManager flushed

## Mandatory routes

These routes must be declared in order to make Firewall entry work:

- themeAccount
- themeSignInUser
- themeLogout
- themeLoginCheck

These are already defined if you are using *AbstractUserTheme* as is.
