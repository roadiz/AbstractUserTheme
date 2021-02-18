<?php
declare(strict_types=1);

namespace Themes\AbstractUserTheme\Controllers;

use RZ\Roadiz\Core\Entities\User;
use RZ\Roadiz\OpenId\Exception\DiscoveryNotAvailableException;
use RZ\Roadiz\OpenId\OAuth2LinkGenerator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Themes\AbstractUserTheme\Entity\ValidationToken;
use Themes\AbstractUserTheme\Event\UserSignedUpEvent;
use Themes\AbstractUserTheme\Form\SignUpType;
use Themes\AbstractUserTheme\Validator\AccountValidatorInterface;

trait SignUpControllerTrait
{
    use ManualSeoTrait;

    abstract protected function getValidationToken(): ?ValidationToken;

    protected function getPageTitle(): string
    {
        return $this->get('translator')->trans('user.sign_up.page_title');
    }

    protected function createSignUpForm(Request $request, User $user): FormInterface
    {
        return $this->createForm(SignUpType::class, $user, [
            'request' => $request,
            'publicKey' => $this->get('settingsBag')->get('recaptcha_public_key'),
            'privateKey' => $this->get('settingsBag')->get('recaptcha_private_key'),
        ]);
    }

    /**
     * @param Request $request
     * @param string $_locale
     *
     * @return Response
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
        $signUpForm = $this->createSignUpForm($request, $user);
        $signUpForm->handleRequest($request);

        if ($signUpForm->isSubmitted() && $signUpForm->isValid()) {
            if (null !== $user->getEmail()) {
                $user->setUsername($user->getEmail());
            }
            $this->get('em')->persist($user);
            $this->get('em')->flush($user);

            /** @var EventDispatcherInterface $eventDispatcher */
            $eventDispatcher = $this->get('dispatcher');
            $eventDispatcher->dispatch(new UserSignedUpEvent(
                $user,
                $this->get('em'),
                $this->get('securityTokenStorage'),
                $signUpForm
            ));

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

            /** @var AccountValidatorInterface $accountValidator */
            $accountValidator = $this->get(AccountValidatorInterface::class);
            if ($accountValidator->canByPassValidation()) {
                return $this->redirect($this->getAccountRedirectedUrl($_locale));
            }
            if (!$accountValidator->canByPassValidationRequest()) {
                return $this->redirect($this->getRedirectedUrl($_locale));
            }
            /*
             * Validation is need but we can already generate and send validation token
             */
            $validationToken = $this->getValidationToken();
            if (null !== $validationToken && $validationToken->isValidated()) {
                return $this->redirect($this->getAccountRedirectedUrl($_locale));
            } elseif (null === $validationToken) {
                $validationToken = new ValidationToken($user);
            }
            try {
                $accountValidator->sendValidationToken($user, $validationToken);
                $this->get('em')->merge($validationToken);
                $this->get('em')->flush();
                return $this->redirect($this->getRedirectedValidateUrl($_locale));
            } catch (\Exception $e) {
                $accountValidator->resetValidationToken($validationToken);
                $this->get('em')->flush();
                $signUpForm->addError(new FormError($e->getMessage()));
            }
        }

        try {
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
        } catch (DiscoveryNotAvailableException $exception) {
            $this->get('logger')->error($exception->getMessage());
        }

        $this->assignation['form'] = $signUpForm->createView();

        return $this->render($this->getTemplatePath(), $this->assignation, null, '/');
    }

    /**
     * @return bool
     * @deprecated Use AccountValidatorInterface::canByPassValidation
     */
    protected function needUserEmailValidation(): bool
    {
        /** @var AccountValidatorInterface $accountValidator */
        $accountValidator = $this->get(AccountValidatorInterface::class);
        return $accountValidator->canByPassValidation();
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
     * @param string $_locale
     *
     * @return string
     */
    protected function getRedirectedValidateUrl(string $_locale): string
    {
        return $this->generateUrl('themeVerifyUserTokenPage', ['_locale' => $_locale]);
    }

    /**
     * @return string
     */
    protected function getTemplatePath(): string
    {
        return 'account/signup/signup.html.twig';
    }
}
