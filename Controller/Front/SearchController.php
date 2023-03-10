<?php

namespace TntSearch\Controller\Front;

use Symfony\Component\Routing\Annotation\Route;
use Thelia\Controller\Front\BaseFrontController;
use Thelia\Core\HttpFoundation\JsonResponse;
use Thelia\Core\HttpFoundation\Request;
use TntSearch\Service\Search;

class SearchController extends BaseFrontController
{
    public function search(Search $search, Request $request): JsonResponse
    {


        $resultsByIndex = $search->search(
            $request->get('q'),
            ($index = $request->get('indexes')) ? explode(',', $index) : null,
            $request->getSession()->getLang()->getLocale(),
            $request->get('offset', 0),
            $request->get('limit', 100)
        );

        return new JsonResponse($resultsByIndex);
    }
}