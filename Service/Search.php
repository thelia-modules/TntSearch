<?php

namespace TntSearch\Service;

use Exception;
use TeamTNT\TNTSearch\Exceptions\IndexNotFoundException;
use Thelia\Log\Tlog;
use TntSearch\Index\TntSearchIndexInterface;
use TntSearch\Service\Provider\IndexationProvider;
use TntSearch\Service\Provider\TntSearchProvider;

class Search
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
     * @param string $searchWords
     * @param array $indexTypes
     * @param int $offset
     * @param int $limit
     * @param string|null $locale
     * @return array
     */
    public function search(
        string $searchWords,
        array  $indexTypes,
        int    $offset,
        int    $limit,
        string $locale = null
    ): array
    {
        $result = [];

        /** @var TntSearchIndexInterface $indexCollection */
        $indexCollection = $this->indexationProvider->findIndexes($indexTypes);

        foreach ($indexCollection as $index) {
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
                Tlog::getInstance()->addError('Error inex missing : ' . $ex->getMessage());
                continue;
            } catch (Exception $ex) {
                Tlog::getInstance()->addError('Error on TntSearch search');
                continue;
            }
        }

        return $result;
    }
}