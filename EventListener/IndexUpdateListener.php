<?php

namespace TntSearch\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Core\Event\Category\CategoryDeleteEvent;
use Thelia\Core\Event\Category\CategoryEvent;
use Thelia\Core\Event\Content\ContentDeleteEvent;
use Thelia\Core\Event\Customer\CustomerEvent;
use Thelia\Core\Event\Folder\FolderDeleteEvent;
use Thelia\Core\Event\Folder\FolderEvent;
use Thelia\Core\Event\Product\ProductDeleteEvent;
use Thelia\Core\Event\Product\ProductEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Event\Content\ContentEvent;
use TntSearch\Service\ItemIndexation;
use TntSearch\TntSearch;

class IndexUpdateListener implements EventSubscriberInterface
{
    public function __construct(
        protected ItemIndexation $itemIndexation
    )
    {
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

            TheliaEvents::FOLDER_CREATE => ['updateFolderIndex', 50],
            TheliaEvents::FOLDER_UPDATE => ['updateFolderIndex', 50],
            TheliaEvents::FOLDER_DELETE => ['updateFolderIndex', 50],

            TheliaEvents::CONTENT_CREATE => ['updateContentIndex', 50],
            TheliaEvents::CONTENT_UPDATE => ['updateContentIndex', 50],
            TheliaEvents::CONTENT_DELETE => ['updateContentIndex', 50],

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

    /**
     * @param FolderEvent $event
     * @return void
     */
    public function updateFolderIndex(FolderEvent $event): void
    {
        if ($event->hasFolder()) {
            $deleteMode = $event instanceof FolderDeleteEvent;

            $this->itemIndexation->deleteItemOnIndexes($event->getFolder()->getId(), 'folder');

            if (!$deleteMode) {
                $this->itemIndexation->indexOneItemOnIndexes($event->getFolder()->getId(), 'folder');
            }
        }
    }

    /**
     * @param ContentEvent $event
     * @return void
     */
    public function updateContentIndex(ContentEvent $event): void
    {
        if ($event->hasContent()) {
            $deleteMode = $event instanceof ContentDeleteEvent;

            $this->itemIndexation->deleteItemOnIndexes($event->getContent()->getId(), 'content');

            if (!$deleteMode) {
                $this->itemIndexation->indexOneItemOnIndexes($event->getContent()->getId(), 'content');
            }
        }
    }
}