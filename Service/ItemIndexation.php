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
use TntSearch\Service\Support\TntGeoIndexer;
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
            $indexName = $index->getIndexName();

            if (!$index->isTranslatable()) {
                $query = $index->buildSqlQuery($itemId);
            } else {
                $query = $index->buildSqlQuery($itemId, $indexLocale);
            }
            if ($index->isGeoIndexable() && $indexLocale === "geo") {
                $query = $index->buildSqlGeoQuery($itemId);
            }

            if ($tntIndexer instanceof TntIndexer){
                $tntIndexer->setIndexObject($index);
            }

            $extendQueryEvent = new ExtendQueryEvent();
            $extendQueryEvent
                ->setQuery($query)
                ->setItemType($indexName)
                ->setItemId($itemId);

            $this->dispatcher->dispatch($extendQueryEvent, ExtendQueryEvent::EXTEND_QUERY . $indexName);

            $tntIndexer->query($extendQueryEvent->getQuery());
            $tntIndexer->run();
        }
    }

    public function deleteItemOnIndexes(int $itemId, string $itemIndexType): void
    {
        $index = $this->indexationProvider->getIndex($itemIndexType);

        foreach ($this->buildTNTIndexers($index) as $TNTIndexer) {
            if($TNTIndexer instanceof TNTIndexer){
                $TNTIndexer->delete($itemId);
                continue;
            }
            $this->deleteGeoIndex(documentId: $itemId,tntIndexer: $TNTIndexer);
        }
    }

    /**
     * @return TntIndexer[]
     */
    public function buildTNTIndexers(TntSearchIndexInterface $index): array
    {
        $tntIndexers = [];

        if ($index->isGeoIndexable()) {
            $indexFileName = $index->getIndexFileName(null, true);
            try {
                $tntState = $this->tntSearchProvider->getGeoTntSearch($indexFileName);
                $tntIndexers['geo'] = $tntState->getIndex();

            } catch (Exception $ex) {
                Tlog::getInstance()->addError("Error on $indexFileName index update : " . $ex->getMessage());
            }
        }

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

    //missing this method because we have an old version see https://github.com/teamtnt/tntsearch/blob/3f6078c37d55feab3927d8f988f9e1e8b3aaa2a0/src/Indexer/TNTGeoIndexer.php#L80
    public function deleteGeoIndex($documentId,TntGeoIndexer $tntIndexer): void
    {
        $tntIndexer->prepareAndExecuteStatement("DELETE FROM locations WHERE doc_id = :documentId;", [
            ['key' => ':documentId', 'value' => $documentId]
        ]);
    }
}
