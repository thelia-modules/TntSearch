<?php

namespace TntSearch\Connector;

use Propel\Runtime\Connection\PdoConnection;
use Propel\Runtime\Propel;
use TeamTNT\TNTSearch\Connectors\Connector;
use TeamTNT\TNTSearch\Connectors\ConnectorInterface;

class PropelConnector extends Connector implements ConnectorInterface
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