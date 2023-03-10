<?php

namespace TntSearch\Controller\Front;
use OpenApi\Annotations as OA;
use OpenApi\Model\Api\ModelFactory;
use OpenApi\Service\OpenApiService;
use Propel\Runtime\ActiveQuery\Criteria;
use Symfony\Component\Routing\Annotation\Route;
use Thelia\Controller\Front\BaseFrontController;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Core\HttpFoundation\Response;
use TntSearch\Service\Search;

/**
 * @Route("/open_api", name="tntsearch_search")
 */
class SearchOpenApiController extends BaseFrontController
{
    /**
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
    public function apiSearch(): Response
    {
        /** @var Search $search */
        $search = $this->getContainer()->get('tntsearch.search');

        /** @var Request $request */
        $request = $this->getRequest();

        /** @var ModelFactory $modelFactory */
        $modelFactory = $this->getContainer()->get('open_api.model.factory');


        $resultsByIndex = $search->search(
            $request->get('q'),
            ($index = $request->get('indexes')) ? explode(',', $index) : null,
            $request->getSession()->getLang()->getLocale(),
            $request->get('offset', 0),
            $request->get('limit', 100)
        );

        $data = [];

        foreach ($resultsByIndex as $index => $ids) {
            if (empty($ids)) {
                continue;
            }

            $model = $search->buildPropelModelFromIndex($index);
            $modelTableMap = $search->buildPropelTableMapFromIndex($index);

            $rows = $model->filterById($ids, Criteria::IN);

            foreach ($ids as $singleId) {
                $givenIdMatched = 'given_id_matched_'.$singleId;
                $rows->withColumn($modelTableMap::COL_ID."='$singleId'", $givenIdMatched);
                $rows->orderBy($givenIdMatched, Criteria::DESC);
            }

            $data[$index] = array_map(function ($row) use ($index, $modelFactory) {
                return $modelFactory->buildModel(ucwords($index), $row);
            }, iterator_to_array($rows));
        }

        return $this->jsonResponse(json_encode($data));
    }
}