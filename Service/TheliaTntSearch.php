<?php
/*************************************************************************************/
/*      Copyright (c) OpenStudio                                                     */
/*      web : https://www.openstudio.fr                                              */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE      */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

/**
 * Created by Franck Allimant, OpenStudio <fallimant@openstudio.fr>
 * Date: 21/12/2021
 */

namespace TntSearch\Service;

use TeamTNT\TNTSearch\Exceptions\IndexNotFoundException;
use TeamTNT\TNTSearch\Indexer\TNTIndexer;
use TeamTNT\TNTSearch\TNTSearch;

class TheliaTntSearch extends TNTSearch
{
    /** @var array */
    protected $stopWords;

    public function __construct(array $config)
    {
        parent::__construct();

        $this->loadConfig($config);

        $this->stemmer = $config['stemmer'] ?? null;
    }

    /**
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

    /**
     * @param $text
     * @return array
     */
    public function breakIntoTokens($text)
    {
        return $this->tokenizer->tokenize($text, $this->stopWords);
    }

    /**
     * @param $searchWords
     * @return string
     */
    public function sanitizeSearchWords($searchWords): string
    {
        return implode(' ', $this->tokenizer->tokenize($searchWords, $this->stopWords));
    }

    /**
     * @param array $stopWords
     * @return void
     */
    public function setStopWords(array $stopWords): void
    {
        $this->stopWords = $stopWords;
    }
}