<?php
declare(strict_types=1);

namespace Themes\AbstractUserTheme\Services;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Symfony\Component\Translation\Translator;
use Twig\Loader\FilesystemLoader;

class AbstractUserThemeProvider implements ServiceProviderInterface
{
    /**
     * @param Container $container
     * @return void
     */
    public function register(Container $container)
    {
        $container['user_theme.allow_sign_up'] = function () {
            return false;
        };
        /*
         * Every path to parse to find doctrine entities
         */
        $container->extend('doctrine.entities_paths', function (array $paths) {
            $paths[] = dirname(__DIR__) . '/Entity';
            return $paths;
        });

        $container->extend('translator', function (Translator $translator) {
            $translator->addResource(
                'xlf',
                dirname(__DIR__) . '/Resources/translations/messages.en.xlf',
                'en'
            );
            $translator->addResource(
                'xlf',
                dirname(__DIR__) . '/Resources/translations/messages.fr.xlf',
                'fr'
            );
            return $translator;
        });

        $container->extend('twig.loaderFileSystem', function (FilesystemLoader $loader) {
            $loader->prependPath(dirname(__DIR__) . '/Resources/views');
            $loader->prependPath(dirname(__DIR__) . '/Resources/views', 'AbstractUserTheme');
            return $loader;
        });
    }
}
