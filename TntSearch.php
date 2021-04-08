<?php
/*************************************************************************************/
/*      This file is part of the Thelia package.                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : dev@thelia.net                                                       */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE.txt  */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

namespace TntSearch;

use Propel\Runtime\Connection\ConnectionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;
use Thelia\Module\BaseModule;
use TntSearch\Event\GenerateIndexesEvent;

class TntSearch extends BaseModule
{
    /** @var string */
    const DOMAIN_NAME = 'tntsearch';

    const INDEXES_DIR = THELIA_LOCAL_DIR . "TNTIndexes";

    public function postActivation(ConnectionInterface $con = null)
    {
        if (!is_dir($this::INDEXES_DIR)) {
            $this->getDispatcher()->dispatch(
                GenerateIndexesEvent::GENERATE_INDEXES,
                new GenerateIndexesEvent()
            );
        }
    }

    public static function generateMissingIndex($indexName, $locale, $tnt = null)
    {

        $fs = new Filesystem();

        if (!is_dir(self::INDEXES_DIR)) {
            $fs->mkdir(self::INDEXES_DIR);
        }

        if (null === $tnt){
            $tnt = self::getTntSearch();
        }

        switch ($indexName){
            case "customer":
                self::generateCustomerIndex($tnt);
                break;
            case "order":
                self::generateOrderIndex($tnt);
                break;
            case "pse":
                self::generatePseIndex($tnt);
                break;
            case "product":
                self::generateProductIndex($tnt, $locale);
                break;
            case "category":
                self::generateCategoryIndex($tnt, $locale);
                break;
            case "content":
                self::generateContentIndex($tnt, $locale);
                break;
            case "folder":
                self::generateFolderIndex($tnt, $locale);
                break;
            case "brand":
                self::generateBrandIndex($tnt, $locale);
                break;
        }
    }

    public static function generateCustomerIndex(\TeamTNT\TNTSearch\TNTSearch $tnt)
    {
        $indexer = $tnt->createIndex('customer.index');
        $indexer->query('SELECT id, ref, firstname, lastname, email FROM customer;');
        $indexer->run();
    }

    public static function generateOrderIndex(\TeamTNT\TNTSearch\TNTSearch $tnt)
    {
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
    }

    public static function generatePseIndex(\TeamTNT\TNTSearch\TNTSearch $tnt)
    {
        $indexer = $tnt->createIndex('pse.index');
        $indexer->query('SELECT pse.id AS id,
                                pse.ref AS ref
                                FROM product_sale_elements AS pse');
        $indexer->run();
    }

    public static function generateProductIndex(\TeamTNT\TNTSearch\TNTSearch $tnt, $locale)
    {
        $indexer = $tnt->createIndex('product_' . $locale . '.index');
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
                                WHERE pi.locale=\'' . $locale . '\';');
        $indexer->run();
    }

    public static function generateCategoryIndex(\TeamTNT\TNTSearch\TNTSearch $tnt, $locale)
    {
        $indexer = $tnt->createIndex('category_' . $locale . '.index');
        $indexer->query('SELECT c.id AS id,
                                ci.title AS title,
                                ci.chapo AS chapo,
                                ci.description AS description,
                                ci.postscriptum AS postscriptum
                                FROM category AS c LEFT JOIN category_i18n AS ci ON c.id = ci.id
                                WHERE ci.locale=\'' . $locale . '\';');
        $indexer->run();
    }

    public static function generateContentIndex(\TeamTNT\TNTSearch\TNTSearch $tnt, $locale)
    {
        $indexer = $tnt->createIndex('content_' . $locale . '.index');
        $indexer->query('SELECT c.id AS id,
                                ci.title AS title,
                                ci.chapo AS chapo,
                                ci.description AS description,
                                ci.postscriptum AS postscriptum
                                FROM content AS c LEFT JOIN content_i18n AS ci ON c.id = ci.id
                                WHERE ci.locale=\'' . $locale . '\';');
        $indexer->run();
    }
    public static function generateFolderIndex(\TeamTNT\TNTSearch\TNTSearch $tnt, $locale)
    {
        $indexer = $tnt->createIndex('folder_' . $locale . '.index');
        $indexer->query('SELECT f.id AS id,
                                fi18n.title AS title,
                                fi18n.chapo AS chapo,
                                fi18n.description AS description,
                                fi18n.postscriptum AS postscriptum
                                FROM folder AS f LEFT JOIN folder_i18n AS fi18n ON f.id = fi18n.id
                                WHERE fi18n.locale=\'' . $locale . '\';');
        $indexer->run();
    }

    public static function generateBrandIndex(\TeamTNT\TNTSearch\TNTSearch $tnt, $locale)
    {
        $indexer = $tnt->createIndex('brand_' . $locale . '.index');
        $indexer->query('SELECT b.id AS id,
                                bi.title AS title,
                                bi.chapo AS chapo,
                                bi.description AS description,
                                bi.postscriptum AS postscriptum
                                FROM brand AS b LEFT JOIN brand_i18n AS bi ON b.id = bi.id
                                WHERE bi.locale=\'' . $locale . '\';');
        $indexer->run();
    }

    public static function getTntSearch()
    {
        $configFile = THELIA_CONF_DIR . "database.yml";

        $propelParameters = Yaml::parse(file_get_contents($configFile))['database']['connection'];

        $driver = $propelParameters['driver'];
        $user = $propelParameters['user'];
        $password = $propelParameters['password'];

        $explodeDns = explode(';', $propelParameters['dsn']);
        $arrayDns = [];
        foreach ($explodeDns as $param) {
            $value = explode('=', $param);
            $arrayDns[$value[0]] = $value[1];
        }
        $host = $arrayDns['mysql:host'];
        $database = $arrayDns['dbname'];

        if (!is_dir(self::INDEXES_DIR)) {
            $fs = new Filesystem();
            $fs->mkdir(self::INDEXES_DIR);
        }

        $config = [
            'driver' => $driver,
            'host' => $host,
            'database' => $database,
            'username' => $user,
            'password' => $password,
            'storage' => self::INDEXES_DIR,
        ];

        $tnt = new \TeamTNT\TNTSearch\TNTSearch();
        $tnt->loadConfig($config);

        return $tnt;
    }
}