<?php
declare(strict_types=1);

namespace Themes\AbstractUserTheme\Services;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Symfony\Component\Translation\Translator;

class AbstractUserThemeProvider implements ServiceProviderInterface
{
    public function register(Container $container)
    {
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

        $container->extend('twig.loaderFileSystem', function (\Twig_Loader_Filesystem $loader) {
            $loader->prependPath(dirname(__DIR__) . '/Resources/views');
            $loader->prependPath(dirname(__DIR__) . '/Resources/views', 'AbstractUserTheme');
            return $loader;
        });
    }
}
