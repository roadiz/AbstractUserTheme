<?php
declare(strict_types=1);

namespace Themes\AbstractUserTheme\Controllers;

use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Themes\AbstractUserTheme\Form\UpdateUserDetailsType;
use Twig\Error\RuntimeError;

trait AccountControllerTrait
{
    use ManualSeoTrait;

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
        if (!($user instanceof User)) {
            throw $this->createAccessDeniedException();
        }
        $validationToken = $this->getValidationToken();

        /** @var FormInterface $updateForm */
        $updateForm = $this->createForm(UpdateUserDetailsType::class, $user, [
            'em' => $this->get('em'),
            'allowEmailChange' => $this->isAllowingEmailChange()
        ]);
        $updateForm->handleRequest($request);

        if ($updateForm->isSubmitted() && $updateForm->isValid()) {
            if ($user->getEmail() !== $user->getUsername()) {
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

        $this->assignation['user'] = $user;
        $this->assignation['validationToken'] = $validationToken;
        $this->assignation['form'] = $updateForm->createView();

        return $this->render($this->getTemplatePath(), $this->assignation, null, '/');
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
