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
            Argument::createAlphaNumStringTypeArgument('search')
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

        if (in_array("*", $searchFor, true)){
            $searchFor = ['customers', 'orders', 'products', 'categories', 'folders', 'contents', 'brands', 'pse'];
        }

        if (in_array("customers", $searchFor, true)) {
            $tnt->selectIndex('customer.index');
            $customers = $tnt->search($search)['ids'];
        }

        if (in_array("orders", $searchFor, true)) {
            $tnt->selectIndex('order.index');
            $orders = $tnt->search($search)['ids'];
        }

        if (in_array("pse", $searchFor, true)) {
            $tnt->selectIndex('pse.index');
            $pse = $tnt->search($search)['ids'];
        }

        /** @var Lang $lang */
        foreach ($langs as $lang) {

            if (in_array("products", $searchFor, true)) {
                $tnt->selectIndex('product_' . $lang->getLocale() . '.index');
                $products += $tnt->search($search)['ids'];
            }
            if (in_array("categories", $searchFor, true)) {
                $tnt->selectIndex('category_' . $lang->getLocale() . '.index');
                $categories += $tnt->search($search)['ids'];
            }
            if (in_array("folders", $searchFor, true)) {
                $tnt->selectIndex('folder_' . $lang->getLocale() . '.index');
                $folders += $tnt->search($search)['ids'];
            }
            if (in_array("contents", $searchFor, true)) {
                $tnt->selectIndex('content_' . $lang->getLocale() . '.index');
                $contents += $tnt->search($search)['ids'];
            }
            if (in_array("brands", $searchFor, true)) {
                $tnt->selectIndex('brand_' . $lang->getLocale() . '.index');
                $brands += $tnt->search($search)['ids'];
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