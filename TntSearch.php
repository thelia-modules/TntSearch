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

    public static function getTntSearch($locale = null)
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

        switch ($locale) {
            case 'fr_FR':
                $stemmer = \TntSearch\Stemmer\FrenchStemmer::class;
                break;
            case 'it_IT':
                $stemmer = \TeamTNT\TNTSearch\Stemmer\ItalianStemmer::class;
                break;
            case 'de_DE':
                $stemmer = \TeamTNT\TNTSearch\Stemmer\GermanStemmer::class;
                break;
            case 'pt_PT':
                $stemmer = \TeamTNT\TNTSearch\Stemmer\PortugueseStemmer::class;
                break;

            default:
                $stemmer = \TeamTNT\TNTSearch\Stemmer\PorterStemmer::class;
        }

        $config = [
            'driver' => $driver,
            'host' => $host,
            'charset' => 'utf8',
            'database' => $database,
            'username' => $user,
            'password' => $password,
            'storage' => self::INDEXES_DIR,
            'stemmer' => $stemmer
        ];

        $tnt = new \TeamTNT\TNTSearch\TNTSearch();
        $tnt->loadConfig($config);

        return $tnt;
    }
}
