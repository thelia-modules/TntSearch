<?php

namespace TntSearch\Loop;

use Thelia\Core\Template\Element\ArraySearchLoopInterface;
use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Type\EnumListType;
use Thelia\Type\TypeCollection;
use TntSearch\Service\Search;

/**
 * @method string getSearch()
 * @method array getSearchFor()
 * @method string getLocale()
 * @method string getBackendContext()
 */
class SearchLoop extends BaseLoop implements ArraySearchLoopInterface
{
    protected function getArgDefinitions(): ArgumentCollection
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

    public function buildArray(): array
    {
        $request = $this->getCurrentRequest();
        $session = $request->getSession();

        if (!$locale = $this->getLocale()) {
            $locale = $session->getLang()->getLocale();
            if ($this->getBackendContext()) {
                $locale = $session->getAdminEditionLang()->getLocale();
            }
        }

        $indexes = $this->getSearchFor();

        $offset = $this->getOffset();
        $limit = $this->getLimit();

        if (in_array("*", $indexes, true)) {
            $indexes = ['customer', 'order', 'product', 'category', 'folder', 'content', 'brand'];
        }

        /** @var Search $searchProvider */
        $searchProvider = $this->container->get('tntsearch.search');

        return $searchProvider->search($this->getSearch(), $indexes, $offset, $limit, $locale);
    }

    /**
     * @param LoopResult $loopResult
     * @return LoopResult
     */
    public function parseResults(LoopResult $loopResult): LoopResult
    {
        foreach ($loopResult->getResultDataCollection() as $searchType => $result) {
            $loopResultRow = new LoopResultRow();

            if ($searchType === 'product') {
                $loopResultRow->set("PRODUCTS_COUNT", count($result));
            }

            $loopResultRow->set(strtoupper($searchType), $result ? implode(',', $result) : 0);

            $loopResult->addRow($loopResultRow);
        }

        return $loopResult;
    }
}