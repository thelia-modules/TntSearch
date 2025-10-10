<?php

namespace TntSearch\Connector;

use TeamTNT\TNTSearch\Connectors\Connector;
use TeamTNT\TNTSearch\Connectors\ConnectorInterface;
use Thelia\Config\DatabaseConfigurationSource;

class PropelConnector extends Connector implements ConnectorInterface
{
    /**
     * Establish a database connection to use propel
     */
    public function connect(array $config): \PDO
    {
        $definePropel = new DatabaseConfigurationSource(
            $this->getEnvParameters()
        );

        return $definePropel->getTheliaConnectionPDO();
    }

    private function getEnvParameters(): array
    {
        $parameters = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'DATABASE_')) {
                $parameters['thelia.' . strtolower($key)] = $value;
            }
        }

        return $parameters;
    }
}