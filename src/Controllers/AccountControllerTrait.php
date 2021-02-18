<?php
declare(strict_types=1);

namespace Themes\AbstractUserTheme\Controllers;

use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;
use Themes\AbstractUserTheme\Form\UpdateUserDetailsType;
use Twig\Error\RuntimeError;

trait AccountControllerTrait
{
    use ManualSeoTrait;

    protected function createUpdateForm(User $user): FormInterface
    {
        return $this->createForm(UpdateUserDetailsType::class, $user, [
            'allowEmailChange' => $this->isAllowingEmailChange()
        ]);
    }

    /**
     * @param Request $request
     * @param string  $_locale
     *
     * @return Response
     * @throws RuntimeError
     */
    public function accountAction(Request $request, $_locale = 'en')
    {
        $this->denyAccessUnlessGranted(static::$firewallRole);
        $this->prepareThemeAssignation(null, $this->bindLocaleFromRoute($request, $_locale));

        $user = $this->getUser();
        if (!($user instanceof UserInterface)) {
            throw $this->createAccessDeniedException();
        }

        if ($user instanceof User) {
            $validationToken = $this->getValidationToken();
            $updateForm = $this->createUpdateForm($user);
            $updateForm->handleRequest($request);

            if ($updateForm->isSubmitted() && $updateForm->isValid()) {
                if (null !== $user->getEmail() && $user->getEmail() !== $user->getUsername()) {
                    /*
                     * Username changed, ask user to validated account again.
                     */
                    $user->setUsername($user->getEmail());
                    if (null !== $validationToken) {
                        $validationToken->reset();
                    }
                }

                $this->get('em')->flush();

                /*
                 * Add history log
                 */
                $msg = $this->getTranslator()->trans('user.%name%.updated_its_account', [
                    '%name%' => $user->getEmail(),
                ]);
                $this->publishConfirmMessage($request, $msg);

                /*
                 * Connect User right after a successful register.
                 */
                $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
                $this->get('securityTokenStorage')->setToken($token);

                return $this->redirect($this->getAccountRedirectedUrl($_locale));
            }

            $this->assignation['validationToken'] = $validationToken;
            $this->assignation['form'] = $updateForm->createView();
        }
        $response = $this->handleCustomAccount($request, $_locale, $user);
        if (null !== $response) {
            return $response;
        }
        $this->assignation['user'] = $user;

        return $this->render($this->getTemplatePath(), $this->assignation, null, '/');
    }

    /**
     * @param Request       $request
     * @param string        $_locale
     * @param UserInterface $user
     * @return Response|null
     */
    protected function handleCustomAccount(Request $request, string $_locale, UserInterface $user): ?Response
    {
        return null;
    }

    /**
     * @return string
     */
    protected function getPageTitle(): string
    {
        return $this->get('translator')->trans('user.account.page_title');
    }

    /**
     * @return string
     */
    protected function getTemplatePath(): string
    {
        return 'account/index.html.twig';
    }

    /**
     * @return bool
     */
    protected function isAllowingEmailChange(): bool
    {
        return true;
    }
}
