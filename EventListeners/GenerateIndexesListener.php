<?php
/**
 * Created by PhpStorm.
 * User: nicolasbarbey
 * Date: 31/07/2020
 * Time: 16:03
 */

namespace TntSearch\EventListeners;


use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Model\Base\LangQuery;
use TntSearch\Event\GenerateIndexesEvent;
use TntSearch\TntSearch;

class GenerateIndexesListener implements EventSubscriberInterface
{
    public function generateIndexes()
    {
        $langs = LangQuery::create()->filterByActive(1)->find();

        $tnt = TntSearch::getTntSearch();

        $indexer = $tnt->createIndex('customer.index');
        $indexer->query('SELECT id, ref, firstname, lastname, email FROM customer;');

        $indexer->run();

        $indexer = $tnt->createIndex('order.index');
        $indexer->query('SELECT o.id AS id,
                                o.ref AS ref,
                                c.ref AS customer_ref,
                                c.firstname AS firstname,
                                c.lastname AS lastname,
                                c.email AS email,
                                o.invoice_ref AS invoice_ref,
                                o.transaction_ref AS transaction_ref,
                                o.delivery_ref AS delivery_ref
                                FROM `order` AS o LEFT JOIN customer AS c ON o.customer_id = c.id;');
        $indexer->run();

        $indexer = $tnt->createIndex('pse.index');
        $indexer->query('SELECT pse.id AS id,
                                pse.ref AS ref
                                FROM product_sale_elements AS pse');
        $indexer->run();

        foreach ($langs as $lang) {
            $tnt = TntSearch::getTntSearch($lang->getLocale());

            $indexer = $tnt->createIndex('product_' . $lang->getLocale() . '.index');
            $indexer->query('SELECT p.id AS id, 
                                p.ref AS ref,
                                pse.ref AS pse_ref,
                                pi.title AS title, 
                                pi.chapo AS chapo, 
                                pi.description AS description, 
                                pi.postscriptum AS postscriptum
                                FROM product AS p 
                                LEFT JOIN product_i18n AS pi ON p.id = pi.id 
                                LEFT JOIN product_sale_elements AS pse ON p.id = pse.product_id
                                WHERE pi.locale=\'' . $lang->getLocale() . '\'');
            $indexer->run();

            $indexer = $tnt->createIndex('category_' . $lang->getLocale() . '.index');
            $indexer->query('SELECT c.id AS id,
                                ci.title AS title,
                                ci.chapo AS chapo,
                                ci.description AS description,
                                ci.postscriptum AS postscriptum
                                FROM category AS c LEFT JOIN category_i18n AS ci ON c.id = ci.id
                                WHERE ci.locale=\'' . $lang->getLocale() . '\';');
            $indexer->run();

            $indexer = $tnt->createIndex('content_' . $lang->getLocale() . '.index');
            $indexer->query('SELECT c.id AS id,
                                ci.title AS title,
                                ci.chapo AS chapo,
                                ci.description AS description,
                                ci.postscriptum AS postscriptum
                                FROM content AS c LEFT JOIN content_i18n AS ci ON c.id = ci.id
                                WHERE ci.locale=\'' . $lang->getLocale() . '\';');
            $indexer->run();

            $indexer = $tnt->createIndex('folder_' . $lang->getLocale() . '.index');
            $indexer->query('SELECT f.id AS id,
                                fi18n.title AS title,
                                fi18n.chapo AS chapo,
                                fi18n.description AS description,
                                fi18n.postscriptum AS postscriptum
                                FROM folder AS f LEFT JOIN folder_i18n AS fi18n ON f.id = fi18n.id
                                WHERE fi18n.locale=\'' . $lang->getLocale() . '\';');
            $indexer->run();

            $indexer = $tnt->createIndex('brand_' . $lang->getLocale() . '.index');
            $indexer->query('SELECT b.id AS id,
                                bi.title AS title,
                                bi.chapo AS chapo,
                                bi.description AS description,
                                bi.postscriptum AS postscriptum
                                FROM brand AS b LEFT JOIN brand_i18n AS bi ON b.id = bi.id
                                WHERE bi.locale=\'' . $lang->getLocale() . '\';');
            $indexer->run();
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            GenerateIndexesEvent::GENERATE_INDEXES => 'generateIndexes',
        ];
    }
}
