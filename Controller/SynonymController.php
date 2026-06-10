<?php

namespace TntSearch\Controller;

use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\HttpFoundation\JsonResponse;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Core\Template\ParserContext;
use Thelia\Form\Exception\FormValidationException;
use Thelia\Tools\TokenProvider;
use Thelia\Tools\URL;
use Twig\Environment;
use TntSearch\Form\SynonymForm;
use TntSearch\Model\TntSynonymQuery;
use TntSearch\Service\Synonym;

#[Route('/admin/module/TntSearch', name: 'synonym')]
class SynonymController extends BaseAdminController
{
    public function __construct(private readonly Environment $twig)
    {
    }

    #[Route('/synonym', name: '_list_synonym', methods: ['GET'])]
    public function listAction(Synonym $synonymService): Response
    {
        $synonymGroups = $synonymService->getSynonymGroups();

        $form = $this->createForm(SynonymForm::class);

        return new Response(
            $this->twig->render('@TntSearchModule/backOffice/default-twig/tntSearch/synonym.html.twig', [
                'synonymGroups' => $synonymGroups,
                'form' => $form->getForm()->createView(),
                'success' => null,
                'error' => null,
            ])
        );
    }

    #[Route('/synonym/save', name: '_save_synonym', methods: ['POST'])]
    public function saveAction(
        Synonym $synonymService,
        Request $request,
        ParserContext $parserContext
    ): JsonResponse|RedirectResponse
    {
        $form = $this->createForm(SynonymForm::class);

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
    public function deleteAction(Request $request, TokenProvider $tokenProvider): RedirectResponse
    {
        $tokenProvider->checkToken((string) $request->request->get('_token'));

        $synonymId = $request->request->get('group_id');

        if (!$synonymId) {
            return new RedirectResponse(URL::getInstance()->absoluteUrl('/admin/module/TntSearch/synonym'));
        }

        TntSynonymQuery::create()->filterByGroupId($synonymId)->delete();

        return new RedirectResponse(URL::getInstance()->absoluteUrl('/admin/module/TntSearch/synonym'));
    }
}