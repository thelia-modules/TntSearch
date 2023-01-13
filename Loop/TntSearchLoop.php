<?php

namespace TntSearch\Loop;

use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Element\PropelSearchLoopInterface;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Type\EnumListType;
use Thelia\Type\TypeCollection;
use TntSearch\Service\TheliaTntSearch;
use TntSearch\TntSearch;

/**
 * @method string getSearch()
 * @method array getSearchFor()
 * @method string getLocale()
 * @method string getBackendContext()
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
                            'product', 'category', 'brand',
                            'folder', 'content', 'order', 'customer', '*'
                        )
                    )
                )
            ),
            Argument::createAlphaNumStringTypeArgument('locale'),
            Argument::createBooleanTypeArgument('backend_context'),
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
        $request = $this->getCurrentRequest();
        $session = $request->getSession();

        if (!$locale = $this->getLocale()) {
            $locale = $session->getLang()->getLocale();
            if ($this->getBackendContext()) {
                $locale = $session->getAdminEditionLang()->getLocale();
            }
        }

        $tnt = TntSearch::getTntSearch($locale);

        $searchWords = $tnt->sanitizeSearchWords($this->getSearch());

        $searchFors = $this->getSearchFor();

        $customer = $order = $product = $category = $pse = $folder = $content = $brand = [];

        $offset = (int)$this->getOffset();
        $limit = (int)$this->getLimit();

        if (in_array("*", $searchFors, true)) {
            $searchFors = ['customer', 'order', 'product', 'category', 'folder', 'content', 'brand'];
        }

        foreach ($searchFors as $searchType) {
            try {
                if (in_array($searchType, ["customer", "order"])) {
                    $$searchType = $this->handleSearch($tnt, $searchWords, $searchType, $offset, $limit);
                    continue;
                }

                $$searchType += $this->handleSearch($tnt, $searchWords, $searchType, $offset, $limit, $locale);

            } catch (\Exception $exception) {
                //TODO: HANDLE ERROR
            }
        }

        $loopResultRow = new LoopResultRow();

        $loopResultRow
            ->set("PRODUCTS", $product ? implode(',', $product) : 0)
            ->set("PRODUCTS_COUNT", $product ? count($product) : 0)
            ->set("CATEGORIES", $category ? implode(',', $category) : 0)
            ->set("BRANDS", $brand ? implode(',', $brand) : 0)
            ->set("PSE", $pse ? implode(',', $pse) : 0)
            ->set("FOLDER", $folder ? implode(',', $folder) : 0)
            ->set("CONTENTS", $content ? implode(',', $content) : 0)
            ->set("CUSTOMERS", $customer ? implode(',', $customer) : 0)
            ->set("ORDERS", $order ? implode(',', $order) : 0);

        $loopResult->addRow($loopResultRow);

        return $loopResult;
    }

    protected function handleSearch(TheliaTntSearch $tnt, $searchWords, $searchType, $offset, $limit, $locale = null)
    {
        $index = $locale ? $searchType . '_' . $locale . '.index' : $searchType . '.index';

        return $tnt->searchAndPaginate($searchWords, $index, $offset, $limit);
    }
}
