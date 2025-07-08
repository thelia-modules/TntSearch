<?php

namespace TntSearch\Service\Support;

use TeamTNT\TNTSearch\TNTGeoSearch as BaseTNTGeoSearch;

class TNTGeoSearch extends BaseTNTGeoSearch
{
    /**
     * Need to instantiate our TntGeoIndexer and set our connector.
     *
     */
    public function getIndex(): TntGeoIndexer
    {
        $indexer           = new TntGeoIndexer;
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

        if ($this->dbh) {
            $indexer->setDatabaseHandle($this->dbh);
        }
        return $indexer->createIndex($indexName);
    }
}
