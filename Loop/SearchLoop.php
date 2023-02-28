<?php

namespace TntSearch\Loop;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Thelia\Core\Template\Element\ArraySearchLoopInterface;
use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
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
class SearchLoop extends BaseLoop implements ArraySearchLoopInterface
{
    protected function getArgDefinitions(): ArgumentCollection
    {
        return new ArgumentCollection(
            Argument::createAnyTypeArgument('search_for', '*'),
            Argument::createAnyTypeArgument('locale'),
            Argument::createBooleanTypeArgument('backend_context'),
            Argument::createAnyTypeArgument('search'),
            Argument::createIntTypeArgument('limit', 100),
            Argument::createIntTypeArgument('offset', 0)
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function buildArray(): array
    {
        $request = $this->getCurrentRequest();
        $session = $request->getSession();

        if (!$search = $this->getSearch()) {
            return [];
        }

        if (!$locale = $this->getLocale()) {
            $locale = $session->getLang()->getLocale();
            if ($this->getBackendContext()) {
                $locale = $session->getAdminEditionLang()->getLocale();
            }
        }

        $offset = $this->getOffset();
        $limit = $this->getLimit();

        /** @var IndexationProvider $indexationProvider */
        $indexationProvider = $this->container->get('tntsearch.indexation.provider');

        $indexes = array_keys($indexationProvider->getIndexes());

        if ('*' !== $this->getSearchFor()) {
            $indexes = array_intersect(explode(',', $this->getSearchFor()), $indexes);
        }

        /** @var Search $searchProvider */
        $searchProvider = $this->container->get('tntsearch.search');

        return $searchProvider->search($search, $indexes, $locale, $offset, $limit);
    }

    /**
     * @param LoopResult $loopResult
     * @return LoopResult
     */
    public function parseResults(LoopResult $loopResult): LoopResult
    {
        foreach ($loopResult->getResultDataCollection() as $searchType => $result) {
            $loopResultRow = new LoopResultRow();

            $loopResultRow->set(strtoupper($searchType), $result ? implode(',', $result) : 0);

            $loopResult->addRow($loopResultRow);
        }

        return $loopResult;
    }
}