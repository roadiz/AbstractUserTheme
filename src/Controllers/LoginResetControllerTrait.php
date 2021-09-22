<?php
declare(strict_types=1);

namespace Themes\AbstractUserTheme\Controllers;

use RZ\Roadiz\CMS\Forms\LoginResetForm;
use RZ\Roadiz\CMS\Traits\LoginResetTrait;
use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Themes\AbstractUserTheme\Event\UserResetPasswordEvent;

trait LoginResetControllerTrait
{
    use LoginResetTrait;
    use ManualSeoTrait;

    protected function getPageTitle(): string
    {
        return $this->get('translator')->trans('login_reset.page_title');
    }

    /**
     * @param Request $request
     * @param string  $token
     * @param string  $_locale
     *
     * @return Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function resetAction(Request $request, $token, $_locale = "en")
    {
        $this->prepareThemeAssignation(null, $this->bindLocaleFromRoute($request, $_locale));

        /** @var User|null $user */
        $user = $this->getUserByToken($this->em(), $token);

        /** @var FormInterface $form */
        $form = $this->createForm(LoginResetForm::class, null, [
            'token' => $token,
            'confirmationTtl' => User::CONFIRMATION_TTL,
            'entityManager' => $this->em(),
        ]);
        $form->handleRequest($request);

        if (null !== $user && $form->isSubmitted() && $form->isValid()) {
            if ($this->updateUserPassword($form, $user, $this->em())) {
                $this->dispatchEvent(
                    new UserResetPasswordEvent($user, $this->em(), $this->get('securityTokenStorage'))
                );

                return $this->redirect($this->getRedirectedUrl($_locale));
            }
        }
        if (null === $user) {
            $form->addError(new FormError($this->getTranslator()->trans('login_reset.token_is_invalid')));
        }

        $this->assignation['form'] = $form->createView();

        return $this->render($this->getTemplatePath(), $this->assignation, null, '/');
    }

    /**
     * @param Request $request
     * @param string  $_locale
     *
     * @return Response
     */
    public function confirmAction(Request $request, $_locale = "en")
    {
        $this->prepareThemeAssignation(null, $this->bindLocaleFromRoute($request, $_locale));

        return $this->render($this->getConfirmTemplatePath(), $this->assignation, null, '/');
    }

    /**
     * @param string $_locale
     *
     * @return string
     */
    protected function getRedirectedUrl(string $_locale): string
    {
        return $this->generateUrl('themeConfirmPassword', ['_locale' => $_locale]);
    }

    /**
     * @return string
     */
    protected function getTemplatePath(): string
    {
        return 'account/login/reset.html.twig';
    }

    /**
     * @return string
     */
    protected function getConfirmTemplatePath(): string
    {
        return 'account/login/resetConfirm.html.twig';
    }
}
