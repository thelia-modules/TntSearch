<?php

namespace TntSearch\Loop;

use Propel\Runtime\ActiveQuery\Criteria;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Thelia\Core\Template\Element\ArraySearchLoopInterface;
use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use TntSearch\Model\Map\TntSearchLogTableMap;
use TntSearch\Model\TntSearchLogQuery;
use TntSearch\Service\Provider\IndexationProvider;
use TntSearch\Service\Search;

/**
 * @method string getSearch()
 * @method string getSearchFor()
 * @method string getLocale()
 * @method string getBackendContext()
 * @method string getLimit()
 * @method string getOffset()
 */
class SearchLogLoop extends BaseLoop implements ArraySearchLoopInterface
{
    protected function getArgDefinitions(): ArgumentCollection
    {
        return new ArgumentCollection(
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function buildArray(): array
    {
        return TntSearchLogQuery::create()
            ->orderBy(TntSearchLogTableMap::COL_NUM_HITS,Criteria::DESC)
            ->find()
            ->toArray();
    }

    /**
     * @param LoopResult $loopResult
     * @return LoopResult
     */
    public function parseResults(LoopResult $loopResult): LoopResult
    {
        foreach ($loopResult->getResultDataCollection() as $searchType => $result) {
            $loopResultRow = new LoopResultRow();
            $loopResultRow
                ->set('SEARCHWORDS', $result['SearchWords'])
                ->set('INDEX', $result['Index'])
                ->set('LOCALE', $result['Locale'])
                ->set('NUMHITS', $result['NumHits']);
            $loopResult->addRow($loopResultRow);
        }

        return $loopResult;
    }
}