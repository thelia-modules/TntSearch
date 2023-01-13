<?php
/*************************************************************************************/
/*      Copyright (c) Open Studio                                                    */
/*      web : https://open.studio                                                    */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE      */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

/**
 * Created by Franck Allimant, OpenStudio <fallimant@openstudio.fr>
 * Date: 15/10/2021 19:59
 */

namespace TntSearch\EventListeners;

use Propel\Runtime\Exception\PropelException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use TeamTNT\TNTSearch\Exceptions\IndexNotFoundException;
use Thelia\Core\Event\Brand\BrandCreateEvent;
use Thelia\Core\Event\Brand\BrandDeleteEvent;
use Thelia\Core\Event\Brand\BrandUpdateEvent;
use Thelia\Core\Event\Category\CategoryCreateEvent;
use Thelia\Core\Event\Category\CategoryDeleteEvent;
use Thelia\Core\Event\Category\CategoryUpdateEvent;
use Thelia\Core\Event\Content\ContentCreateEvent;
use Thelia\Core\Event\Content\ContentDeleteEvent;
use Thelia\Core\Event\Content\ContentUpdateEvent;
use Thelia\Model\Event\CustomerEvent;
use Thelia\Core\Event\Folder\FolderCreateEvent;
use Thelia\Core\Event\Folder\FolderDeleteEvent;
use Thelia\Core\Event\Folder\FolderUpdateEvent;
use Thelia\Core\Event\Order\OrderEvent;
use Thelia\Core\Event\Product\ProductCreateEvent;
use Thelia\Core\Event\Product\ProductDeleteEvent;
use Thelia\Core\Event\Product\ProductUpdateEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Model\Base\Category;
use Thelia\Model\Brand;
use Thelia\Model\Content;
use Thelia\Model\Customer;
use Thelia\Model\Folder;
use Thelia\Model\LangQuery;
use Thelia\Model\Order;
use Thelia\Model\Product;
use TntSearch\TntSearch;

class UpdateListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        // We don't update indexes if updates on back-office changes is disabled.
        if (false === (bool)TntSearch::getConfigValue(TntSearch::ON_THE_FLY_UPDATE, true)) {
            return [];
        }

        return [
            TheliaEvents::CUSTOMER_CREATEACCOUNT => 'createCustomerIndex',
            TheliaEvents::CUSTOMER_UPDATEACCOUNT => 'updateCustomerIndex',
            TheliaEvents::CUSTOMER_DELETEACCOUNT => 'deleteCustomerIndex',

            \Thelia\Model\Event\OrderEvent::POST_INSERT => 'createOrderIndex',

            TheliaEvents::PRODUCT_CREATE => 'createProductIndex',
            TheliaEvents::PRODUCT_UPDATE => 'updateProductIndex',
            TheliaEvents::PRODUCT_DELETE => 'deleteProductIndex',

            TheliaEvents::CATEGORY_CREATE => 'createCategoryIndex',
            TheliaEvents::CATEGORY_UPDATE => 'updateCategoryIndex',
            TheliaEvents::CATEGORY_DELETE => 'deleteCategoryIndex',

            TheliaEvents::CONTENT_CREATE => 'createContentIndex',
            TheliaEvents::CONTENT_UPDATE => 'updateContentIndex',
            TheliaEvents::CONTENT_DELETE => 'deleteContentIndex',

            TheliaEvents::FOLDER_CREATE => 'createFolderIndex',
            TheliaEvents::FOLDER_UPDATE => 'updateFolderIndex',
            TheliaEvents::FOLDER_DELETE => 'deleteFolderIndex',

            TheliaEvents::BRAND_CREATE => 'createBrandIndex',
            TheliaEvents::BRAND_UPDATE => 'updateBrandIndex',
            TheliaEvents::BRAND_DELETE => 'deleteBrandIndex',
        ];
    }


    /**
     * @param CustomerEvent $event
     */
    public function updateCustomerIndex(CustomerEvent $event)
    {
        $customer = $event->getModel();

        try {
            $tnt = TntSearch::getTntSearch();
            $tnt->selectIndex("customer.index");
        } catch (IndexNotFoundException $error) {
            return;
        }

        $index = $tnt->getIndex();

        $index->update($customer->getId(), $this->getCustomerData($customer));
    }

    /**
     * @param CustomerEvent $event
     */
    public function createCustomerIndex(CustomerEvent $event)
    {
        $customer = $event->getModel();

        try {
            $tnt = TntSearch::getTntSearch();
            $tnt->selectIndex("customer.index");
        } catch (IndexNotFoundException $error) {
            return;
        }

        $index = $tnt->getIndex();

        $index->insert($this->getCustomerData($customer));
    }

    /**
     * @param CustomerEvent $event
     */
    public function deleteCustomerIndex(CustomerEvent $event)
    {
        $customer = $event->getModel();

        try {
            $tnt = TntSearch::getTntSearch();
            $tnt->selectIndex("customer.index");
        } catch (IndexNotFoundException $error) {
            return;
        }

        $index = $tnt->getIndex();

        $index->delete($customer->getId());
    }

    /**
     * @param OrderEvent $event
     * @throws PropelException
     */
    public function createOrderIndex(OrderEvent $event)
    {
        $order = $event->getOrder();

        try {
            $tnt = TntSearch::getTntSearch();
            $tnt->selectIndex("order.index");
        } catch (IndexNotFoundException $error) {
            return;
        }

        $index = $tnt->getIndex();

        $index->insert($this->getOrderData($order));
    }

    /**
     * @param ProductUpdateEvent $event
     */
    public function updateProductIndex(ProductUpdateEvent $event)
    {
        $product = $event->getProduct();

        try {
            $tnt = TntSearch::getTntSearch($product->getLocale());
            $tnt->selectIndex("product_" . $product->getLocale() . ".index");
        } catch (IndexNotFoundException $error) {
            return;
        }

        $index = $tnt->getIndex();
        $index->update($product->getId(), $this->getProductData($product));
    }

    /**
     * @param ProductCreateEvent $event
     */
    public function createProductIndex(ProductCreateEvent $event)
    {
        $product = $event->getProduct();
        $langs = LangQuery::create()->filterByByDefault(1)->find();

        try {
            foreach ($langs as $lang) {
                $tnt = TntSearch::getTntSearch($lang->getLocale());

                $tnt->selectIndex("product_" . $lang->getLocale() . ".index");
                $product->setLocale($lang->getLocale());
                $index = $tnt->getIndex();
                $index->insert($this->getProductData($product));
            }
        } catch (IndexNotFoundException $error) {
            return;
        }
    }

    /**
     * @param ProductDeleteEvent $event
     */
    public function deleteProductIndex(ProductDeleteEvent $event)
    {
        $product = $event->getProduct();
        $langs = LangQuery::create()->filterByActive(1)->find();

        try {
            foreach ($langs as $lang) {
                $tnt = TntSearch::getTntSearch($lang->getLocale());
                $tnt->selectIndex("product_" . $lang->getLocale() . ".index");

                $index = $tnt->getIndex();
                $index->delete($product->getId());
            }
        } catch (IndexNotFoundException $error) {
            return;
        }
    }


    /**
     * @param CategoryUpdateEvent $event
     */
    public function updateCategoryIndex(CategoryUpdateEvent $event)
    {
        $category = $event->getCategory();

        try {
            $tnt = TntSearch::getTntSearch($category->getLocale());
            $tnt->selectIndex("category_" . $category->getLocale() . ".index");
        } catch (IndexNotFoundException $error) {
            return;
        }

        $index = $tnt->getIndex();
        $index->update($category->getId(), $this->getCategoryData($category));
    }

    /**
     * @param CategoryCreateEvent $event
     */
    public function createCategoryIndex(CategoryCreateEvent $event)
    {
        $category = $event->getCategory();
        $langs = LangQuery::create()->filterByByDefault(1)->find();

        try {
            foreach ($langs as $lang) {
                $tnt = TntSearch::getTntSearch($lang->getLocale());

                $tnt->selectIndex("category_" . $lang->getLocale() . ".index");
                $index = $tnt->getIndex();
                $category->setLocale($lang->getLocale());
                $index->insert($this->getCategoryData($category));
            }
        } catch (IndexNotFoundException $error) {
            return;
        }
    }

    /**
     * @param CategoryDeleteEvent $event
     */
    public function deleteCategoryIndex(CategoryDeleteEvent $event)
    {
        $category = $event->getCategory();
        $langs = LangQuery::create()->filterByActive(1)->find();

        try {
            foreach ($langs as $lang) {
                $tnt = TntSearch::getTntSearch($lang->getLocale());
                $tnt->selectIndex("category_" . $lang->getLocale() . ".index");

                $index = $tnt->getIndex();
                $index->delete($category->getId());
            }

        } catch (IndexNotFoundException $error) {
            return;
        }
    }


    /**
     * @param FolderUpdateEvent $event
     */
    public function updateFolderIndex(FolderUpdateEvent $event)
    {
        $folder = $event->getFolder();

        try {
            $tnt = TntSearch::getTntSearch($folder->getLocale());
            $tnt->selectIndex("folder_" . $folder->getLocale() . ".index");
        } catch (IndexNotFoundException $error) {
            return;
        }

        $index = $tnt->getIndex();
        $index->update($folder->getId(), $this->getFolderData($folder));
    }

    /**
     * @param FolderCreateEvent $event
     */
    public function createFolderIndex(FolderCreateEvent $event)
    {
        $folder = $event->getFolder();
        $langs = LangQuery::create()->filterByByDefault(1)->find();

        try {
            foreach ($langs as $lang) {
                $tnt = TntSearch::getTntSearch($lang->getLocale());

                $tnt->selectIndex("folder_" . $lang->getLocale() . ".index");
                $index = $tnt->getIndex();
                $folder->setLocale($lang->getLocale());
                $index->insert([
                    'id' => $folder->getId(),
                    'title' => $folder->getTitle(),
                    'chapo' => $folder->getChapo(),
                    'description' => $folder->getDescription(),
                    'postscriptum' => $folder->getPostscriptum()
                ]);
            }
        } catch (IndexNotFoundException $error) {
            return;
        }
    }

    /**
     * @param FolderDeleteEvent $event
     */
    public function deleteFolderIndex(FolderDeleteEvent $event)
    {

        $folder = $event->getFolder();
        $langs = LangQuery::create()->filterByActive(1)->find();

        try {
            foreach ($langs as $lang) {
                $tnt = TntSearch::getTntSearch($lang->getLocale());
                $tnt->selectIndex("folder_" . $lang->getLocale() . ".index");

                $index = $tnt->getIndex();
                $index->delete($folder->getId());
            }
        } catch (IndexNotFoundException $error) {
            return;
        }
    }


    /**
     * @param ContentUpdateEvent $event
     */
    public function updateContentIndex(ContentUpdateEvent $event)
    {
        $content = $event->getContent();

        try {
            $tnt = TntSearch::getTntSearch($content->getLocale());
            $tnt->selectIndex("content_" . $content->getLocale() . ".index");
        } catch (IndexNotFoundException $error) {
            return;
        }

        $index = $tnt->getIndex();
        $index->update($content->getId(), $this->getContentData($content));
    }

    /**
     * @param ContentCreateEvent $event
     */
    public function createContentIndex(ContentCreateEvent $event)
    {
        $content = $event->getContent();
        $langs = LangQuery::create()->filterByByDefault(1)->find();
        $tnt = TntSearch::getTntSearch();

        try {
            foreach ($langs as $lang) {
                $tnt->selectIndex("content_" . $lang->getLocale() . ".index");
                $index = $tnt->getIndex();
                $content->setLocale($lang->getLocale());
                $index->insert($this->getContentData($content));
            }
        } catch (IndexNotFoundException $error) {
            return;
        }
    }

    /**
     * @param ContentDeleteEvent $event
     */
    public function deleteContentIndex(ContentDeleteEvent $event)
    {
        $content = $event->getContent();
        $langs = LangQuery::create()->filterByActive(1)->find();

        try {
            foreach ($langs as $lang) {
                $tnt = TntSearch::getTntSearch($lang->getLocale());
                $tnt->selectIndex("content_" . $lang->getLocale() . ".index");

                $index = $tnt->getIndex();
                $index->delete($content->getId());
            }
        } catch (IndexNotFoundException $error) {
            return;
        }
    }


    /**
     * @param BrandUpdateEvent $event
     */
    public function updateBrandIndex(BrandUpdateEvent $event)
    {
        $brand = $event->getBrand();
        try {
            $tnt = TntSearch::getTntSearch($brand->getLocale());
            $tnt->selectIndex("brand_" . $brand->getLocale() . ".index");
        } catch (IndexNotFoundException $error) {
            return;
        }

        $index = $tnt->getIndex();
        $index->update($brand->getId(), $this->getBrandData($brand));
    }

    /**
     * @param BrandCreateEvent $event
     */
    public function createBrandIndex(BrandCreateEvent $event)
    {
        $brand = $event->getBrand();
        $langs = LangQuery::create()->filterByByDefault(1)->find();
        $tnt = TntSearch::getTntSearch();

        try {
            foreach ($langs as $lang) {
                $tnt->selectIndex("brand_" . $lang->getLocale() . ".index");
                $index = $tnt->getIndex();

                $brand->setLocale($lang->getLocale());
                $index->insert($this->getBrandData($brand));
            }
        } catch (IndexNotFoundException $error) {
            return;
        }
    }

    /**
     * @param BrandDeleteEvent $event
     */
    public function deleteBrandIndex(BrandDeleteEvent $event)
    {

        $brand = $event->getBrand();
        $langs = LangQuery::create()->filterByActive(1)->find();
        $tnt = TntSearch::getTntSearch();
        try {
            foreach ($langs as $lang) {
                $tnt->selectIndex("brand_" . $lang->getLocale() . ".index");
                $index = $tnt->getIndex();
                $index->delete($brand->getId());
            }
        } catch (IndexNotFoundException $error) {
            return;
        }
    }

    /**
     * @param Customer $customer
     * @return array
     */
    protected function getCustomerData(Customer $customer): array
    {
        return [
            'id' => $customer->getId(),
            'ref' => $customer->getRef(),
            'firstname' => $customer->getFirstname(),
            'lastname' => $customer->getLastname(),
            'email' => $customer->getEmail()
        ];
    }

    /**
     * @param Order $order
     * @return array
     * @throws PropelException
     */
    protected function getOrderData(Order $order): array
    {
        $customer = $order->getCustomer();
        return [
            'id' => $order->getId(),
            'ref' => $order->getRef(),
            'customer_ref' => $customer->getRef(),
            'firstname' => $customer->getFirstname(),
            'lastname' => $customer->getLastname(),
            'email' => $customer->getEmail(),
            'invoice_ref' => $order->getInvoiceRef(),
            'transaction_ref' => $order->getTransactionRef(),
            'delivery_ref' => $order->getDeliveryRef()
        ];
    }

    /**
     * @param Product $product
     * @return array
     */
    protected function getProductData(Product $product): array
    {
        return [
            'id' => $product->getId(),
            'ref' => $product->getRef(),
            'title' => $product->getTitle(),
            'chapo' => $product->getChapo(),
            'description' => $product->getDescription(),
            'postscriptum' => $product->getPostscriptum()
        ];
    }

    /**
     * @param Category $category
     * @return array
     */
    protected function getCategoryData(Category $category): array
    {
        return [
            'id' => $category->getId(),
            'title' => $category->getTitle(),
            'chapo' => $category->getChapo(),
            'description' => $category->getDescription(),
            'postscriptum' => $category->getPostscriptum()
        ];
    }

    /**
     * @param Folder $folder
     * @return array
     */
    protected function getFolderData(Folder $folder): array
    {
        return [
            'id' => $folder->getId(),
            'title' => $folder->getTitle(),
            'chapo' => $folder->getChapo(),
            'description' => $folder->getDescription(),
            'postscriptum' => $folder->getPostscriptum()
        ];
    }

    /**
     * @param Content $content
     * @return array
     */
    protected function getContentData(Content $content): array
    {
        return [
            'id' => $content->getId(),
            'title' => $content->getTitle(),
            'chapo' => $content->getChapo(),
            'description' => $content->getDescription(),
            'postscriptum' => $content->getPostscriptum()
        ];
    }

    /**
     * @param Brand $brand
     * @return array
     */
    protected function getBrandData(Brand $brand): array
    {
        return [
            'id' => $brand->getId(),
            'title' => $brand->getTitle(),
            'chapo' => $brand->getChapo(),
            'description' => $brand->getDescription(),
            'postscriptum' => $brand->getPostscriptum()
        ];
    }
}
