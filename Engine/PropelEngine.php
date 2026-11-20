<?php

namespace TntSearch\Engine;

use TeamTNT\TNTSearch\Engines\SqliteEngine;
use TntSearch\Connector\PropelConnector;
use TntSearch\Index\TntSearchIndexInterface;

class PropelEngine extends SqliteEngine
{
    use WeightedWordlistTrait;

    protected TntSearchIndexInterface $indexObject;

    public function setIndexObject(TntSearchIndexInterface $indexObject): void
    {
        $this->indexObject = $indexObject;
    }
    public function createConnector(array $config): PropelConnector
    {
        return new PropelConnector;
    }

    public function loadConfig(array $config)
    {
        parent::loadConfig($config);

        if (!isset($this->dbh)) {
            $connector = $this->createConnector($this->config);
            $this->dbh = $connector->connect($this->config);
        }
    }

    protected function weightOf(string $field): int
    {
        return $this->indexObject->getFieldWeights($field);
    }
}