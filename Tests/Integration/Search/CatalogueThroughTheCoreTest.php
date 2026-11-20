<?php

declare(strict_types=1);

namespace TntSearch\Tests\Integration\Search;

use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;
use Thelia\Domain\Search\Document\SearchHit;
use Thelia\Domain\Search\Document\SearchQuery;
use Thelia\Domain\Search\Provider\ProductDocumentProvider;
use Thelia\Domain\Search\SearchDocumentProviders;
use Thelia\Domain\Search\SearchEngineRegistry;
use Thelia\Domain\Search\SearchIndexer;
use Thelia\Domain\Search\SearchLocales;
use Thelia\Domain\Search\SearchService;
use Thelia\Model\Product;
use Thelia\Test\FixtureFactory;
use Thelia\Test\IntegrationTestCase;
use TntSearch\TntSearch;

final class CatalogueThroughTheCoreTest extends IntegrationTestCase
{
    private const string ENVIRONMENT = 'phpunit-core-catalogue';

    private FixtureFactory $fixtures;
    private SearchIndexer $indexer;
    private SearchService $search;

    protected function setUp(): void
    {
        parent::setUp();
        (new Filesystem())->remove(TntSearch::INDEXES_DIR.\DIRECTORY_SEPARATOR.self::ENVIRONMENT);
        $this->fixtures = $this->createFixtureFactory();

        $engines = new SearchEngineRegistry([TntSearchEngineTest::engine(self::ENVIRONMENT)]);
        $providers = new SearchDocumentProviders([new ProductDocumentProvider()]);
        $this->indexer = new SearchIndexer($engines, $providers, new SearchLocales(), new NullLogger());
        $this->search = new SearchService($engines, $providers, [], [], new NullLogger());
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove(TntSearch::INDEXES_DIR.\DIRECTORY_SEPARATOR.self::ENVIRONMENT);

        parent::tearDown();
    }

    public function testAnInvisibleProductNeverComesBackFromAShopSearch(): void
    {
        $shown = $this->product('Applique Zéphyrine', 1);
        $hidden = $this->product('Lampe Zéphyrine', 0);

        $this->indexer->synchronize('product', [$shown->getId(), $hidden->getId()], ['en_US']);

        self::assertSame([$shown->getId()], $this->ids('Zéphyrine'));
    }

    public function testAProductHiddenAfterItsIndexationDisappearsFromTheShopSearch(): void
    {
        $product = $this->product('Lampe Zéphyrine', 1);
        $this->indexer->synchronize('product', [$product->getId()], ['en_US']);

        $product->setVisible(0)->save();
        $this->indexer->synchronize('product', [$product->getId()], ['en_US']);

        self::assertSame([], $this->ids('Zéphyrine'));
    }

    public function testAProductIsFoundByAMisspelledTitleAndByTheValueOfAFeature(): void
    {
        $product = $this->product('Applique Zéphyrine', 1);
        $this->fixtures->featureProduct($product, $this->fixtures->featureAv($this->fixtures->feature(), ['title' => 'Laiton']));

        $this->indexer->synchronize('product', [$product->getId()], ['en_US']);

        self::assertSame([$product->getId()], $this->ids('Zéphirine'));
        self::assertSame([$product->getId()], $this->ids('laiton'));
    }

    public function testADeletedProductLeavesTheIndex(): void
    {
        $product = $this->product('Lampe Zéphyrine', 1);
        $this->indexer->synchronize('product', [$product->getId()], ['en_US']);
        $id = $product->getId();

        $product->delete();
        $this->indexer->remove('product', [$id]);

        self::assertSame([], $this->ids('Zéphyrine'));
    }

    /**
     * @return list<int>
     */
    private function ids(string $term): array
    {
        return array_map(static fn (SearchHit $hit): int => $hit->id, $this->search->search(new SearchQuery($term, 'en_US', ['product']))?->hits ?? []);
    }

    private function product(string $title, int $visible): Product
    {
        return $this->fixtures->product($this->fixtures->category(), $this->fixtures->taxRule(), $this->fixtures->currency(), ['title' => $title, 'locale' => 'en_US', 'visible' => $visible]);
    }
}
