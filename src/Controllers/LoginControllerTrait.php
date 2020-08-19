<?php
declare(strict_types=1);

namespace Themes\AbstractUserTheme\Controllers;

use RZ\Roadiz\OpenId\Exception\DiscoveryNotAvailableException;
use RZ\Roadiz\OpenId\OAuth2LinkGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Twig\Error\RuntimeError;

trait LoginControllerTrait
{
    use ManualSeoTrait;

    /**
     * @param Request $request
     * @param string  $_locale
     *
     * @return Response
     * @throws RuntimeError
     */
    public function loginAction(Request $request, $_locale = 'en')
    {
        $this->prepareThemeAssignation(null, $this->bindLocaleFromRoute($request, $_locale));
        /** @var AuthenticationUtils $helper */
        $helper = $this->get('securityAuthenticationUtils');
        $this->assignation['last_username'] = $helper->getLastUsername();
        $this->assignation['error'] = $helper->getLastAuthenticationError();

        try {
            /** @var OAuth2LinkGenerator $oauth2LinkGenerator */
            $oauth2LinkGenerator = $this->get(OAuth2LinkGenerator::class);
            if ($oauth2LinkGenerator->isSupported($request)) {
                $this->assignation['openid_button_label'] = $this->get('settingsBag')->get('openid_button_label');
                $state = [];
                $redirectParams = [
                    '_locale' => $_locale,
                ];
                if ($request->get('_target_path', false) && substr($request->get('_target_path'), 0, 1) === '/') {
                    $state['_target_path'] = trim((string) $request->get('_target_path'));
                }
                $this->assignation['openid'] = $oauth2LinkGenerator->generate(
                    $request,
                    $this->generateUrl('themeLoginCheck', $redirectParams, UrlGeneratorInterface::ABSOLUTE_URL),
                    $state
                );
            }
        } catch (DiscoveryNotAvailableException $exception) {
            $this->get('logger')->error($exception->getMessage());
        }

        if ($this->get('user_theme.allow_sign_up') === true) {
            $this->assignation['allow_sign_up'] = true;
        }

        return $this->render($this->getTemplatePath(), $this->assignation, null, '/');
    }

    /**
     * @return string
     */
    protected function getPageTitle(): string
    {
        return $this->get('translator')->trans('user.sign_in.page_title');
    }

    /**
     * @return string
     */
    protected function getTemplatePath(): string
    {
        return 'account/login/login.html.twig';
    }
}
