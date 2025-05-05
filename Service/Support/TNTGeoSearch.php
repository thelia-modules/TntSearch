<?php

namespace TntSearch\Service\Support;

use TeamTNT\TNTSearch\TNTGeoSearch as BaseTNTGeoSearch;

class TNTGeoSearch extends BaseTNTGeoSearch
{
    /**
     * Need to instantiate our TntGeoIndexer and set our connector.
     *
     * @throws Exception
     */
    public function getIndex()
    {
        $indexer           = new TntGeoIndexer;
        $indexer->inMemory = false;
        $indexer->setIndex($this->index);
        $indexer->loadConfig($this->config);

        $connector = $indexer->createConnector($this->config);
        $this->dbh = $connector->connect($this->config);

        $indexer->setDatabasePropelConnector($this->dbh);

        return $indexer;
    }


    /**
     * Need to instantiate our TntIndexer.
     */
    public function createIndex($indexName, $disableOutput = false): TntGeoIndexer
    {
        $indexer = new TntGeoIndexer;

        $indexer->loadConfig($this->config);
        $indexer->disableOutput = $disableOutput;

        if ($this->dbh) {
            $indexer->setDatabaseHandle($this->dbh);
        }
        return $indexer->createIndex($indexName);
    }
}
