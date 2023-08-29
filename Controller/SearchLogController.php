<?php

namespace TntSearch\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Thelia\Core\HttpFoundation\Response;
use Thelia\Controller\Admin\BaseAdminController;



/**
 * @Route("/admin/search_log", name="front_search_log_")
 */
class SearchLogController extends BaseAdminController
{
    /**
     * @Route("", name="front_search_log_loop", methods="GET")
     *
     * @return Response
     */
    public function searchLogAdminAction(): Response
    {
        return $this->render('tntSearch/search_log');
    }
}