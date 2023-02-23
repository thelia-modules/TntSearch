<?php

namespace TntSearch\Index;

use Exception;
use Propel\Runtime\ActiveQuery\Criteria;
use ReflectionClass;
use Thelia\Log\Tlog;
use Thelia\Model\ConfigQuery;
use Thelia\Model\LangQuery;
use TntSearch\Service\Provider\TntSearchProvider;

abstract class BaseIndex implements TntSearchIndexInterface
{
    /** @var TntSearchProvider */
    private $tntSearchProvider;

    public function __construct(TntSearchProvider $tntSearchProvider)
    {
        $this->tntSearchProvider = $tntSearchProvider;
    }

    /**
     * @return string
     */
    public function getTokenizer(): string
    {
        return \TntSearch\Tokenizer\TheliaTokenizer::class;
    }

    /**
     * @return string
     */
    public function getIndexName(): string
    {
        $reflectionClass = new ReflectionClass(get_called_class());
        return strtolower($reflectionClass->getShortName());
    }

    /**
     * @param string|null $locale
     * @return string
     */
    public function getIndexFileName(string $locale = null): string
    {
        $indexName = $this->getIndexName();
        $indexFileName = $this->getIndexName() . '.index';

        if ($locale) {
            $indexFileName = $indexName . '_' . $locale . '.index';
        }

        return $indexFileName;
    }

    /**
     * @return void
     */
    public function index(): void
    {
        if (!$this->isTranslatable()) {
            $this->indexOneIndex();
            return;
        }

        $this->indexTranslatableIndexes();
    }

    /**
     * @param string|null $locale
     * @return void
     */
    protected function indexOneIndex(string $locale = null): void
    {

        $indexFileName = $this->getIndexFileName($locale);

        try {
            $tntSate = $this->tntSearchProvider->getTntSearch($this->getTokenizer(), $locale);

            $query = $this->buildSqlQuery(null, $locale);

            $tntIndexer = $tntSate->createIndex($indexFileName);
            $tntIndexer->decodeHtmlEntities();

            $tntIndexer->query($query);
            $tntIndexer->run();

        } catch (Exception $ex) {
            Tlog::getInstance()->addError("Error indexation on index $indexFileName : " . $ex->getMessage());
        }
    }

    /**
     * @return void
     */
    protected function indexTranslatableIndexes(): void
    {
        $langs = LangQuery::create()
            ->filterByActive(1)
            ->filterById(ConfigQuery::read("indexation_exclude_lang", []), Criteria::NOT_IN)
            ->find();

        foreach ($langs as $lang) {
            $this->indexOneIndex($lang->getLocale());
        }
    }
}