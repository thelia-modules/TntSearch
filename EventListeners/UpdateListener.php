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
use Thelia\Core\Event\Customer\CustomerEvent;
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
    public static function getSubscribedEvents()
    {
        // We don't update indexes if updates on back-office changes is disabled.
        if (false === (bool) TntSearch::getConfigValue(TntSearch::ON_THE_FLY_UPDATE, true)) {
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
     * @throws \TeamTNT\TNTSearch\Exceptions\IndexNotFoundException
     */
    public function updateCustomerIndex(CustomerEvent $event)
    {
        $customer = $event->getCustomer();

        $tnt = TntSearch::getTntSearch();
        $tnt->selectIndex("customer.index");

        $index = $tnt->getIndex();

        $index->update($customer->getId(), $this->getCustomerData($customer));
    }

    /**
     * @param CustomerEvent $event
     * @throws \TeamTNT\TNTSearch\Exceptions\IndexNotFoundException
     */
    public function createCustomerIndex(CustomerEvent $event)
    {
        $customer = $event->getCustomer();

        $tnt = TntSearch::getTntSearch();

        $tnt->selectIndex("customer.index");

        $index = $tnt->getIndex();

        $index->insert($this->getCustomerData($customer));
    }

    /**
     * @param CustomerEvent $event
     * @throws \TeamTNT\TNTSearch\Exceptions\IndexNotFoundException
     */
    public function deleteCustomerIndex(CustomerEvent $event)
    {
        $customer = $event->getCustomer();

        $tnt = TntSearch::getTntSearch();
        $tnt->selectIndex("customer.index");

        $index = $tnt->getIndex();

        $index->delete($customer->getId());
    }

    /**
     * @param OrderEvent $event
     * @throws \Propel\Runtime\Exception\PropelException
     * @throws \TeamTNT\TNTSearch\Exceptions\IndexNotFoundException
     */
    public function createOrderIndex(OrderEvent $event)
    {
        $order = $event->getOrder();

        $tnt = TntSearch::getTntSearch();
        $tnt->selectIndex("order.index");

        $index = $tnt->getIndex();

        $index->insert($this->getOrderData($order));
    }

    /**
     * @param ProductUpdateEvent $event
     * @throws \TeamTNT\TNTSearch\Exceptions\IndexNotFoundException
     */
    public function updateProductIndex(ProductUpdateEvent $event)
    {
        $product = $event->getProduct();

        $tnt = TntSearch::getTntSearch($product->getLocale());
        $tnt->selectIndex("product_" . $product->getLocale() . ".index");

        $index = $tnt->getIndex();
        $index->update($product->getId(), $this->getProductData($product));
    }

    /**
     * @param ProductCreateEvent $event
     * @throws \TeamTNT\TNTSearch\Exceptions\IndexNotFoundException
     */
    public function createProductIndex(ProductCreateEvent $event)
    {
        $product = $event->getProduct();
        $langs = LangQuery::create()->filterByByDefault(1)->find();

        foreach ($langs as $lang) {
            $tnt = TntSearch::getTntSearch($lang->getLocale());

            $tnt->selectIndex("product_" . $lang->getLocale() . ".index");
            $product->setLocale($lang->getLocale());
            $index = $tnt->getIndex();
            $index->insert($this->getProductData($product));
        }
    }

    /**
     * @param ProductDeleteEvent $event
     * @throws \TeamTNT\TNTSearch\Exceptions\IndexNotFoundException
     */
    public function deleteProductIndex(ProductDeleteEvent $event)
    {
        $product = $event->getProduct();
        $langs = LangQuery::create()->filterByActive(1)->find();

        foreach ($langs as $lang) {
            $tnt = TntSearch::getTntSearch($lang->getLocale());

            $tnt->selectIndex("product_" . $lang->getLocale() . ".index");

            $index = $tnt->getIndex();

            $index->delete($product->getId());
        }
    }


    /**
     * @param CategoryUpdateEvent $event
     * @throws \TeamTNT\TNTSearch\Exceptions\IndexNotFoundException
     */
    public function updateCategoryIndex(CategoryUpdateEvent $event)
    {
        $category = $event->getCategory();

        $tnt = TntSearch::getTntSearch($category->getLocale());

        $tnt->selectIndex("category_" . $category->getLocale() . ".index");

        $index = $tnt->getIndex();
        $index->update($category->getId(), $this->getCategoryData($category));
    }

    /**
     * @param CategoryCreateEvent $event
     * @throws \TeamTNT\TNTSearch\Exceptions\IndexNotFoundException
     */
    public function createCategoryIndex(CategoryCreateEvent $event)
    {
        $category = $event->getCategory();
        $langs = LangQuery::create()->filterByByDefault(1)->find();

        foreach ($langs as $lang) {
            $tnt = TntSearch::getTntSearch($lang->getLocale());

            $tnt->selectIndex("category_" . $lang->getLocale() . ".index");
            $index = $tnt->getIndex();
            $category->setLocale($lang->getLocale());
            $index->insert($this->getCategoryData($category));
        }
    }

    /**
     * @param CategoryDeleteEvent $event
     * @throws \TeamTNT\TNTSearch\Exceptions\IndexNotFoundException
     */
    public function deleteCategoryIndex(CategoryDeleteEvent $event)
    {

        $category = $event->getCategory();
        $langs = LangQuery::create()->filterByActive(1)->find();

        foreach ($langs as $lang) {
            $tnt = TntSearch::getTntSearch($lang->getLocale());

            $tnt->selectIndex("category_" . $lang->getLocale() . ".index");

            $index = $tnt->getIndex();

            $index->delete($category->getId());
        }
    }


    /**
     * @param FolderUpdateEvent $event
     * @throws \TeamTNT\TNTSearch\Exceptions\IndexNotFoundException
     */
    public function updateFolderIndex(FolderUpdateEvent $event)
    {
        $folder = $event->getFolder();

        $tnt = TntSearch::getTntSearch($folder->getLocale());

        $tnt->selectIndex("folder_" . $folder->getLocale() . ".index");

        $index = $tnt->getIndex();
        $index->update($folder->getId(), $this->getFolderData($folder));
    }

    /**
     * @param FolderCreateEvent $event
     * @throws \TeamTNT\TNTSearch\Exceptions\IndexNotFoundException
     */
    public function createFolderIndex(FolderCreateEvent $event)
    {
        $folder = $event->getFolder();
        $langs = LangQuery::create()->filterByByDefault(1)->find();

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
    }

    /**
     * @param FolderDeleteEvent $event
     * @throws \TeamTNT\TNTSearch\Exceptions\IndexNotFoundException
     */
    public function deleteFolderIndex(FolderDeleteEvent $event)
    {

        $folder = $event->getFolder();
        $langs = LangQuery::create()->filterByActive(1)->find();

        foreach ($langs as $lang) {
            $tnt = TntSearch::getTntSearch($lang->getLocale());

            $tnt->selectIndex("folder_" . $lang->getLocale() . ".index");

            $index = $tnt->getIndex();

            $index->delete($folder->getId());
        }
    }


    /**
     * @param ContentUpdateEvent $event
     * @throws \TeamTNT\TNTSearch\Exceptions\IndexNotFoundException
     */
    public function updateContentIndex(ContentUpdateEvent $event)
    {
        $content = $event->getContent();

        $tnt = TntSearch::getTntSearch($content->getLocale());

        $tnt->selectIndex("content_" . $content->getLocale() . ".index");

        $index = $tnt->getIndex();
        $index->update($content->getId(), $this->getContentData($content));
    }

    /**
     * @param ContentCreateEvent $event
     * @throws \TeamTNT\TNTSearch\Exceptions\IndexNotFoundException
     */
    public function createContentIndex(ContentCreateEvent $event)
    {
        $content = $event->getContent();
        $langs = LangQuery::create()->filterByByDefault(1)->find();
        $tnt = TntSearch::getTntSearch();

        foreach ($langs as $lang) {
            $tnt->selectIndex("content_" . $lang->getLocale() . ".index");
            $index = $tnt->getIndex();
            $content->setLocale($lang->getLocale());
            $index->insert($this->getContentData($content));
        }
    }

    /**
     * @param ContentDeleteEvent $event
     * @throws \TeamTNT\TNTSearch\Exceptions\IndexNotFoundException
     */
    public function deleteContentIndex(ContentDeleteEvent $event)
    {

        $content = $event->getContent();
        $langs = LangQuery::create()->filterByActive(1)->find();

        foreach ($langs as $lang) {
            $tnt = TntSearch::getTntSearch($lang->getLocale());

            $tnt->selectIndex("content_" . $lang->getLocale() . ".index");

            $index = $tnt->getIndex();

            $index->delete($content->getId());
        }
    }


    /**
     * @param BrandUpdateEvent $event
     * @throws \TeamTNT\TNTSearch\Exceptions\IndexNotFoundException
     */
    public function updateBrandIndex(BrandUpdateEvent $event)
    {
        $brand = $event->getBrand();

        $tnt = TntSearch::getTntSearch($brand->getLocale());

        $tnt->selectIndex("brand_" . $brand->getLocale() . ".index");

        $index = $tnt->getIndex();
        $index->update($brand->getId(), $this->getBrandData($brand));
    }

    /**
     * @param BrandCreateEvent $event
     * @throws \TeamTNT\TNTSearch\Exceptions\IndexNotFoundException
     */
    public function createBrandIndex(BrandCreateEvent $event)
    {
        $brand = $event->getBrand();
        $langs = LangQuery::create()->filterByByDefault(1)->find();
        $tnt = TntSearch::getTntSearch();

        foreach ($langs as $lang) {
            $tnt->selectIndex("brand_" . $lang->getLocale() . ".index");
            $index = $tnt->getIndex();
            $brand->setLocale($lang->getLocale());
            $index->insert($this->getBrandData($brand));
        }
    }

    /**
     * @param BrandDeleteEvent $event
     * @throws \TeamTNT\TNTSearch\Exceptions\IndexNotFoundException
     */
    public function deleteBrandIndex(BrandDeleteEvent $event)
    {

        $brand = $event->getBrand();
        $langs = LangQuery::create()->filterByActive(1)->find();
        $tnt = TntSearch::getTntSearch();

        foreach ($langs as $lang) {
            $tnt->selectIndex("brand_" . $lang->getLocale() . ".index");
            $index = $tnt->getIndex();
            $index->delete($brand->getId());
        }
    }

    /**
     * @param Customer $customer
     * @return array
     */
    protected function getCustomerData(Customer $customer)
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
     * @throws \Propel\Runtime\Exception\PropelException
     */
    protected function getOrderData(Order $order)
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
    protected function getProductData(Product $product)
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
    protected function getCategoryData(Category $category)
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
    protected function getFolderData(Folder $folder)
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
    protected function getContentData(Content $content)
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
    protected function getBrandData(Brand $brand)
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
