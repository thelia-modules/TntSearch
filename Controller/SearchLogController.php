<?php

namespace TntSearch\Controller;

use Propel\Runtime\ActiveQuery\Criteria;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Thelia\Controller\Admin\BaseAdminController;
use Twig\Environment;
use TntSearch\Model\Map\TntSearchLogTableMap;
use TntSearch\Model\TntSearchLogQuery;

#[Route("/admin/search_log", name: "front_search_log_")]
class SearchLogController extends BaseAdminController
{
    public function __construct(private readonly Environment $twig)
    {
    }

    #[Route("", name: "front_search_log_loop", methods: ["GET"])]
    public function searchLogAdminAction(): Response
    {
        $searchLogs = TntSearchLogQuery::create()
            ->orderBy(TntSearchLogTableMap::COL_NUM_HITS, Criteria::DESC)
            ->find()
            ->toArray();

        return new Response(
            $this->twig->render(
                '@TntSearchModule/backOffice/default-twig/tntSearch/search_log.html.twig',
                ['searchLogs' => $searchLogs]
            )
        );
    }
}
