<?php

namespace TntSearch\Controller;

use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\HttpFoundation\Response;

class SearchController extends BaseAdminController
{
    /**
     * @return Response
     */
    public function searchAdminAction(): Response
    {
        return $this->render('tntSearch/search');
    }
}