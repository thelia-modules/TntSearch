<?php

namespace TntSearch\Service;

use Composer\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Thelia\Model\Base\LangQuery;
use TntSearch\Event\IndexesEvent;
use TntSearch\Model\TntSearchIndexQuery;
use TntSearch\TntSearch;

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

        $theliaIndexesData = TntSearchIndexQuery::create()->find()->toArray();

        $theliaIndexes=array_map(
            function($v) {
                return
                    [
                        'name' => $v["Index"],
                        'is_translatable' => $v["IsTranslatable"]
                    ];
            },
            $theliaIndexesData
        );


        foreach ($theliaIndexes as $index) {
            if ($index['is_translatable']) {
                foreach ($langs as $lang) {
                    $tnt = TntSearch::getTntSearch($lang->getLocale());
                    $this->index($index['name'], $tnt, $lang->getLocale());
                }
                continue;
            }
            $tnt = TntSearch::getTntSearch();
            $this->index($index['name'], $tnt);
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
        $eventName = 'TntSearch\\Event\\'.ucwords($indexName) . 'IndexationEvent';
        return new $eventName();
    }
}