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