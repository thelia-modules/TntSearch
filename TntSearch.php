<?php

namespace TntSearch;

use Propel\Runtime\Connection\ConnectionInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;
use Symfony\Component\Finder\Finder;
use Thelia\Install\Database;
use Thelia\Module\BaseModule;
use TntSearch\CompilerPass\IndexPass;
use TntSearch\Index\BaseIndex;

class TntSearch extends BaseModule
{
    /** @var string */
    const DOMAIN_NAME = 'tntsearch';

    /** @var string */
    const INDEXES_DIR = THELIA_LOCAL_DIR . "TNTIndexes";

    /** @var string */
    const ON_THE_FLY_UPDATE = 'tntsearch.on_the_fly_update';

    public function postActivation(ConnectionInterface $con = null): void
    {
        self::setConfigValue(self::ON_THE_FLY_UPDATE, false);
        if (!self::getConfigValue('is_initialized', false)) {
            $database = new Database($con);
            $database->insertSql(null, [__DIR__.'/Config/TheliaMain.sql']);
            self::setConfigValue('is_initialized', true);
        }

    }

    public function update($currentVersion, $newVersion, ConnectionInterface $con = null): void
    {
        if (version_compare($currentVersion, '0.7.0') === -1) {
            self::setConfigValue(self::ON_THE_FLY_UPDATE, true);
        }

        $finder = Finder::create()
            ->name('*.sql')
            ->depth(0)
            ->sortByName()
            ->in(__DIR__ . DS . 'Config' . DS . 'update');

        $database = new Database($con);

        /** @var \SplFileInfo $file */
        foreach ($finder as $file) {
            if (version_compare($currentVersion, $file->getBasename('.sql'), '<')) {
                $database->insertSql(null, [$file->getPathname()]);
            }
        }
    }

    /**
     * @return IndexPass[]
     */
    public static function getCompilers(): array
    {
        return [
            new IndexPass()
        ];
    }

    /**
     * @param ServicesConfigurator $servicesConfigurator
     * @return void
     */
    public static function configureServices(ServicesConfigurator $servicesConfigurator): void
    {
        $servicesConfigurator->load(self::getModuleCode() . '\\', __DIR__)
            ->exclude([THELIA_MODULE_DIR . ucfirst(self::getModuleCode()) . '/I18n/*'])
            ->autowire()
            ->autoconfigure();
    }

    /**
     * @param ContainerBuilder $containerBuilder
     * @return void
     */
    public static function loadConfiguration(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->registerForAutoconfiguration(BaseIndex::class)
            ->setPublic(true)
            ->setShared(false)
            ->setParent("tntsearch.base.index")
            ->addTag('tntsearch.index');
    }
}