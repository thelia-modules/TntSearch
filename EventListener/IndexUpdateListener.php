<?php

namespace TntSearch\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Core\Event\Brand\BrandCreateEvent;
use Thelia\Core\Event\Brand\BrandDeleteEvent;
use Thelia\Core\Event\Brand\BrandUpdateEvent;
use Thelia\Core\Event\Category\CategoryCreateEvent;
use Thelia\Core\Event\Category\CategoryDeleteEvent;
use Thelia\Core\Event\Category\CategoryUpdateEvent;
use Thelia\Core\Event\Content\ContentCreateEvent;
use Thelia\Core\Event\Content\ContentDeleteEvent;
use Thelia\Core\Event\Content\ContentUpdateEvent;
use Thelia\Core\Event\Customer\CustomerCreateOrUpdateEvent;
use Thelia\Core\Event\Folder\FolderCreateEvent;
use Thelia\Core\Event\Folder\FolderDeleteEvent;
use Thelia\Core\Event\Folder\FolderUpdateEvent;
use Thelia\Core\Event\Product\ProductCreateEvent;
use Thelia\Core\Event\Product\ProductDeleteEvent;
use Thelia\Core\Event\Product\ProductUpdateEvent;
use Thelia\Core\Event\TheliaEvents;
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

            TheliaEvents::BRAND_CREATE => ['updateBrandIndex', 50],
            TheliaEvents::BRAND_UPDATE => ['updateBrandIndex', 50],
            TheliaEvents::BRAND_DELETE => ['updateBrandIndex', 50],

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
     * @param CustomerCreateOrUpdateEvent $event
     * @return void
     */
    public function updateCustomerIndex(CustomerCreateOrUpdateEvent $event): void
    {
        if ($event->hasCustomer()) {
            $this->itemIndexation->deleteItemOnIndexes($event->getCustomer()->getId(), 'customer');
            $this->itemIndexation->indexOneItemOnIndexes($event->getCustomer()->getId(), 'customer');
        }
    }

    /**
     * @param ProductCreateEvent|ProductUpdateEvent|ProductDeleteEvent $event
     * @return void
     */
    public function updateProductIndex(ProductCreateEvent|ProductUpdateEvent|ProductDeleteEvent $event): void
    {
        if ($event->getProduct()) {
            $deleteMode = $event instanceof ProductDeleteEvent;


            $this->itemIndexation->deleteItemOnIndexes($event->getProduct()->getId(), 'product');

            if (!$deleteMode) {
                $this->itemIndexation->indexOneItemOnIndexes($event->getProduct()->getId(), 'product');
            }
        }
    }

    /**
     * @param CategoryCreateEvent|CategoryUpdateEvent|CategoryDeleteEvent $event
     * @return void
     */
    public function updateCategoryIndex(CategoryCreateEvent|CategoryUpdateEvent|CategoryDeleteEvent $event): void
    {
        if ($event->getCategory()) {
            $deleteMode = $event instanceof CategoryDeleteEvent;

            $this->itemIndexation->deleteItemOnIndexes($event->getCategory()->getId(), 'category');

            if (!$deleteMode) {
                $this->itemIndexation->indexOneItemOnIndexes($event->getCategory()->getId(), 'category');
            }
        }
    }

    /**
     * @param FolderCreateEvent|FolderUpdateEvent|FolderDeleteEvent $event
     * @return void
     */
    public function updateFolderIndex(FolderCreateEvent|FolderUpdateEvent|FolderDeleteEvent $event): void
    {
        if ($event->getFolder()) {
            $deleteMode = $event instanceof FolderDeleteEvent;

            $this->itemIndexation->deleteItemOnIndexes($event->getFolder()->getId(), 'folder');

            if (!$deleteMode) {
                $this->itemIndexation->indexOneItemOnIndexes($event->getFolder()->getId(), 'folder');
            }
        }
    }


    /**
     * @param BrandCreateEvent|BrandUpdateEvent|BrandDeleteEvent $event
     * @return void
     */
    public function updateBrandIndex(BrandCreateEvent|BrandUpdateEvent|BrandDeleteEvent $event): void
    {
        if ($event->getBrand()) {
            $deleteMode = $event instanceof BrandDeleteEvent;

            $this->itemIndexation->deleteItemOnIndexes($event->getBrand()->getId(), 'brand');

            if (!$deleteMode) {
                $this->itemIndexation->indexOneItemOnIndexes($event->getBrand()->getId(), 'brand');
            }
        }
    }

    /**
     * @param ContentCreateEvent|ContentUpdateEvent|ContentDeleteEvent $event
     * @return void
     */
    public function updateContentIndex(ContentCreateEvent|ContentUpdateEvent|ContentDeleteEvent $event): void
    {
        if ($event->getContent()) {
            $deleteMode = $event instanceof ContentDeleteEvent;

            $this->itemIndexation->deleteItemOnIndexes($event->getContent()->getId(), 'content');

            if (!$deleteMode) {
                $this->itemIndexation->indexOneItemOnIndexes($event->getContent()->getId(), 'content');
            }
        }
    }
}