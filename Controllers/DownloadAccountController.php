<?php
declare(strict_types=1);

namespace Themes\AbstractUserTheme\Controllers;

use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use RZ\Roadiz\Core\Entities\Log;
use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Themes\AbstractUserTheme\AbstractUserThemeApp;

class DownloadAccountController extends AbstractUserThemeApp
{
    public function downloadAction(Request $request, $_locale = 'en')
    {
        $this->validateAccessForRole(static::$firewallRole);
        $this->prepareThemeAssignation(null, $this->bindLocaleFromRoute($request, $_locale));

        $this->assignation['pageMeta'] = [
            'title' => $this->get('translator')->trans('user_data_download.page_title') . ' — ' . $this->get('settingsBag')->get('site_name'),
        ];

        $user = $this->getUser();
        if (!($user instanceof User)) {
            throw $this->createAccessDeniedException();
        }
        $validationToken = $this->getValidationToken();

        /** @var Form $userForm */
        $userForm = $this->createNamedFormBuilder('download_user')->getForm();
        $userForm->handleRequest($request);
        if ($userForm->isValid()) {
            /** @var Serializer $serializer */
            $serializer = $this->get('serializer');
            $response = $this->getDownloadResponse(
                $serializer->serialize($validationToken, 'json', SerializationContext::create()->setGroups(['validationToken', 'user'])),
                $user->getEmail() . '.json'
            );
            $response->prepare($request);
            return $response;
        }
        $this->assignation['user_form'] = $userForm->createView();


        /** @var Form $logsForm */
        $logsForm = $this->createNamedFormBuilder('download_logs')->getForm();
        $logsForm->handleRequest($request);
        if ($logsForm->isValid()) {
            $logs = $this->get('em')->getRepository(Log::class)->findByUser($user);
            /** @var Serializer $serializer */
            $serializer = $this->get('serializer');
            $response = $this->getDownloadResponse(
                $serializer->serialize($logs, 'json', SerializationContext::create()->setGroups(['log', 'log_user', 'log_sources']), 'array<'.Log::class.'>'),
                $user->getEmail() . '_logs.json'
            );
            $response->prepare($request);
            return $response;
        }
        $this->assignation['logs_form'] = $logsForm->createView();

        return $this->render($this->getTemplatePath(), $this->assignation);
    }

    /**
     * @return string
     */
    protected function getTemplatePath(): string
    {
        return 'account/download/index.html.twig';
    }

    /**
     * @param string $data
     * @param string $filenane
     *
     * @return Response
     */
    protected function getDownloadResponse(string $data, string $filenane): Response
    {
        $response = new Response(
            $data,
            Response::HTTP_OK,
            []
        );
        /*$response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $filenane
            )
        );*/
        return $response;
    }
}
