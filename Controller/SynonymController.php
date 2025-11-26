<?php

namespace TntSearch\Controller;

use Exception;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\HttpFoundation\JsonResponse;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Core\HttpFoundation\Response;
use Thelia\Core\Template\ParserContext;
use Thelia\Form\Exception\FormValidationException;
use Thelia\Tools\URL;
use TntSearch\Form\SynonymForm;
use TntSearch\Model\TntSynonymQuery;
use TntSearch\Service\Synonym;

#[Route('/admin/module/TntSearch', name: 'synonym')]
class SynonymController extends BaseAdminController
{
    #[Route('/synonym', name: '_list_synonym', methods: ['GET'])]
    public function listAction(Synonym $synonymService): Response
    {
        $synonymGroups = $synonymService->getSynonymGroups();

        return $this->render('tntSearch/synonym', [
            'synonymGroups' => $synonymGroups,
            'success' => null,
            'error' => null
        ]);
    }

    #[Route('/synonym/save', name: '_save_synonym', methods: ['POST'])]
    public function saveAction(
        Synonym $synonymService,
        Request $request,
        ParserContext $parserContext
    ): JsonResponse|RedirectResponse
    {
        $form = $this->createForm(SynonymForm::class, FormType::class, [], ['csrf_protection' => false]);

        try {
            $data = $this->validateForm($form)->getData();
            $synonymService->saveTerms(terms: $data['terms'], groupId: $data['group_id']);

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => true]);
            }
            return $this->generateSuccessRedirect($form);

        } catch (FormValidationException $e) {
            $error_message = $this->createStandardFormValidationErrorMessage($e);
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['success' => false, 'error' => $error_message], Response::HTTP_BAD_REQUEST);
        }

        $form->setErrorMessage($error_message);

        $parserContext
            ->addForm($form)
            ->setGeneralError($error_message);

        return $this->generateErrorRedirect($form);
    }

    #[Route('/synonym/delete', name: '_delete_synonym', methods: ['POST'])]
    public function deleteAction(Request $request): RedirectResponse
    {
        $synonymId = $request->request->get('group_id');

        if (!$synonymId) {
            return new RedirectResponse(URL::getInstance()->absoluteUrl('/admin/module/TntSearch/synonym'));
        }

        TntSynonymQuery::create()->filterByGroupId($synonymId)->delete();

        return new RedirectResponse(URL::getInstance()->absoluteUrl('/admin/module/TntSearch/synonym'));
    }
}