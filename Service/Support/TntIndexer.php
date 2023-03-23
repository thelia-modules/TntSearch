<?php

namespace TntSearch\Service\Support;

use PDO;
use Propel\Runtime\Connection\PdoConnection;
use TeamTNT\TNTSearch\Indexer\TNTIndexer as BaseTNTIndexer;
use TeamTNT\TNTSearch\Support\EdgeNgramTokenizer;
use TntSearch\Connector\PropelConnector;

/**
 * @method query(string $getQuery)
 * @method run()
 * @method decodeHtmlEntities()
 * @property PdoConnection $dbh
 */
class TntIndexer extends BaseTNTIndexer
{
    /**
     * @var string[] $fieldsWithNgramTokenization
     */
    private $fieldsWithNgramTokenization = [
        'title', 'ref', 'pse_ref', 'brand', 'features', 'firstname', 'lastname', 'email'
    ];

    /** @var bool $isFieldWithNgramTokenization */
    public $isFieldWithNgramTokenization = false;

    /**
     * Override tu use propel instance instead of dsn.
     *
     * @param array $config
     * @return PropelConnector
     */
    public function createConnector(array $config): PropelConnector
    {
        return new PropelConnector();
    }

    /**
     * Allow to handle PDOConnection from propel.
     *
     * @param PdoConnection $dbh
     */
    public function setDatabasePropelConnector(PdoConnection $dbh): void
    {
        $this->dbh = $dbh;
        if ($this->dbh->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $this->dbh->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        }
    }

    /**
     * Allow to select fields tokenization.
     *
     * @param $row
     * @return void
     */
    public function processDocument($row): void
    {
        $documentId = $row->get($this->getPrimaryKey());

        if ($this->excludePrimaryKey) {
            $row->forget($this->getPrimaryKey());
        }

        $stems = $row->map(function ($columnContent, $columnName) use ($row) {
            $this->isFieldWithNgramTokenization = false;
            if (in_array($columnName, $this->fieldsWithNgramTokenization, true)) {
                $columnContent = ($columnName === 'ref' || $columnName === 'pse_ref') ? str_replace(["-","_"], "", $columnContent) : $columnContent;
                $columnContent = $columnName !== 'email' ? $columnContent : explode('@', $columnContent)[0];
                $this->isFieldWithNgramTokenization = true;
            }
            return $this->stemText($columnContent);
        });
        $this->saveToIndex($stems, $documentId);
    }

    /**
     * Returns the tokenized indexes based on their field tokenization.
     *
     * @param $text
     * @return string[]
     */
    public function breakIntoTokens($text)
    {
        if ($this->decodeHTMLEntities) {
            $text = html_entity_decode($text);
        }
        $text = str_replace(['(', ')'], ' ', $text);

        if($this->isFieldWithNgramTokenization) {
            $edgeTokenizer = new EdgeNgramTokenizer();
            return $edgeTokenizer->tokenize($text, $this->stopWords);
        }
        return $this->tokenizer->tokenize($text, $this->stopWords);
    }
}