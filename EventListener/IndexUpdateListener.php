<?php

declare(strict_types=1);

namespace TntSearch\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Core\Event\Customer\CustomerCreateOrUpdateEvent;
use Thelia\Core\Event\TheliaEvents;
use TntSearch\Service\ItemIndexation;
use TntSearch\TntSearch;

class IndexUpdateListener implements EventSubscriberInterface
{
    public function __construct(
        protected ItemIndexation $itemIndexation,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        // Always subscribed: the "update in real time" setting is read when an event comes (isEnabled()), so
        // saving it in the back-office takes effect without a cache clear.
        return [
            TheliaEvents::CUSTOMER_CREATEACCOUNT => ['updateCustomerIndex', 50],
            TheliaEvents::CUSTOMER_UPDATEACCOUNT => ['updateCustomerIndex', 50],
        ];
    }

    public function updateCustomerIndex(CustomerCreateOrUpdateEvent $event): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        if ($event->hasCustomer()) {
            $this->itemIndexation->deleteItemOnIndexes($event->getCustomer()->getId(), 'customer');
            $this->itemIndexation->indexOneItemOnIndexes($event->getCustomer()->getId(), 'customer');
        }
    }

    private function isEnabled(): bool
    {
        return (bool) TntSearch::getConfigValue(TntSearch::ON_THE_FLY_UPDATE, true);
    }
}
