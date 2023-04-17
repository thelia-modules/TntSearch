<?php

namespace TntSearch\Index;

use Exception;
use Propel\Runtime\ActiveQuery\Criteria;
use Psr\EventDispatcher\EventDispatcherInterface;
use ReflectionClass;
use Thelia\Log\Tlog;
use Thelia\Model\ConfigQuery;
use Thelia\Model\LangQuery;
use TntSearch\Event\ExtendQueryEvent;
use TntSearch\Service\Provider\TntSearchProvider;
use TntSearch\Tokenizer\Tokenizer;

abstract class BaseIndex implements TntSearchIndexInterface
{
    public function __construct(
        protected EventDispatcherInterface $disptacher,
        protected TntSearchProvider        $tntSearchProvider
    ) {}

    public function getTokenizer(): string
    {
        return Tokenizer::class;
    }

    public function getIndexName(): string
    {
        $reflectionClass = new ReflectionClass(get_called_class());
        return strtolower($reflectionClass->getShortName());
    }

    public function getIndexFileName(string $locale = null): string
    {
        $indexName = $this->getIndexName();
        $indexFileName = $this->getIndexName() . '.index';

        if ($locale) {
            $indexFileName = $indexName . '_' . $locale . '.index';
        }

        return $indexFileName;
    }

    public function index(): void
    {
        if (!$this->isTranslatable()) {
            $this->indexOneIndex();
            return;
        }

        $this->indexTranslatableIndexes();
    }

    protected function indexOneIndex(string $locale = null): void
    {
        $indexFileName = $this->getIndexFileName($locale);

        try {
            $tntSate = $this->tntSearchProvider->getTntSearch($this->getTokenizer(), $locale);

            $query = $this->buildSqlQuery(null, $locale);

            $tntIndexer = $tntSate->createIndex($indexFileName);
            $tntIndexer->decodeHtmlEntities();

            $indexName = $this->getIndexName();

            $extendQueryEvent = new ExtendQueryEvent();
            $extendQueryEvent
                ->setQuery($query)
                ->setItemId(null)
                ->setItemType($indexName);

            $this->disptacher->dispatch($extendQueryEvent, ExtendQueryEvent::EXTEND_QUERY . $indexName);

            $tntIndexer->query($extendQueryEvent->getQuery());
            $tntIndexer->run();
        } catch (Exception $ex) {
            Tlog::getInstance()->addError("Error indexation on index $indexFileName : " . $ex->getMessage());
        }
    }

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