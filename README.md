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
- Import AbstractUserTheme routes into your theme’s
```yaml
# Resources/routes.yml
abstract_user_theme_routes:
    resource: abstract_routes.yml
```


## User events

- `user.signed_up`: After user has been created and EntityManager flushed
- `user.reset_password`: After user has reset its password (in *forgot my password*) and EntityManager flushed
- `user.validated`: After user has confirmed its account and EntityManager flushed
- `user.before_delete`: When user has deleted its account **before** entity is removed and EntityManager flushed (useful to remove references and related entities).
- `user.after_delete`: When user has deleted its account and **after** EntityManager flushed
