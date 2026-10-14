<?php

declare(strict_types=1);

namespace TntSearch\Tests\Integration;

use FlexyBundle\Search\ProductSearchEngineInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Filesystem\Filesystem;
use Thelia\Test\IntegrationTestCase;
use TntSearch\Index\Product as ProductIndex;
use TntSearch\Search\FlexyProductSearchEngine;
use TntSearch\Service\Provider\TntSearchProvider;
use TntSearch\Service\Stemmer;
use TntSearch\Service\StopWord;
use TntSearch\TntSearch;

/**
 * The Flexy search answered from a real product index, built in a storage of its own from literal rows: the
 * indexer reads them through the database connection, no table is read nor written.
 */
final class FlexyProductSearchEngineTest extends IntegrationTestCase
{
    private const ENVIRONMENT = 'phpunit-flexy-engine';

    private const LOCALE = 'fr_FR';

    private FlexyProductSearchEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();

        if (!interface_exists(ProductSearchEngineInterface::class)) {
            self::markTestSkipped('The Flexy theme does not ship the search engine interface.');
        }

        $dispatcher = new EventDispatcher();
        $provider = new TntSearchProvider(new Stemmer($dispatcher), new StopWord($dispatcher), self::ENVIRONMENT);

        $index = new LiteralRowsProductIndex($dispatcher, $provider);
        // The indexer reports its progress on the standard output.
        ob_start();
        $index->buildFor(self::LOCALE);
        ob_end_clean();

        $this->engine = new FlexyProductSearchEngine($provider, $index);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove(TntSearch::INDEXES_DIR.\DIRECTORY_SEPARATOR.self::ENVIRONMENT);

        parent::tearDown();
    }

    public function testATitleWithTwoLettersSwappedFindsTheProduct(): void
    {
        self::assertSame([4], $this->engine->productIds('Scralett', self::LOCALE, 100));
    }

    public function testEveryWordOfTheTermMustMatch(): void
    {
        self::assertSame([7], $this->engine->productIds('chaise rouge', self::LOCALE, 100));
    }

    public function testTheMostRelevantProductComesFirst(): void
    {
        // "chaise" in the title (weight 10) beats "chaise" in the description only.
        self::assertSame([7, 9], $this->engine->productIds('chaise', self::LOCALE, 100));
    }

    public function testTheLimitIsHonoured(): void
    {
        self::assertSame([7], $this->engine->productIds('chaise', self::LOCALE, 1));
    }

    public function testAnUnknownWordFindsNothing(): void
    {
        self::assertSame([], $this->engine->productIds('xylophone', self::LOCALE, 100));
    }

    public function testALocaleWithoutIndexFindsNothingWithoutFailing(): void
    {
        self::assertSame([], $this->engine->productIds('Scarlett', 'de_DE', 100));
    }

    public function testABlankTermFindsNothing(): void
    {
        self::assertSame([], $this->engine->productIds('  ', self::LOCALE, 100));
    }
}

/**
 * The product index of the module (its name, weights and tokenizer), fed with literal rows instead of the catalogue.
 * Named Product so its index files are those the engine reads.
 */
final class LiteralRowsProductIndex extends ProductIndex
{
    public function getIndexName(): string
    {
        return 'product';
    }

    public function buildSqlQuery(?int $itemId = null, ?string $locale = null): string
    {
        return "SELECT 4 AS id, 'Scarlett' AS title, 'Une lampe' AS description"
            ." UNION ALL SELECT 7, 'Chaise rouge', 'Assise confortable'"
            ." UNION ALL SELECT 9, 'Tabouret', 'Plus bas qu''une chaise'"
            ." UNION ALL SELECT 12, 'Table rouge', 'Plateau en chêne'";
    }

    public function buildFor(string $locale): void
    {
        $this->indexOneIndex($locale);
    }
}
