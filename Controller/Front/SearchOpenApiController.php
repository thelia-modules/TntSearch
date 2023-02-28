<?php

namespace TntSearch\Controller\Front;

use OpenApi\Annotations as OA;
use OpenApi\Model\Api\ModelFactory;
use OpenApi\Service\OpenApiService;
use Propel\Runtime\ActiveQuery\Criteria;
use Symfony\Component\Routing\Annotation\Route;
use Thelia\Controller\Front\BaseFrontController;
use Thelia\Core\HttpFoundation\Request;
use TntSearch\Service\Search;

/**
 * @Route("/open_api/tnt-search", name="tntsearch_search")
 */
class SearchOpenApiController extends BaseFrontController
{
    /**
     * @Route("", name="indexes_search", methods="GET")
     *
     * @OA\Get(
     *     path="/tnt-search",
     *     tags={"TNTSearch", "Search"},
     *     summary="Search products or category or folder or customer etc...",
     *     @OA\Parameter(
     *          name="q",
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="indexes",
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="limit",
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="offset",
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *          @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *          response="400",
     *          description="Bad request",
     *          @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
    public function apiSearch(Search $search, Request $request, ModelFactory $modelFactory)
    {
        $resultsByIndex = $search->search(
            $request->get('q'),
            ($index = $request->get('indexes')) ? explode(',', $index) : null,
            $request->getSession()->getLang()->getLocale(),
            $request->get('offset', 0),
            $request->get('limit', 100),
        );

        $data = [];

        foreach ($resultsByIndex as $index => $ids) {
            if (empty($ids)) {
                continue;
            }

            $model = $search->buildPropelModelFromIndex($index);
            $rows = $model->filterById($ids, Criteria::IN);

            $data[$index] = array_map(function ($row) use ($index, $modelFactory) {
                return $modelFactory->buildModel(ucwords($index), $row);
            }, iterator_to_array($rows));
        }

        return OpenApiService::jsonResponse($data);
    }
}