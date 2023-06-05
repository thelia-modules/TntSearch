<?php

namespace TntSearch\Service;

use Exception;
use Propel\Runtime\ActiveQuery\Criteria;
use Psr\EventDispatcher\EventDispatcherInterface;
use Thelia\Log\Tlog;
use Thelia\Model\ConfigQuery;
use Thelia\Model\LangQuery;
use TntSearch\Event\ExtendQueryEvent;
use TntSearch\Index\TntSearchIndexInterface;
use TntSearch\Service\Provider\IndexationProvider;
use TntSearch\Service\Provider\TntSearchProvider;
use TntSearch\Service\Support\TntIndexer;

class ItemIndexation
{
    public function __construct(
        protected IndexationProvider $indexationProvider,
        protected TntSearchProvider  $tntSearchProvider,
        protected EventDispatcherInterface $dispatcher
    ) {}

    public function indexOneItemOnIndexes(int $itemId, string $itemIndexType): void
    {
        $index = $this->indexationProvider->getIndex($itemIndexType);

        foreach ($this->buildTNTIndexers($index) as $indexLocale => $tntIndexer) {
            if (!$index->isTranslatable()) {
                $indexName = $index->getIndexName();
                $query = $index->buildSqlQuery($itemId);

                $extendQueryEvent = new ExtendQueryEvent();
                $extendQueryEvent
                    ->setQuery($query)
                    ->setItemId(null)
                    ->setItemType($indexName);

                $this->dispatcher->dispatch($extendQueryEvent, ExtendQueryEvent::EXTEND_QUERY . $indexName);

                $tntIndexer->query($extendQueryEvent->getQuery());
                $tntIndexer->run();

                continue;
            }

            $tntIndexer->query($index->buildSqlQuery($itemId, $indexLocale));
            $tntIndexer->run();
        }
    }

    public function deleteItemOnIndexes(int $itemId, string $itemIndexType): void
    {
        $index = $this->indexationProvider->getIndex($itemIndexType);

        foreach ($this->buildTNTIndexers($index) as $tNTIndexer) {
            $tNTIndexer->delete($itemId);
        }
    }

    /**
     * @return TntIndexer[]
     */
    public function buildTNTIndexers(TntSearchIndexInterface $index): array
    {
        $tntIndexers = [];

        if (!$index->isTranslatable()) {
            $indexFileName = $index->getIndexFileName();

            try {
                $tntState = $this->tntSearchProvider->getTntSearch($index->getTokenizer());
                $tntState->selectIndex($index->getIndexFileName());

                $tntIndexers[] = $tntState->getIndex();

            } catch (Exception $ex) {
                Tlog::getInstance()->addError("Error on $indexFileName index update : " . $ex->getMessage());
            }

            return $tntIndexers;
        }

        $langs = LangQuery::create()
            ->filterByActive(1)
            ->filterById(ConfigQuery::read("indexation_exclude_lang", []), Criteria::NOT_IN)
            ->find();

        foreach ($langs as $lang) {
            $locale = $lang->getLocale();
            $indexFileName = $index->getIndexFileName($locale);

            try {
                $tntState = $this->tntSearchProvider->getTntSearch($index->getTokenizer(), $locale);
                $tntState->selectIndex($indexFileName);

                $tntIndexers[$lang->getLocale()] = $tntState->getIndex();

            } catch (Exception $ex) {
                Tlog::getInstance()->addError("Error on $indexFileName index update : " . $ex->getMessage());
            }
        }

        return $tntIndexers;
    }
}