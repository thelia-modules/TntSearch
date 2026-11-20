<?php

declare(strict_types=1);

namespace TntSearch\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Log\Tlog;
use Thelia\Tools\TokenProvider;
use Thelia\Tools\URL;
use TntSearch\Search\CatalogueReindexer;
use TntSearch\Service\Provider\IndexationProvider;

#[Route('/admin/module/TntSearch', name: 'tntsearch_indexation')]
class IndexationController extends BaseAdminController
{
    #[Route('/generate-indexes', name: '_generation', methods: ['POST'])]
    public function generateIndexesAction(Request $request, TokenProvider $tokenProvider, IndexationProvider $indexationProvider, CatalogueReindexer $catalogueReindexer): Response
    {
        $tokenProvider->checkToken((string) $request->request->get('_token'));

        ini_set('max_execution_time', 3600);

        try {
            $indexationProvider->indexAll();
            $catalogueReindexer->rebuild();
        } catch (\Exception $exception) {
            Tlog::getInstance()->addError('TntSearch: the indexes could not be rebuilt from the back-office: '.$exception->getMessage());

            return $this->generateRedirect(URL::getInstance()->absoluteUrl('/admin/module/TntSearch', ['error' => 1]));
        }

        return $this->generateRedirect(URL::getInstance()->absoluteUrl('/admin/module/TntSearch', ['success' => true]));
    }
}
