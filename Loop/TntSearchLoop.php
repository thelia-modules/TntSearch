<?php
/**
 * Created by PhpStorm.
 * User: nicolasbarbey
 * Date: 29/07/2020
 * Time: 13:35
 */

namespace TntSearch\Loop;


use Propel\Runtime\ActiveQuery\ModelCriteria;
use Thelia\Action\ProductSaleElement;
use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Element\PropelSearchLoopInterface;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Model\Base\ProductQuery;
use Thelia\Model\Base\ProductSaleElementsQuery;
use Thelia\Model\Lang;
use Thelia\Model\LangQuery;
use Thelia\Type\EnumListType;
use Thelia\Type\TypeCollection;
use TntSearch\TntSearch;

/**
 * @method string getSearch()
 * @method array getSearchFor()
 * @method string getLangs()
 */
class TntSearchLoop extends BaseLoop implements PropelSearchLoopInterface
{
    protected function getArgDefinitions()
    {
        return new ArgumentCollection(
            new Argument(
                'search_for',
                new TypeCollection(
                    new EnumListType(
                        array(
                            'products', 'categories', 'brands', 'pse',
                            'folders', 'contents', 'orders', 'customers', '*'
                        )
                    )
                )
            ),
            Argument::createAlphaNumStringTypeArgument('langs'),
            Argument::createAnyTypeArgument('search'),
            Argument::createIntTypeArgument('limit', 100)
        );
    }

    public function buildModelCriteria()
    {
        return null;
    }

    /**
     * @param LoopResult $loopResult
     * @return LoopResult
     * @throws \TeamTNT\TNTSearch\Exceptions\IndexNotFoundException
     */
    public function parseResults(LoopResult $loopResult)
    {
        $tnt = TntSearch::getTntSearch();

        $search = $this->getSearch();

        $langs = LangQuery::create()->filterByActive(1);

        if ($this->getLangs()){
            $langs->filterByLocale($this->getLangs());
        }

        $langs = $langs->find();

        $searchFor = $this->getSearchFor();

        $customers = $orders = $products = $categories = $pse = $folders = $contents = $brands = [];

        $offset = (int) $this->getOffset();
        $limit = (int) $this->getLimit();

        if (in_array("*", $searchFor, true)){
            $searchFor = ['customers', 'orders', 'products', 'categories', 'folders', 'contents', 'brands', 'pse'];
        }

        if (in_array("customers", $searchFor, true)) {
            $customers = $tnt->searchAndPaginate($search, 'customer.index', $offset, $limit);
        }

        if (in_array("orders", $searchFor, true)) {
            $orders = $tnt->searchAndPaginate($search, 'order.index', $offset, $limit);
        }

        if (in_array("pse", $searchFor, true)) {
            $pse = $tnt->searchAndPaginate($search, 'pse.index', $offset, $limit);
        }

        /** @var Lang $lang */
        foreach ($langs as $lang) {
            $tnt = TntSearch::getTntSearch($lang->getLocale());

            if (in_array("products", $searchFor, true)) {
                $products += $tnt->searchAndPaginate($search, 'product_' . $lang->getLocale() . '.index', $offset, $limit);
            }
            if (in_array("categories", $searchFor, true)) {
                $categories += $tnt->searchAndPaginate($search, 'category_' . $lang->getLocale() . '.index', $offset, $limit);
            }
            if (in_array("folders", $searchFor, true)) {
                $folders += $tnt->searchAndPaginate($search, 'folder_' . $lang->getLocale() . '.index', $offset, $limit);
            }
            if (in_array("contents", $searchFor, true)) {
                $contents += $tnt->searchAndPaginate($search, 'content_' . $lang->getLocale() . '.index', $offset, $limit);
            }
            if (in_array("brands", $searchFor, true)) {
                $brands += $tnt->searchAndPaginate($search, 'brand_' . $lang->getLocale() . '.index', $offset, $limit);
            }
        }

        $loopResultRow = new LoopResultRow();

        $loopResultRow
            ->set("PRODUCTS", $products ? implode(',', array_unique($products)) : 0)
            ->set("CATEGORIES", $categories ? implode(',', array_unique($categories)) : 0)
            ->set("BRANDS", $brands ? implode(',', array_unique($brands)) : 0)
            ->set("PSE", $pse ? implode(',', array_unique($pse)) : 0)
            ->set("FOLDER", $folders ? implode(',', array_unique($folders)) : 0)
            ->set("CONTENTS", $contents ? implode(',', array_unique($contents)) : 0)
            ->set("CUSTOMERS", $customers ? implode(',', $customers) : 0)
            ->set("ORDERS", $orders ? implode(',', $orders) : 0);

        $loopResult->addRow($loopResultRow);

        return $loopResult;
    }
}
