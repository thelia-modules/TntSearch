<?php

namespace TntSearch;

use FlexyBundle\Search\ProductSearchEngineInterface;
use Propel\Runtime\Connection\ConnectionInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;
use Symfony\Component\Finder\Finder;
use Thelia\Core\Install\Database;
use Thelia\Log\Tlog;
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

    public function postActivation(?ConnectionInterface $con = null): void
    {
        // Flexy searches through the indexes: a product saved in the back-office must be findable right away, so the
        // real-time update is on by default. A choice the merchant already made is kept.
        if (null === self::getConfigValue(self::ON_THE_FLY_UPDATE)) {
            self::setConfigValue(self::ON_THE_FLY_UPDATE, true);
        }
        if (!self::getConfigValue('is_initialized', false)) {
            $database = new Database($con);
            $database->insertSql(null, [__DIR__.'/Config/TheliaMain.sql']);
            self::setConfigValue('is_initialized', true);
        }

        $this->buildIndexes();
    }

    /**
     * The front search (Flexy) reads the indexes and never builds them. When the container already knows the
     * module (install, module:post-activate-all) they are built here; on an activation from the back-office the
     * running container predates the module, and IndexBootstrapListener builds them at the end of the next
     * back-office request. A failure is logged and never blocks the activation.
     */
    private function buildIndexes(): void
    {
        if (!$this->hasContainer() || !$this->getContainer()->has('tntsearch.indexation.provider')) {
            return;
        }

        try {
            $this->getContainer()->get('tntsearch.indexation.provider')->indexAll();
        } catch (\Throwable $exception) {
            Tlog::getInstance()->addError('TntSearch: the indexes could not be built at activation: '.$exception->getMessage());
        }
    }

    public function update($currentVersion, $newVersion, ?ConnectionInterface $con = null): void
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
        $excluded = [__DIR__.'/I18n/*', __DIR__.'/Tests/*'];

        // The Flexy search engine only exists when the Flexy theme ships its interface: the module boots without it.
        if (!interface_exists(ProductSearchEngineInterface::class)) {
            $excluded[] = __DIR__.'/Search/*';
        }

        $servicesConfigurator->load(self::getModuleCode() . '\\', __DIR__)
            ->exclude($excluded)
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