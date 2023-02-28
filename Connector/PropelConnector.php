<?php

namespace TntSearch\Connector;

use Propel\Runtime\Connection\PdoConnection;
use Propel\Runtime\Propel;
use TeamTNT\TNTSearch\Connectors\Connector;

class PropelConnector extends Connector
{
    /**
     * Establish a database connection to use propel
     *
     * @param array $config
     * @return PdoConnection
     */
    public function connect(array $config): PdoConnection
    {
        return Propel::getConnection()->getWrappedConnection();
    }
}