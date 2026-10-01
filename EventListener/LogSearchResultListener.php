<?php

declare(strict_types=1);

namespace TntSearch\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Action\BaseAction;
use Thelia\Core\Event\Product\ProductSearchedEvent;
use Thelia\Log\Tlog;
use TntSearch\Event\SaveRequestEvent;
use TntSearch\Model\TntSearchLog;
use TntSearch\Model\TntSearchLogQuery;

class LogSearchResultListener extends BaseAction implements EventSubscriberInterface
{
    private const PRODUCT_INDEX = 'product';

    // search_words is a VARCHAR(255)
    private const SEARCH_WORDS_LENGTH = 255;

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
        return $this->log($event->getSearchWords(), $event->getLocale(), $event->getIndex(), $event->getHits());
    }

    /**
     * A product search a front theme ran on its own (Flexy queries the catalogue through the API), logged on the
     * product index like the searches of the module, so the search log sees the visitors' searches.
     *
     * The term comes from a public URL: it is cut to the column length, and a log that cannot be written never
     * breaks the shopper's search page.
     */
    public function logShopSearch(ProductSearchedEvent $event): void
    {
        try {
            $this->log(mb_substr($event->getTerm(), 0, self::SEARCH_WORDS_LENGTH), $event->getLocale(), self::PRODUCT_INDEX, $event->getHits());
        } catch (\Throwable $exception) {
            Tlog::getInstance()->addError('TntSearch could not log a shop search: '.$exception->getMessage());
        }
    }

    /**
     * @throws \Propel\Runtime\Exception\PropelException
     */
    private function log(string $searchWords, string $locale, string $index, int $hits): TntSearchLog
    {
        $entry = $this->tntSearchLogQuery->findOneBySearchWordsAndLocaleAndIndex($searchWords, $locale, $index);

        if (null === $entry) {
            $entry = new TntSearchLog();
            $entry->setSearchWords($searchWords)
                ->setLocale($locale)
                ->setIndex($index);
        } else {
            $entry->setSearchCount($entry->getSearchCount() + 1);
        }

        $entry->setNumHits($hits);
        $entry->save();

        return $entry;
    }

    public static function getSubscribedEvents(): array
    {
        // The core raises ProductSearchedEvent under its class name; ::class does not load the class, so the
        // subscription holds on a core that does not ship the event yet.
        return array(
            SaveRequestEvent::SAVE_REQUEST           => array("saveRequest", 128),
            ProductSearchedEvent::class              => array("logShopSearch", 128),
        );
    }

}