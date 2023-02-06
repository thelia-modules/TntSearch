<?php

namespace TntSearch\Service;

use Composer\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Thelia\Model\Base\LangQuery;
use TntSearch\Event\IndexesEvent;
use TntSearch\TntSearch as TntSearchModule;

class IndexationService
{
    /** @var EventDispatcher */
    private $dispatcher;

    public function __construct(EventDispatcherInterface $disptacher)
    {
        $this->dispatcher = $disptacher;
    }

    /**
     * @return void
     */
    public function generateIndexes(): void
    {
        $langs = LangQuery::create()->filterByActive(1)->find();

        foreach (TntSearchModule::THELIA_INDEXES as $name => $index) {
            if (!$index['is_translatable']) {
                $tnt = TntSearchModule::getTntSearch(null, $index['tokenizer'] ?? null);
                $this->index($name, $tnt);
                continue;
            }

            foreach ($langs as $lang) {
                $tnt = TntSearchModule::getTntSearch($lang->getLocale(), $index['tokenizer'] ?? null);
                $this->index($name, $tnt, $lang->getLocale());
            }
        }
    }

    public function index($indexName, TheliaTntSearch $theliaTntSearch, $locale = null)
    {
        $indexTitle = ($locale) ? $indexName . '_' . $locale . '.index' : $indexName . '.index';
        $tntIndexer = $theliaTntSearch->createIndex($indexTitle);

        $indexEvent = $this->getIndexEvent($indexName)
            ->setLocale($locale);
        $indexEvent->buildSqlQuery();

        $indexEventName = "action.tntsearch.$indexName.sql.query.indexation";

        $this->dispatcher->dispatch($indexEventName, $indexEvent);

        $tntIndexer->query($indexEvent->getSqlQuery());
        $tntIndexer->run();
    }

    protected function getIndexEvent(string $indexName): IndexesEvent
    {
        $eventName = 'TntSearch\\Event\\' . ucwords($indexName) . 'IndexationEvent';
        return new $eventName();
    }
}