<?php

namespace TntSearch\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Thelia\Controller\Admin\BaseAdminController;


/**
 * @Route("/admin/search", name="front_search_")
 */
class SearchController extends BaseAdminController
{
    /**
     * @Route("", name="front_search_loop", methods="GET")
     *
     */
    public function searchAdminAction(): Response
    {
        return $this->render('tntSearch/search');
    }
}