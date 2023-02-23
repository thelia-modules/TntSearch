<?php

namespace TntSearch\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Core\Event\Category\CategoryDeleteEvent;
use Thelia\Core\Event\Category\CategoryEvent;
use Thelia\Core\Event\Customer\CustomerEvent;
use Thelia\Core\Event\Product\ProductDeleteEvent;
use Thelia\Core\Event\Product\ProductEvent;
use Thelia\Core\Event\TheliaEvents;
use TntSearch\Service\IndexItem;
use TntSearch\TntSearch;

class IndexUpdateListener implements EventSubscriberInterface
{
    /**
     * @var IndexItem
     */
    private $itemIndexation;

    public function __construct(
        IndexItem $itemIndexation
    )
    {
        $this->itemIndexation = $itemIndexation;
    }

    public static function getSubscribedEvents(): array
    {
        // We don't update indexes if updates on back-office changes is disabled.
        if (false === (bool)TntSearch::getConfigValue(TntSearch::ON_THE_FLY_UPDATE, true)) {
            return [];
        }

        return [
            TheliaEvents::PRODUCT_CREATE => ['updateProductIndex', 50],
            TheliaEvents::PRODUCT_UPDATE => ['updateProductIndex', 50],
            TheliaEvents::PRODUCT_DELETE => ['updateProductIndex', 50],

            TheliaEvents::CATEGORY_CREATE => ['updateCategoryIndex', 50],
            TheliaEvents::CATEGORY_UPDATE => ['updateCategoryIndex', 50],
            TheliaEvents::CATEGORY_DELETE => ['updateCategoryIndex', 50],

            TheliaEvents::CUSTOMER_CREATEACCOUNT => ['updateCustomerIndex', 50],
            TheliaEvents::CUSTOMER_UPDATEACCOUNT => ['updateCustomerIndex', 50],
        ];
    }

    /**
     * @param CustomerEvent $event
     * @return void
     */
    public function updateCustomerIndex(CustomerEvent $event): void
    {
        if ($event->hasCustomer()) {
            $this->itemIndexation->deleteItemOnIndexes($event->getCustomer()->getId(), 'customer');
            $this->itemIndexation->indexOneItemOnIndexes($event->getCustomer()->getId(), 'customer');
        }
    }

    /**
     * @param ProductEvent $event
     * @return void
     */
    public function updateProductIndex(ProductEvent $event): void
    {
        if ($event->hasProduct()) {
            $deleteMode = $event instanceof ProductDeleteEvent;

            $this->itemIndexation->deleteItemOnIndexes($event->getProduct()->getId(), 'product');

            if (!$deleteMode) {
                $this->itemIndexation->indexOneItemOnIndexes($event->getProduct()->getId(), 'product');
            }
        }
    }

    /**
     * @param CategoryEvent $event
     * @return void
     */
    public function updateCategoryIndex(CategoryEvent $event): void
    {
        if ($event->hasCategory()) {
            $deleteMode = $event instanceof CategoryDeleteEvent;

            $this->itemIndexation->deleteItemOnIndexes($event->getCategory()->getId(), 'category');

            if (!$deleteMode) {
                $this->itemIndexation->indexOneItemOnIndexes($event->getCategory()->getId(), 'category');
            }
        }
    }
}