<?php

namespace TntSearch\Service;

use Exception;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\Map\TableMap;
use TeamTNT\TNTSearch\Exceptions\IndexNotFoundException;
use Thelia\Log\Tlog;
use TntSearch\Service\Provider\IndexationProvider;
use TntSearch\Service\Provider\TntSearchProvider;

class Search
{
    public function __construct(
        protected IndexationProvider $indexationProvider,
        protected TntSearchProvider  $tntSearchProvider
    )
    {
    }

    /**
     * @param string $searchWords
     * @param ?array $indexes
     * @param ?string $locale
     * @param ?int $offset
     * @param ?int $limit
     * @return array
     */
    public function search(
        string $searchWords,
        ?array  $indexes,
        ?string $locale,
        ?int    $offset,
        ?int    $limit
    ): array
    {
        $result = [];

        $indexlist = $indexes ? $this->indexationProvider->findIndexes($indexes): $this->indexationProvider->getIndexes();

        foreach ($indexlist as $index) {
            try {
                $indexLocale = $locale;

                if (!$index->isTranslatable()) {
                    $indexLocale = null;
                }

                $tntSate = $this->tntSearchProvider->getTntSearch($index->getTokenizer(), $locale);

                $result[$index->getIndexName()] = $tntSate->searchAndPaginate(
                    $searchWords,
                    $index->getIndexFileName($indexLocale),
                    $offset,
                    $limit
                );
            } catch (IndexNotFoundException $ex) {
                Tlog::getInstance()->addError('Error index missing : ' . $ex->getMessage());
                continue;
            } catch (Exception $ex) {
                Tlog::getInstance()->addError('Error on TntSearch search ' . $ex->getMessage());
                continue;
            }
        }

        return $result;
    }

    public function buildPropelModelFromIndex(string $indexName): ModelCriteria
    {
        /** @var ModelCriteria $modelQuery */
        $modelQuery = 'Thelia\\Model\\'.ucwords($indexName) . 'Query';

        return $modelQuery::create();
    }

    public function buildPropelTableMapFromIndex(string $indexName): string
    {
        return 'Thelia\\Model\\Map\\'.ucwords($indexName) . 'TableMap';
    }
}