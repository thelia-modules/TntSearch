<?php

namespace TntSearch\Engine;

use TeamTNT\TNTSearch\Engines\SqliteEngine;
use TeamTNT\TNTSearch\Support\Collection;
use TntSearch\Connector\PropelConnector;
use TntSearch\Index\TntSearchIndexInterface;
use \PDO;

class PropelEngine extends SqliteEngine
{
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

        if (!$this->dbh) {
            $connector = $this->createConnector($this->config);
            $this->dbh = $connector->connect($this->config);
        }
    }

    public function saveWordlist(Collection $stems)
    {
        $terms = [];
        $stems->map(function ($column, $key) use (&$terms) {
            $weight = $this->indexObject->getFieldWeights($key);
            foreach ($column as $term) {
                if (array_key_exists($term, $terms)) {
                    $terms[$term]['hits'] += $weight;
                    $terms[$term]['docs'] = 1;
                } else {
                    $terms[$term] = [
                        'hits' => $weight,
                        'docs' => 1,
                        'id' => 0,
                    ];
                }
            }
        });

        foreach ($terms as $key => $term) {

            try {
                $this->insertWordlistStmt->bindParam(":keyword", $key);
                $this->insertWordlistStmt->bindParam(":hits", $term['hits']);
                $this->insertWordlistStmt->bindParam(":docs", $term['docs']);
                $this->insertWordlistStmt->execute();

                $lastInsertId = $this->index->query('SELECT MAX(id) FROM wordlist')->fetchColumn();
                $terms[$key]['id'] = $lastInsertId;

                if ($this->inMemory) {
                    $this->inMemoryTerms[$key] = $terms[$key]['id'];
                }

            } catch (\Exception $e) {

                if ($e->getCode() == 23000) {
                    $this->updateWordlistStmt->bindValue(':docs', $term['docs']);
                    $this->updateWordlistStmt->bindValue(':hits', $term['hits']);
                    $this->updateWordlistStmt->bindValue(':keyword', $key);
                    $this->updateWordlistStmt->execute();
                    if (!$this->inMemory || !array_key_exists($key, $this->inMemoryTerms)) {
                        $this->selectWordlistStmt->bindValue(':keyword', $key);
                        $this->selectWordlistStmt->execute();
                        $res = $this->selectWordlistStmt->fetch(PDO::FETCH_ASSOC);
                        $terms[$key]['id'] = $res['id'];
                    } else {
                        $terms[$key]['id'] = $this->inMemoryTerms[$key];
                    }
                } else {
                    echo "Error while saving wordlist: " . $e->getMessage() . "\n";
                }

                // Statements must be refreshed, because in this state they have error attached to them.
                $this->statementsPrepared = false;
                $this->prepareStatementsForIndex();

            }
        }

        return $terms;
    }
}