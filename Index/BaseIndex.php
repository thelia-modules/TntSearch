<?php

namespace TntSearch\Index;

use Exception;
use Propel\Runtime\ActiveQuery\Criteria;
use ReflectionClass;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Thelia\Log\Tlog;
use Thelia\Model\ConfigQuery;
use Thelia\Model\LangQuery;
use TntSearch\Event\ExtendQueryEvent;
use TntSearch\Service\Provider\TntSearchProvider;
use TntSearch\Tokenizer\Tokenizer;

abstract class BaseIndex implements TntSearchIndexInterface
{
    /** @var EventDispatcherInterface */
    private $disptacher;

    /** @var TntSearchProvider */
    private $tntSearchProvider;

    public function __construct( EventDispatcherInterface $disptacher, TntSearchProvider $tntSearchProvider )
    {
        $this->disptacher = $disptacher;
        $this->tntSearchProvider = $tntSearchProvider;
    }

    /**
     * Applies the appropriate tokenization method to build the indexes.
     *
     * @return string
     */
    public function getTokenizer(): string
    {
        return Tokenizer::class;
    }

    /**
     * Returns the name of the index.
     *
     * @return string
     */
    public function getIndexName(): string
    {
        $reflectionClass = new ReflectionClass(get_called_class());
        return strtolower($reflectionClass->getShortName());
    }

    /**
     * Create and returns the name of the file in which the created indexes will be exported.
     *
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
     * Checks if an index is translatable.
     * If not: calls the method that will register the indexes
     * Otherwise calls the method that will translate them.
     *
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
     * Register indexes.
     *
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

            $indexName = $this->getIndexName();

            $extendQueryEvent = new ExtendQueryEvent();
            $extendQueryEvent
                ->setQuery($query)
                ->setItemId(null)
                ->setItemType($indexName);

            $this->disptacher->dispatch(ExtendQueryEvent::EXTEND_QUERY . $indexName, $extendQueryEvent);

            $tntIndexer->query($extendQueryEvent->getQuery());
            $tntIndexer->run();
        } catch (Exception $ex) {
            Tlog::getInstance()->addError("Error indexation on index $indexFileName : " . $ex->getMessage());
        }
    }

    /**
     * Translate indexes and calls the method that will register them.
     *
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