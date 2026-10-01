<?php

declare(strict_types=1);

namespace TntSearch\Tests\Integration;

use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Thelia\Core\Event\Product\ProductSearchedEvent;
use Thelia\Test\IntegrationTestCase;
use TntSearch\Model\TntSearchLog;
use TntSearch\Model\TntSearchLogQuery;

/**
 * A front theme that searches the catalogue without the module (Flexy) raises ProductSearchedEvent: the search lands
 * in the log on the product index, where the back-office report of the searches without result reads it.
 */
final class LogShopSearchTest extends IntegrationTestCase
{
    private const TERM = 'tntsearch-test-licorne';

    protected function setUp(): void
    {
        parent::setUp();

        if (!class_exists(ProductSearchedEvent::class)) {
            self::markTestSkipped('The core does not ship ProductSearchedEvent.');
        }
    }

    public function testAShopSearchIsLoggedOnTheProductIndex(): void
    {
        $this->search(self::TERM, 'fr_FR', 0);

        $entry = $this->entry(self::TERM, 'fr_FR');

        self::assertSame(0, $entry->getNumHits());
        self::assertSame(1, $entry->getSearchCount());
    }

    public function testTheSameShopSearchAgainIsCountedOnTheSameLine(): void
    {
        $this->search(self::TERM, 'fr_FR', 0);
        $this->search(self::TERM, 'fr_FR', 2);

        $entry = $this->entry(self::TERM, 'fr_FR');

        self::assertSame(2, $entry->getSearchCount());
        self::assertSame(2, $entry->getNumHits(), 'The hits are those of the latest search.');
    }

    public function testTheSameTermInAnotherLanguageIsAnotherLine(): void
    {
        $this->search(self::TERM, 'fr_FR', 0);
        $this->search(self::TERM, 'en_US', 0);

        self::assertSame(1, $this->entry(self::TERM, 'fr_FR')->getSearchCount());
        self::assertSame(1, $this->entry(self::TERM, 'en_US')->getSearchCount());
    }

    private function search(string $term, string $locale, int $hits): void
    {
        $this->getService(EventDispatcherInterface::class)->dispatch(new ProductSearchedEvent($term, $locale, $hits));
    }

    private function entry(string $term, string $locale): TntSearchLog
    {
        $entry = TntSearchLogQuery::create()->findOneBySearchWordsAndLocaleAndIndex($term, $locale, 'product');

        self::assertNotNull($entry, \sprintf('"%s" (%s) is missing from the search log.', $term, $locale));

        return $entry;
    }
}
