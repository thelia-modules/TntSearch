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
use TntSearch\Event\WeightEvent;
use TntSearch\Service\Provider\TntSearchProvider;
use TntSearch\Tokenizer\Tokenizer;

abstract class BaseIndex implements TntSearchIndexInterface
{
    const FIELD_WEIGHT = [
        'title' => 10,
        'chapo' => 5
    ];

    public function __construct(
        protected EventDispatcherInterface $dispatcher,
        protected TntSearchProvider        $tntSearchProvider
    )
    {
    }

    public function getTokenizer(): string
    {
        return Tokenizer::class;
    }

    public function getFieldWeights($field): int
    {
        $weightEvent = new WeightEvent();
        $weightEvent->setFieldWeights(self::FIELD_WEIGHT);

        $this->dispatcher->dispatch($weightEvent, WeightEvent::WEIGHT . $this->getIndexName());

        return $weightEvent->getFieldWeight($field);
    }

    public function getIndexName(): string
    {
        $reflectionClass = new ReflectionClass(get_called_class());
        return strtolower($reflectionClass->getShortName());
    }

    public function getIndexFileName(string $locale = null, bool $isGeo = false): string
    {
        $indexName = $this->getIndexName();
        $indexFileName = $this->getIndexName() . '.index';

        if ($locale) {
            $indexFileName = $indexName . '_' . $locale . '.index';
        }

        if ($isGeo) {
            $indexFileName = $indexName . '_geo.index';
        }


        return $indexFileName;
    }

    /**
     * @throws Exception
     */
    public function index(): void
    {
        if ($this->isGeoIndexable()) {
            $this->geoIndex();
        }

        if (!$this->isTranslatable()) {
            $this->indexOneIndex();
            return;
        }

        $this->indexTranslatableIndexes();
    }

    /**
     * @throws Exception
     */
    protected function geoIndex(): void
    {
        $geoIndexer = $this->tntSearchProvider->getGeoTntIndexer();

        if (!$query = $this->buildSqlGeoQuery()) {
            throw new Exception("No query found for geo indexation");
        }

        $geoIndexer->createIndex($this->getIndexFileName(null, true));
        $geoIndexer->query($query);
        $geoIndexer->run();
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
                ->setLocale($locale)
                ->setItemType($indexName);

            $this->dispatcher->dispatch($extendQueryEvent, ExtendQueryEvent::EXTEND_QUERY . $indexName);

            $tntIndexer->setIndexObject($this);

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

    public function isGeoIndexable(): bool
    {
        return false;
    }

    public function buildSqlGeoQuery(int $itemId = null): ?string
    {
        return null;
    }
}
