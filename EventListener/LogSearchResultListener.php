<?php

declare(strict_types=1);

namespace TntSearch\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Action\BaseAction;
use Thelia\Core\Event\TheliaEvents;
use TntSearch\Event\SaveRequestEvent;
use TntSearch\Model\TntSearchLog;
use TntSearch\Model\TntSearchLogQuery;

class LogSearchResultListener extends BaseAction implements EventSubscriberInterface
{
    public function __construct(
        protected TntSearchLogQuery $tntSearchLogQuery
    )
    {
    }

    /**
     * Log a search: a new term starts with a count of 1 (the column default), a known term has its count increased.
     *
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function saveRequest(SaveRequestEvent $event): TntSearchLog
    {
        $entry = $this->tntSearchLogQuery->findOneBySearchWordsAndLocaleAndIndex($event->getSearchWords(), $event->getLocale(), $event->getIndex());

        if (null === $entry) {
            $entry = new TntSearchLog();
            $entry->setSearchWords($event->getSearchWords())
                ->setLocale($event->getLocale())
                ->setIndex($event->getIndex());
        } else {
            $entry->setSearchCount($entry->getSearchCount() + 1);
        }

        $entry->setNumHits($event->getHits());
        $entry->save();

        return $entry;
    }

    public static function getSubscribedEvents(): array
    {
        return array(
            SaveRequestEvent::SAVE_REQUEST           => array("saveRequest", 128),
        );
    }

}