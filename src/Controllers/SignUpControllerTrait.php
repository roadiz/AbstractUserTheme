<?php
declare(strict_types=1);

namespace Themes\AbstractUserTheme\Controllers;

use RZ\Roadiz\Core\Entities\User;
use RZ\Roadiz\OpenId\OAuth2LinkGenerator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Themes\AbstractUserTheme\Event\UserSignedUpEvent;
use Themes\AbstractUserTheme\Form\SignUpType;
use Twig\Error\RuntimeError;

trait SignUpControllerTrait
{
    use ManualSeoTrait;

    protected function getPageTitle(): string
    {
        return $this->get('translator')->trans('user.sign_up.page_title');
    }

    protected function createSignUpForm(Request $request, User $user): FormInterface
    {
        return $this->createForm(SignUpType::class, $user, [
            'em' => $this->get('em'),
            'request' => $request,
            'publicKey' => $this->get('settingsBag')->get('recaptcha_public_key'),
            'privateKey' => $this->get('settingsBag')->get('recaptcha_private_key'),
        ]);
    }

    /**
     * @param Request $request
     * @param string  $_locale
     *
     * @return Response
     * @throws RuntimeError
     */
    public function signUpAction(Request $request, $_locale = "en")
    {
        $this->prepareThemeAssignation(null, $this->bindLocaleFromRoute($request, $_locale));

        if ($this->get('user_theme.allow_sign_up') !== true) {
            throw $this->createNotFoundException('Sign-up is not allowed for this site.');
        }

        if ($request->query->has('_target_path') &&
            1 === preg_match('#^\/#', $request->query->get('_target_path'))) {
            $this->assignation['_target_path'] = $request->query->get('_target_path');
        }

        $user = new User();
        $user->sendCreationConfirmationEmail(false);
        /** @var Form $signUpForm */
        $signUpForm = $this-> createSignUpForm($request, $user);
        $signUpForm->handleRequest($request);

        if ($signUpForm->isSubmitted() && $signUpForm->isValid()) {
            if (null !== $user->getEmail()) {
                $user->setUsername($user->getEmail());
            }
            $this->get('em')->persist($user);
            $this->get('em')->flush($user);

            /** @var EventDispatcherInterface $eventDispatcher */
            $eventDispatcher = $this->get('dispatcher');
            $eventDispatcher->dispatch(new UserSignedUpEvent($user, $this->get('em'), $this->get('securityTokenStorage')));

            /*
             * Add history log
             */
            $msg = $this->getTranslator()->trans('user.%name%.created_an_account', [
                '%name%' => $user->getEmail(),
            ]);
            $this->publishConfirmMessage($request, $msg);
            /*
             * Connect User right after a successful register.
             */
            $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
            $this->get('securityTokenStorage')->setToken($token);

            /*
             * Redirect to subscription choice
             */
            if ($this->needUserEmailValidation()) {
                return $this->redirect($this->getRedirectedUrl($_locale));
            }
            return $this->redirect($this->getAccountRedirectedUrl($_locale));
        }

        /** @var OAuth2LinkGenerator $oauth2LinkGenerator */
        $oauth2LinkGenerator = $this->get(OAuth2LinkGenerator::class);
        if ($oauth2LinkGenerator->isSupported($request)) {
            $this->assignation['openid_button_label'] = $this->get('settingsBag')->get('openid_button_label');
            $this->assignation['openid'] = $oauth2LinkGenerator->generate(
                $request,
                $this->generateUrl('themeLoginCheck', [
                    '_locale' => $_locale
                ], UrlGeneratorInterface::ABSOLUTE_URL)
            );
        }
        $this->assignation['form'] = $signUpForm->createView();

        return $this->render($this->getTemplatePath(), $this->assignation, null, '/');
    }

    /**
     * @return bool
     */
    protected function needUserEmailValidation(): bool
    {
        return true;
    }

    /**
     * @param string $_locale
     *
     * @return string
     */
    protected function getRedirectedUrl(string $_locale): string
    {
        return $this->generateUrl('themeVerifyUser', ['_locale' => $_locale]);
    }

    /**
     * @return string
     */
    protected function getTemplatePath(): string
    {
        return 'account/signup/signup.html.twig';
    }
}
