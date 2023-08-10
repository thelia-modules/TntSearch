<?php

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
     * Override the event "ORDER_SEND_CONFIRMATION_EMAIL" to prevent the email sending if the order is not paid.
     * @param SaveRequestEvent $event
     *
     * @throws \Exception if the message cannot be loaded.
     */
    public function saveRequest(SaveRequestEvent $event): TntSearchLog
    {
        $entry = $this->tntSearchLogQuery->findOneBySearchWordsAndLocaleAndIndex($event->getSearchWords(),$event->getLocale(),$event->getIndex());
        if(empty($entry)){
            $entry = new TntSearchLog();
            $entry->setSearchWords($event->getSearchWords())
                ->setLocale($event->getLocale())
                ->setIndex($event->getIndex())
                ->setNumHits(0);
        }
        $entry->setNumHits($entry->getNumHits() + 1);
        $entry->save();
        return  $entry;
    }

    public static function getSubscribedEvents()
    {
        return array(
            SaveRequestEvent::SAVE_REQUEST           => array("saveRequest", 128),
        );
    }

}