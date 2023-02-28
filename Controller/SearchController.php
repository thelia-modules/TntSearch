<?php

namespace TntSearch\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\HttpFoundation\Response;

/**
 * @Route("/admin/search", name="front_search_")
 */
class SearchController extends BaseAdminController
{
    /**
     * @Route("", name="front_search_loop", methods="GET")
     *
     * @return Response
     */
    public function searchAdminAction(): Response
    {
        return $this->render('tntSearch/search');
    }
}