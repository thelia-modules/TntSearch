<?php

declare(strict_types=1);

namespace TntSearch\Tests\Integration\Synonym;

use Propel\Runtime\Propel;
use Symfony\Component\Filesystem\Filesystem;
use Thelia\Model\SearchSynonymQuery;
use Thelia\Test\IntegrationTestCase;
use TntSearch\Model\TntSynonym;
use TntSearch\Model\TntSynonymQuery;
use TntSearch\TntSearch;

final class UpgradeToTheCoreSearchTest extends IntegrationTestCase
{
    private const string ENVIRONMENT = 'phpunit-upgrade';

    private string $storage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->storage = TntSearch::INDEXES_DIR.\DIRECTORY_SEPARATOR.self::ENVIRONMENT.\DIRECTORY_SEPARATOR;
        (new Filesystem())->mkdir($this->storage);
        TntSynonymQuery::create()->deleteAll();
        SearchSynonymQuery::create()->deleteAll();
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->storage);

        parent::tearDown();
    }

    public function testUpgradingFrom4CopiesTheSynonymsAndDropsTheOldCatalogueIndexes(): void
    {
        foreach (['product_fr_FR.index', 'product_fr_FR.index-wal', 'brand_en_US.index', 'customer.index', 'document--product--fr_FR.index'] as $file) {
            touch($this->storage.$file);
        }
        (new TntSynonym())->setGroupId(1)->setTerm('téléphone')->setPosition(1)->setEnabled(true)->save();
        (new TntSynonym())->setGroupId(1)->setTerm('tel')->setPosition(2)->setEnabled(true)->save();

        $module = new TntSearch();
        $module->setDispatcher(static::getContainer()->get('event_dispatcher'));
        $module->update('4.3.1', '5.0.0', Propel::getConnection());

        self::assertSame(['customer.index', 'document--product--fr_FR.index'], array_values(array_diff(scandir($this->storage) ?: [], ['.', '..'])));
        self::assertTrue(SearchSynonymQuery::create()->filterByTerm('téléphone')->exists());
    }
}
