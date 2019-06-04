<?php
declare(strict_types=1);

namespace Themes\AbstractUserTheme\Controllers;

use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Themes\AbstractUserTheme\AbstractUserThemeApp;
use Themes\AbstractUserTheme\Event\FilterUserEvent;
use Themes\AbstractUserTheme\Event\UserEvents;
use Themes\AbstractUserTheme\Form\SignUpType;

class SignUpController extends AbstractUserThemeApp
{
    /**
     * @param Request $request
     * @param string  $_locale
     *
     * @return Response
     * @throws \Twig_Error_Runtime
     */
    public function signUpAction(Request $request, $_locale = "en")
    {
        $this->prepareThemeAssignation(null, $this->bindLocaleFromRoute($request, $_locale));

        $helper = $this->get('securityAuthenticationUtils');
        $this->assignation['last_username'] = $helper->getLastUsername();
        $this->assignation['error'] = $helper->getLastAuthenticationError();

        $this->assignation['pageMeta'] = [
            'title' => $this->get('translator')->trans('user.sign_up.page_title') . ' — ' . $this->get('settingsBag')->get('site_name'),
        ];

        if ($request->query->has('_target_path') &&
            1 === preg_match('#^\/#', $request->query->get('_target_path'))) {
            $this->assignation['_target_path'] = $request->query->get('_target_path');
        }

        $user = new User();
        $user->sendCreationConfirmationEmail(true);
        /** @var Form $signUpForm */
        $signUpForm = $this->createForm(SignUpType::class, $user, [
            'em' => $this->get('em'),
            'request' => $request,
            'publicKey' => $this->get('settingsBag')->get('recaptcha_public_key'),
            'privateKey' => $this->get('settingsBag')->get('recaptcha_private_key'),
        ]);
        $signUpForm->handleRequest($request);

        if ($signUpForm->isValid()) {
            $user->setUsername($user->getEmail());
            $this->get('em')->persist($user);
            $this->get('em')->flush($user);

            $event = new FilterUserEvent($user, $this->get('em'), $this->get('securityTokenStorage'));
            /** @var EventDispatcherInterface $eventDispatcher */
            $eventDispatcher = $this->get('dispatcher');
            $eventDispatcher->dispatch(UserEvents::USER_SIGNED_UP, $event);

            /*
             * Add history log
             */
            $msg = $this->getTranslator()->trans('user.%name%.created_an_account', [
                '%name%' => $user->getEmail(),
            ]);
            $this->get('logger')->info($msg);

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

        $this->assignation['form'] = $signUpForm->createView();

        return $this->render($this->getTemplatePath(), $this->assignation);
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
     * @return RedirectResponse
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
