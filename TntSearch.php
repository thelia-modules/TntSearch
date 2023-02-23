<?php

namespace TntSearch;

use Propel\Runtime\Connection\ConnectionInterface;
use Thelia\Module\BaseModule;
use TntSearch\CompilerPass\IndexPass;

class TntSearch extends BaseModule
{
    /** @var string */
    const DOMAIN_NAME = 'tntsearch';

    /** @var string */
    const INDEXES_DIR = THELIA_LOCAL_DIR . "TNTIndexes";

    /** @var string */
    const ON_THE_FLY_UPDATE = 'tntsearch.on_the_fly_update';

    public function postActivation(ConnectionInterface $con = null)
    {
        self::setConfigValue(self::ON_THE_FLY_UPDATE, false);
    }

    public function update($currentVersion, $newVersion, ConnectionInterface $con = null)
    {
        if (version_compare($currentVersion, '0.7.0') === -1) {
            self::setConfigValue(self::ON_THE_FLY_UPDATE, true);
        }
    }

    public static function getCompilers()
    {
        return [
            new IndexPass()
        ];
    }
}
