<?php
declare(strict_types=1);

namespace Themes\AbstractUserTheme;

use Pimple\Container;
use RZ\Roadiz\CMS\Controllers\FrontendController;
use RZ\Roadiz\Core\Entities\User;
use RZ\Roadiz\Utils\Security\FirewallEntry;
use Symfony\Component\Config\FileLocator;
use Themes\AbstractUserTheme\Entity\ValidationToken;
use Themes\AbstractUserTheme\Security\Authentication\AuthenticationSuccessHandler;

class AbstractUserThemeApp extends FrontendController
{
    protected static $themeName = 'Abstract User theme';
    protected static $themeAuthor = 'REZO ZERO';
    protected static $themeCopyright = 'REZO ZERO';
    protected static $themeDir = 'AbstractUserTheme';
    protected static $backendTheme = false;
    public static $priority = 5;

    /**
     * @var string
     */
    protected static $firewallRoot = '/account';
    /**
     * @var string
     */
    protected static $firewallBasePath = 'themeAccount';
    /**
     * @var string
     */
    protected static $firewallLogin = 'themeSignInUser';
    /**
     * @var string
     */
    protected static $firewallLogout =  'themeLogout';
    /**
     * @var string
     */
    protected static $firewallLoginCheck = 'themeLoginCheck';
    /**
     * @var string
     */
    protected static $firewallRole = 'ROLE_USER';

    /**
     * @param Container $container
     * @return void
     */
    public static function addDefaultFirewallEntry(Container $container)
    {
        $firewallBasePattern = '^(\/[a-z]{2})?' . static::$firewallRoot;

        $firewallEntry = new FirewallEntry(
            $container,
            $firewallBasePattern,
            static::$firewallBasePath,
            static::$firewallLogin,
            static::$firewallLogout,
            static::$firewallLoginCheck,
            [static::$firewallRole],
            AuthenticationSuccessHandler::class
        );
        $firewallEntry
            ->withAnonymousAuthenticationListener()
            ->withOAuth2AuthenticationListener()
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
     * @throws \ReflectionException
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
     * @return string
     */
    protected function getAccountRedirectedUrl(string $_locale): string
    {
        return $this->generateUrl('themeAccount', ['_locale' => $_locale]);
    }
}
