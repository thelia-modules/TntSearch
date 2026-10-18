<?php

namespace TntSearch\Service\Support;

use TeamTNT\TNTSearch\Indexer\TNTGeoIndexer as BaseTNTGeoIndexer;
use TntSearch\Connector\PropelConnector;
use TntSearch\Engine\PropelEngine;

class TntGeoIndexer extends BaseTNTGeoIndexer
{
    public function __construct()
    {
        parent::__construct(new PropelEngine());
        $this->dbh = (new PropelConnector())->connect([]);
    }

    /**
     * Override tu use propel instance instead of dsn.
     */
    public function createConnector(array $config): PropelConnector
    {
        return new PropelConnector();
    }

    /**
     * Allow to handle PDOConnection from propel.
     */
    public function setDatabasePropelConnector($dbh): void
    {
        $this->dbh = $dbh;
        if ($this->dbh->getAttribute(\PDO::ATTR_DRIVER_NAME) == 'mysql') {
            $this->dbh->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        }
    }
}
