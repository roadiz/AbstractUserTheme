<?php
declare(strict_types=1);

namespace Themes\AbstractUserTheme;

use Pimple\Container;
use RZ\Roadiz\CMS\Controllers\FrontendController;
use RZ\Roadiz\Core\Entities\User;
use RZ\Roadiz\Utils\Security\FirewallEntry;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Themes\AbstractUserTheme\Entity\ValidationToken;
use Themes\AbstractUserTheme\Security\Authentication\AuthenticationSuccessHandler;

/**
 * AbstractUserThemeApp class
 */
class AbstractUserThemeApp extends FrontendController
{
    const VERSION = '1.1.2';

    protected static $themeName = 'Abstract User theme';
    protected static $themeAuthor = 'REZO ZERO';
    protected static $themeCopyright = 'REZO ZERO';
    protected static $themeDir = 'AbstractUserTheme';
    protected static $backendTheme = false;
    public static $priority = 5;

    protected static $firewallRoot = '/account';
    protected static $firewallRole = 'ROLE_USER';

    /**
     * {@inheritdoc}
     */
    public static function addDefaultFirewallEntry(Container $container)
    {
        $firewallBasePattern = '^(\/[a-z]{2})?' . static::$firewallRoot;
        $firewallBasePath = 'themeAccount';
        $firewallLogin = 'themeSignInUser';
        $firewallLogout =  'themeLogout';
        $firewallLoginCheck = 'themeLoginCheck';
        $firewallBaseRole = [static::$firewallRole];

        $firewallEntry = new FirewallEntry(
            $container,
            $firewallBasePattern,
            $firewallBasePath,
            $firewallLogin,
            $firewallLogout,
            $firewallLoginCheck,
            $firewallBaseRole,
            AuthenticationSuccessHandler::class
        );
        $firewallEntry
            ->withAnonymousAuthenticationListener()
            ->withSwitchUserListener()
            ->withReferer();

        /*
         * Finally add this long long configuration to the Roadiz
         * firewall map.
         */
        $container['firewallMap']->add(
            $firewallEntry->getRequestMatcher(),
            $firewallEntry->getListeners(),
            $firewallEntry->getExceptionListener()
        );

        parent::addDefaultFirewallEntry($container);
    }

    /**
     * @return FileLocator
     */
    public static function getFileLocator()
    {
        $abstractResourcesFolder = dirname(__FILE__).'/Resources';
        $resourcesFolder = static::getResourcesFolder();
        return new FileLocator([
            $abstractResourcesFolder,
            $abstractResourcesFolder . '/routing',
            $abstractResourcesFolder . '/config',
            $resourcesFolder,
            $resourcesFolder . '/routing',
            $resourcesFolder . '/config',
        ]);
    }


    /**
     * @return ValidationToken|null
     */
    protected function getValidationToken(): ?ValidationToken
    {
        $user = $this->getUser();
        if (null !== $user && $user instanceof User) {
            return $this->get('em')->getRepository(ValidationToken::class)->findOneByUser($user);
        }
        return null;
    }

    /**
     * @param string $_locale
     *
     * @return RedirectResponse
     */
    protected function getAccountRedirectedUrl(string $_locale): string
    {
        return $this->generateUrl('themeAccount', ['_locale' => $_locale]);
    }
}