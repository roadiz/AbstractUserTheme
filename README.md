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
