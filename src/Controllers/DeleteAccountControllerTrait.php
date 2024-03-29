<?php
declare(strict_types=1);

namespace Themes\AbstractUserTheme\Controllers;

use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;
use Themes\AbstractUserTheme\Event\UserAfterDeleteEvent;
use Themes\AbstractUserTheme\Event\UserBeforeDeleteEvent;
use Twig\Error\RuntimeError;

trait DeleteAccountControllerTrait
{
    use ManualSeoTrait;

    protected function getPageTitle(): string
    {
        return $this->get('translator')->trans('user.delete.page_title');
    }

    /**
     * @param Request $request
     * @param string  $_locale
     *
     * @return Response
     * @throws RuntimeError
     */
    public function deleteAction(Request $request, $_locale = "en")
    {
        $this->denyAccessUnlessGranted(static::$firewallRole);
        $this->prepareThemeAssignation(null, $this->bindLocaleFromRoute($request, $_locale));

        $user = $this->getUser();
        if (null === $user || !$this->isGranted(static::$firewallRole) || !($user instanceof UserInterface)) {
            throw $this->createAccessDeniedException();
        }

        /** @var FormInterface $form */
        $form = $this->createForm(FormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $msg = $this->getTranslator()->trans('user.%name%.deleted_his_account', [
                '%name%' => $user->getUsername(),
            ]);
            $this->dispatchEvent(
                new UserBeforeDeleteEvent($user, $this->em(), $this->get('securityTokenStorage'))
            );
            $this->get('logger')->info($msg);
            if ($user instanceof User) {
                $this->em()->remove($user);
                $this->em()->flush();
            }
            $this->get('securityTokenStorage')->setToken(null);
            $request->getSession()->invalidate();

            $this->dispatchEvent(new UserAfterDeleteEvent(
                $user,
                $this->em(),
                $this->get('securityTokenStorage')
            ));

            return $this->redirect($this->getRedirectedUrl($_locale));
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
        return $this->generateUrl('themeDeleteSuccessUser', ['_locale' => $_locale]);
    }

    /**
     * @return string
     */
    protected function getTemplatePath(): string
    {
        return 'account/delete/delete.html.twig';
    }

    /**
     * @return string
     */
    protected function getConfirmTemplatePath(): string
    {
        return 'account/delete/deleteConfirm.html.twig';
    }
}
