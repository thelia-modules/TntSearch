<?php
/**
 * Created by PhpStorm.
 * User: nicolasbarbey
 * Date: 14/09/2020
 * Time: 09:36
 */

namespace TntSearch\Controller\Front;

use OpenApi\Annotations as OA;
use OpenApi\Controller\Front\BaseFrontOpenApiController;
use OpenApi\Exception\OpenApiException;
use OpenApi\Model\Api\Error;
use TeamTNT\TNTSearch\Exceptions\IndexNotFoundException;
use Thelia\Model\Base\BrandQuery;
use Thelia\Model\Base\CategoryQuery;
use Thelia\Model\Base\ContentQuery;
use Thelia\Model\Base\CustomerQuery;
use Thelia\Model\Base\FolderQuery;
use Thelia\Model\Base\LangQuery;
use Thelia\Model\Base\ProductQuery;
use Thelia\Model\Base\ProductSaleElementsQuery;
use Thelia\Model\Lang;
use Thelia\Model\OrderQuery;
use TntSearch\TntSearch;

class TntSearchFrontController extends BaseFrontOpenApiController
{
    /**
     * @OA\Get(
     *     path="/TntSearch/search",
     *     tags={"tnt", "search"},
     *     summary="Search with TntSearch",
     *     @OA\Parameter(
     *          name="term",
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="locale",
     *          in="query",
     *          description="Current locale by default",
     *          @OA\Schema(
     *              type="string"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="search_for",
     *          in="query",
     *          @OA\Schema(
     *              type="list",
     *              enum={"customer", "order", "pse", "product", "category", "folder", "content", "brand", "all"},
     *              default="all"
     *          )
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *          @OA\JsonContent(
     *                  type="array",
     *                  @OA\Items(
     *                      ref="#/components/schemas/Result"
     *                  )
     *          )
     *     ),
     *     @OA\Response(
     *          response="400",
     *          description="Bad request",
     *          @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *     @OA\Response(
     *          response="500",
     *          description="Error server",
     *          @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     *
     * @return $this|\Thelia\Core\HttpFoundation\Response
     * @throws OpenApiException
     */
    public function frontSearchAction()
    {

        /** @var Lang $lang */
        if (!$lang = LangQuery::create()->filterByLocale($this->getRequest()->get('locale'))->findOne()) {
            $lang = LangQuery::create()->filterByByDefault(1)->findOne();
        }

        $tnt = TntSearch::getTntSearch();
        $tnt->fuzziness = true;

        $modelFactory = $this->getModelFactory();

        $term = $this->getRequest()->get('term');

        $searchable = ['customer', 'order', 'product', 'category', 'folder', 'content', 'brand', 'pse'];

        if ((!$searchFor = $this->getRequest()->get('search_for') ? explode(',', $this->getRequest()->get('search_for')) : null) ||
            in_array('all', $searchFor, true)
        ) {
            $searchFor = $searchable;
        }

        $results = [];

        foreach ($searchFor as $type) {
            if (!in_array($type, $searchable, true)) {
                /** @var Error $error */
                $error = $modelFactory->buildModel('Error', [
                    'title' => 'There is no type named ' . $type
                ]);
                throw new OpenApiException($error, 400);
            }

            $indexName = $type;
            if (in_array($type, ['product', 'category', 'folder', 'content', 'brand'])) {
                $indexName .= '_' . $lang->getLocale();
            }
            $indexName .= '.index';

            do {
                $isNotFound = false;
                try {                    $tnt->selectIndex($indexName);
                    $resultIds = $tnt->search($term);

                }catch (IndexNotFoundException $e){
                    TntSearch::generateMissingIndex($type, $lang->getLocale(), $tnt);
                } catch (\Exception $e) {
                    /** @var Error $error */
                    $error = $modelFactory->buildModel('Error', [
                        'title' => $e->getMessage()
                    ]);
                    throw new OpenApiException($error, 500);
                }
            }while($isNotFound === true);

            $theliaElement = $modelName = null;

            $weight = count($resultIds['ids']);

            foreach ($resultIds['ids'] as $resultId) {
                switch ($type) {
                    case "customer":
                        $theliaElement = CustomerQuery::create()->filterById($resultId)->findOne();
                        $modelName = 'Customer';
                        break;
                    case "order":
                        $theliaElement = OrderQuery::create()->filterById($resultId)->findOne();
                        $modelName = 'Order';
                        break;
                    case "pse":
                        $theliaElement = ProductSaleElementsQuery::create()->filterById($resultId)->findOne();
                        $modelName = 'ProductSaleElement';
                        break;
                    case "product":
                        $theliaElement = ProductQuery::create()->filterById($resultId)->findOne();
                        $modelName = 'Product';
                        break;
                    case "category":
                        $theliaElement = CategoryQuery::create()->filterById($resultId)->findOne();
                        $modelName = 'Category';
                        break;
                    case "folder":
                        $theliaElement = FolderQuery::create()->filterById($resultId)->findOne();
                        $modelName = 'Folder';
                        break;
                    case "content":
                        $theliaElement = ContentQuery::create()->filterById($resultId)->findOne();
                        $modelName = 'Content';
                        break;
                    case "brand":
                        $theliaElement = BrandQuery::create()->filterById($resultId)->findOne();
                        $modelName = 'Brand';
                        break;

                }
                $apiElement = $modelFactory->buildModel($modelName, $theliaElement);
                $results[] = $modelFactory->buildModel('Result', [
                    'object' => $apiElement,
                    'type' => $type,
                    'weight' => $weight--
                ]);
            }
        }
        return $this->jsonResponse($results);
    }
}