<?php

namespace TntSearch\Service;

use Exception;
use Propel\Runtime\ActiveQuery\Criteria;
use TeamTNT\TNTSearch\Indexer\TNTIndexer;
use Thelia\Log\Tlog;
use Thelia\Model\ConfigQuery;
use Thelia\Model\LangQuery;
use TntSearch\Index\TntSearchIndexInterface;
use TntSearch\Service\Provider\IndexationProvider;
use TntSearch\Service\Provider\TntSearchProvider;

class IndexItem
{
    /** @var IndexationProvider */
    private $indexationProvider;

    /** @var TntSearchProvider */
    private $tntSearchProvider;

    public function __construct(
        IndexationProvider $indexationProvider,
        TntSearchProvider  $tntSearchProvider
    )
    {
        $this->indexationProvider = $indexationProvider;
        $this->tntSearchProvider = $tntSearchProvider;
    }

    /**
     * @param int $itemId
     * @param string $itemIndexType
     * @return void
     */
    public function indexOneItemOnIndexes(int $itemId, string $itemIndexType): void
    {
        $index = $this->indexationProvider->getIndex($itemIndexType);

        foreach ($this->buildTNTIndexers($index) as $indexLocale => $tNTIndexer) {
            if (!$index->isTranslatable()) {
                $tNTIndexer->query($index->buildSqlQuery($itemId));
                $tNTIndexer->run();

                continue;
            }

            $tNTIndexer->query($index->buildSqlQuery($itemId, $indexLocale));
            $tNTIndexer->run();
        }
    }

    /**
     * @param int $itemId
     * @param string $itemIndexType
     * @return void
     */
    public function deleteItemOnIndexes(int $itemId, string $itemIndexType): void
    {
        $index = $this->indexationProvider->getIndex($itemIndexType);

        foreach ($this->buildTNTIndexers($index) as $tNTIndexer) {
            $tNTIndexer->delete($itemId);
        }
    }

    /**
     * @param TntSearchIndexInterface $index
     * @return TNTIndexer[]
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