<?php

namespace TntSearch\Service\Support;

use TeamTNT\TNTSearch\Indexer\TNTGeoIndexer as BaseTNTGeoIndexer;
use TntSearch\Connector\PropelConnector;

class TntGeoIndexer extends BaseTNTGeoIndexer
{
    public function __construct()
    {
        parent::__construct();
        $this->dbh = (new PropelConnector())->connect($this->config);
    }
}
