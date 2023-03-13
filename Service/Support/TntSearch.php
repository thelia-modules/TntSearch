<?php

namespace TntSearch\Service\Support;

use Exception;
use TntSearch\Service\Support\TntIndexer;
use TeamTNT\TNTSearch\Exceptions\IndexNotFoundException;
use TeamTNT\TNTSearch\TNTSearch as BaseTNTSearch;


/**
 * Extend TntSearch to rework stop words utilisation.
 * ex : Stop words can be use to clean search words too
 *
 * Override create and get index to handle Propel instance
 * @method selectIndex($getIndexFileName)
 */
class TntSearch extends BaseTNTSearch
{
    /** @var array */
    protected $stopWords = [];

    public function __construct(array $config)
    {
        parent::__construct();

        $this->loadConfig($config);

        $this->stemmer = $config['stemmer'] ?? null;
    }

    /**
     * Force stop word on every tokenisation
     *
     * @param $text
     * @return array
     */
    public function breakIntoTokens($text): array
    {
        return $this->tokenizer->tokenize($text, $this->stopWords);
    }

    /**
     * @param array $stopWords
     * @return void
     */
    public function setStopWords(array $stopWords): void
    {
        $this->stopWords = $stopWords;
    }

    /**
     * Need to instantiate our TntIndexer.
     *
     * @param string $indexName
     * @param boolean $disableOutput
     *
     * @return TNTIndexer
     */
    public function createIndex($indexName, $disableOutput = false): TNTIndexer
    {
        $indexer = new TNTIndexer;

        $indexer->loadConfig($this->config);
        $indexer->disableOutput = $disableOutput;

        $indexer->setStopWords($this->stopWords);

        if ($this->dbh) {
            $indexer->setDatabaseHandle($this->dbh);
        }
        return $indexer->createIndex($indexName);
    }

    /**
     * Need to instantiate our TntIndexer and set our connector.
     *
     * @return TNTIndexer
     * @throws Exception
     */
    public function getIndex(): TNTIndexer
    {
        $indexer = new TNTIndexer;

        $indexer->inMemory = false;
        $indexer->setIndex($this->index);
        $indexer->setStemmer($this->stemmer);
        $indexer->setTokenizer($this->tokenizer);
        $indexer->loadConfig($this->config);
        $connector = $indexer->createConnector($this->config);
        $this->dbh = $connector->connect($this->config);

        $indexer->setDatabasePropelConnector($this->dbh);

        return $indexer;
    }

    /**
     * Allow a kind of results pagination using offset and limit.
     *
     * @param string $search
     * @param string $index
     * @param int $offset
     * @param int $limit
     * @return array
     * @throws IndexNotFoundException
     */
    public function searchAndPaginate(string $search, string $index, int $offset = 0, int $limit = 100): array
    {
        $searchLimit = $limit + $offset;

        $this->selectIndex($index);

        $result = $this->search($search, $searchLimit)['ids'];

        if ($offset === 0) {
            return $result;
        }

        return array_slice($result, $offset, $limit, true);
    }
}