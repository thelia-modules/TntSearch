<?php

declare(strict_types=1);

namespace TntSearch\Tests\Integration\Search;

use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Filesystem\Filesystem;
use TeamTNT\TNTSearch\Exceptions\IndexNotFoundException;
use Thelia\Domain\Search\Contract\SearchableDocumentProviderInterface;
use Thelia\Domain\Search\Contract\SearchScope;
use Thelia\Domain\Search\Document\SearchDocument;
use Thelia\Domain\Search\Document\SearchHit;
use Thelia\Domain\Search\Document\SearchQuery;
use Thelia\Domain\Search\SearchDocumentProviders;
use Thelia\Domain\Search\SearchLocales;
use Thelia\Test\IntegrationTestCase;
use TntSearch\Search\CatalogueCompletion;
use TntSearch\Search\DocumentIndexes;
use TntSearch\Search\EngineOutage;
use TntSearch\Search\IncompleteLocaleException;
use TntSearch\Search\SearchMemo;
use TntSearch\Search\TermScorer;
use TntSearch\Search\TntSearchEngine;
use TntSearch\Service\Provider\TntSearchProvider;
use TntSearch\Service\Stemmer;
use TntSearch\Service\StopWord;
use TntSearch\TntSearch;

final class TntSearchEngineTest extends IntegrationTestCase
{
    private const string ENVIRONMENT = 'phpunit-core-engine';

    private const array WEIGHTS = ['title' => 10, 'chapo' => 5];

    private TntSearchEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        (new Filesystem())->remove(TntSearch::INDEXES_DIR.\DIRECTORY_SEPARATOR.self::ENVIRONMENT);
        $this->engine = self::engine(self::ENVIRONMENT);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove(TntSearch::INDEXES_DIR.\DIRECTORY_SEPARATOR.self::ENVIRONMENT);

        parent::tearDown();
    }

    /**
     * @param list<SearchableDocumentProviderInterface> $providers
     */
    public static function engine(string $environment, array $providers = [], ?DocumentIndexes $indexes = null, ?SearchMemo $memo = null, ?EngineOutage $outage = null): TntSearchEngine
    {
        return new TntSearchEngine($indexes ?? self::indexes($environment), new SearchDocumentProviders($providers), new TermScorer(), $memo ?? new SearchMemo(), new SearchLocales(), $outage ?? new EngineOutage(new ArrayAdapter()));
    }

    public static function indexes(string $environment): DocumentIndexes
    {
        $dispatcher = new EventDispatcher();
        $stemmer = new Stemmer($dispatcher);

        return new DocumentIndexes(new TntSearchProvider($stemmer, new StopWord($dispatcher), $environment), $stemmer, new StopWord($dispatcher));
    }

    public function testATitleWithTwoLettersSwappedFindsTheProduct(): void
    {
        $this->engine->index([self::product(4, 'Scarlett', 'Une lampe'), self::product(7, 'Chaise rouge')]);

        self::assertSame([4], self::ids($this->engine->search(self::query('Scralett'))->hits));
    }

    public function testEveryWordOfTheTermMustMatch(): void
    {
        $this->engine->index([self::product(7, 'Chaise rouge'), self::product(12, 'Table rouge'), self::product(9, 'Chaise bleue')]);

        self::assertSame([7], self::ids($this->engine->search(self::query('chaise rouge'))->hits));
    }

    public function testTheWeightOfAFieldDecidesWhichDocumentComesFirst(): void
    {
        $this->engine->index([self::product(7, 'Tabouret', 'Plus bas qu\'une chaise'), self::product(9, 'Chaise rouge', 'Assise confortable'), self::product(12, 'Table basse')]);

        self::assertSame([9, 7], self::ids($this->engine->search(self::query('chaise'))->hits));
    }

    public function testAnInvisibleDocumentIsOnlyFoundWhenTheQueryAsksForHiddenOnes(): void
    {
        $this->engine->index([self::product(4, 'Lampe Scarlett', visible: false), self::product(5, 'Lampe Ava')]);

        self::assertSame([5], self::ids($this->engine->search(self::query('lampe'))->hits));
        self::assertEqualsCanonicalizing([4, 5], self::ids($this->engine->search(new SearchQuery('lampe', 'fr_FR', visibleOnly: false))->hits));
    }

    public function testADocumentOfAnotherScopeIsNeverFound(): void
    {
        $this->engine->index([new SearchDocument('order', 3, null, SearchScope::BackOffice, true, ['ref' => 'ORD000123'])]);

        self::assertSame([], $this->engine->search(new SearchQuery('ORD000123', null))->hits);
        self::assertSame([3], self::ids($this->engine->search(new SearchQuery('ORD000123', null, scope: SearchScope::BackOffice))->hits));
    }

    public function testADocumentIsSearchedInItsOwnLocaleAndADocumentWithoutLocaleInAll(): void
    {
        $this->engine->index([
            self::product(4, 'Lampe Scarlett'),
            new SearchDocument('brand', 2, null, SearchScope::Shop, true, ['title' => 'Lampadaires Dupont'], self::WEIGHTS),
        ]);

        self::assertSame([], $this->engine->search(new SearchQuery('Scarlett', 'de_DE'))->hits);
        self::assertSame([2], self::ids($this->engine->search(new SearchQuery('Dupont', 'de_DE'))->hits));
    }

    public function testAnAlternativeTermFindsWhatTheTermAloneMisses(): void
    {
        $this->engine->index([self::product(4, 'Téléphone portable'), self::product(5, 'Tablette')]);

        self::assertSame([], $this->engine->search(self::query('mobile'))->hits);
        self::assertSame([4], self::ids($this->engine->search(self::query('mobile')->withAlternativeTerms(['téléphone']))->hits));
    }

    public function testIndexingTheSameDocumentTwiceLeavesASingleHitWithItsLatestText(): void
    {
        $this->engine->index([self::product(4, 'Lampe Scarlett')]);
        $this->engine->index([self::product(4, 'Applique Scarlett')]);

        $result = $this->engine->search(self::query('Scarlett'));

        self::assertSame([4], self::ids($result->hits));
        self::assertSame(1, $result->total);
        self::assertSame([], $this->engine->search(self::query('lampe'))->hits);
    }

    public function testARemovedDocumentIsNoLongerFoundInItsLocaleOnly(): void
    {
        $this->engine->index([self::product(4, 'Lampe Scarlett'), self::product(4, 'Lamp Scarlett', locale: 'en_US')]);

        $this->engine->remove('product', [4], 'fr_FR');

        self::assertSame([], $this->engine->search(self::query('Scarlett'))->hits);
        self::assertSame([4], self::ids($this->engine->search(new SearchQuery('Scarlett', 'en_US'))->hits));
    }

    public function testRemovingFromAnIndexNeverBuiltDoesNothing(): void
    {
        $this->engine->remove('product', [4], 'fr_FR');

        self::assertSame([], self::indexes(self::ENVIRONMENT)->files());
    }

    public function testAPurgeEmptiesTheFamilyAndOnlyIt(): void
    {
        $this->engine->index([
            self::product(4, 'Lampe Scarlett'),
            self::product(4, 'Lamp Scarlett', locale: 'en_US'),
            new SearchDocument('category', 8, 'fr_FR', SearchScope::Shop, true, ['title' => 'Lampes Scarlett'], self::WEIGHTS),
        ]);

        $this->engine->purge('product');

        self::assertSame([['category', 8]], array_map(static fn (SearchHit $hit): array => [$hit->family, $hit->id], $this->engine->search(self::query('Scarlett'))->hits));
        self::assertSame([], $this->engine->search(new SearchQuery('Scarlett', 'en_US'))->hits);
    }

    public function testTheFamiliesOfTheQueryNarrowTheSearch(): void
    {
        $this->engine->index([
            self::product(4, 'Lampe Scarlett'),
            new SearchDocument('category', 8, 'fr_FR', SearchScope::Shop, true, ['title' => 'Lampes'], self::WEIGHTS),
        ]);

        self::assertSame([4], self::ids($this->engine->search(new SearchQuery('lampe', 'fr_FR', ['product']))->hits));
    }

    public function testAPageOfHitsCarriesTheTotalOfTheMatches(): void
    {
        $this->engine->index(array_map(static fn (int $id): SearchDocument => self::product($id, 'Lampe '.$id), range(1, 5)));

        $result = $this->engine->search(new SearchQuery('lampe', 'fr_FR', limit: 2, offset: 2));

        self::assertCount(2, $result->hits);
        self::assertSame(5, $result->total);
    }

    public function testTheBestDocumentComesFirstAmongMoreMatchesThanTheLibraryKeepsPerWord(): void
    {
        $documents = array_map(static fn (int $id): SearchDocument => self::product($id, 'Article '.$id, 'Une lampe'), range(1, 1200));
        $documents[] = self::product(1201, 'Lampe de chevet', 'Une lampe');
        $documents[] = self::product(1202, 'Chaise');
        $this->engine->index($documents);

        $result = $this->engine->search(new SearchQuery('lampe', 'fr_FR', limit: 1));

        self::assertSame([1201], self::ids($result->hits));
        self::assertSame(1201, $result->total);
    }

    public function testAnIndexStillBeingCreatedFindsNothingWithoutFailing(): void
    {
        $this->engine->index([self::product(4, 'Lampe Scarlett')]);
        touch(TntSearch::INDEXES_DIR.\DIRECTORY_SEPARATOR.self::ENVIRONMENT.\DIRECTORY_SEPARATOR.'document--product--en_US.index');

        self::assertSame([], $this->engine->search(new SearchQuery('Scarlett', 'en_US'))->hits);
        self::assertSame([4], self::ids($this->engine->search(self::query('Scarlett'))->hits));
    }

    public function testANameThatCouldEscapeTheStorageIsRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->engine->purge('../product');
    }

    public function testALocaleIsSearchedOnceEveryFamilyHoldingEntitiesIsMarkedCompleteInIt(): void
    {
        $providers = [self::provider('category', [8]), self::provider('brand', [])];
        $memo = new SearchMemo();
        $engine = self::engine(self::ENVIRONMENT, $providers, null, $memo);
        $completion = new CatalogueCompletion(new SearchDocumentProviders($providers), new SearchLocales(), self::indexes(self::ENVIRONMENT), $memo);

        $engine->index([new SearchDocument('category', 8, 'fr_FR', SearchScope::Shop, true, ['title' => 'Lampes'], self::WEIGHTS)]);
        self::assertFalse($engine->isAvailable());

        $completion->mark(['category'], ['fr_FR']);
        self::assertTrue($engine->isAvailable());
        self::assertFalse($engine->isCompleteInEveryIndexedLocale());
        self::assertSame([8], self::ids($engine->search(new SearchQuery('lampes', 'fr_FR'))->hits));

        try {
            $engine->search(new SearchQuery('lampes', 'en_US'));
            self::fail('A locale not indexed to the end must leave the search to the database.');
        } catch (IncompleteLocaleException $exception) {
            self::assertSame('en_US', $exception->locale);
        }

        $completion->mark();
        self::assertTrue($engine->isCompleteInEveryIndexedLocale());
        self::assertSame([], $engine->search(new SearchQuery('lampes', 'en_US'))->hits);

        $engine->purge('category');
        self::assertFalse($engine->isAvailable());
    }

    public function testTheLastWordIsFoundByItsBeginning(): void
    {
        $this->engine->index([self::product(4, 'Chaussures de ville'), self::product(5, 'Chaise')]);

        self::assertSame([4], self::ids($this->engine->search(self::query('chau'))->hits));
        self::assertSame([4], self::ids($this->engine->search(self::query('ville chau'))->hits));
        self::assertSame([], $this->engine->search(self::query('chau ville'))->hits);
    }

    public function testTheBeginningOfAnAccentedWordFindsItInFrench(): void
    {
        $this->engine->index([self::product(4, 'Étagère murale'), self::product(5, 'Chaise')]);

        self::assertSame([4], self::ids($this->engine->search(self::query('étag'))->hits));
    }

    public function testAWordInTheTitleOutweighsTwoWordsInTheSummary(): void
    {
        $this->engine->index([
            new SearchDocument('product', 3, 'fr_FR', SearchScope::Shop, true, ['title' => 'Chaise', 'description' => 'rouge'], self::WEIGHTS),
            new SearchDocument('product', 9, 'fr_FR', SearchScope::Shop, true, ['title' => 'Tabouret', 'chapo' => 'chaise rouge'], self::WEIGHTS),
            self::product(12, 'Table'),
        ]);

        self::assertSame([3, 9], self::ids($this->engine->search(self::query('chaise rouge'))->hits));
    }

    public function testABrokenIndexStopsTheEngineForAWhileSoTheDatabaseAnswers(): void
    {
        $outage = new EngineOutage(new ArrayAdapter());
        $engine = self::engine(self::ENVIRONMENT, [], null, null, $outage);
        $engine->index([self::product(4, 'Lampe Scarlett')]);
        file_put_contents(TntSearch::INDEXES_DIR.\DIRECTORY_SEPARATOR.self::ENVIRONMENT.\DIRECTORY_SEPARATOR.'document--product--en_US.index', str_repeat('not a database ', 100));
        self::assertTrue($engine->isAvailable());

        try {
            $engine->search(new SearchQuery('lampe', 'en_US'));
            self::fail('A broken index must fail the search.');
        } catch (\PDOException) {
        }

        self::assertTrue($outage->isOngoing());
        self::assertFalse($engine->isAvailable());
    }

    public function testRemovingTheIndexesKeepsTheLockFilesAWriterMayHold(): void
    {
        $indexes = self::indexes(self::ENVIRONMENT);
        $engine = self::engine(self::ENVIRONMENT, [], $indexes);
        $engine->index([self::product(4, 'Lampe Scarlett')]);
        $indexes->markComplete('product', 'fr_FR');
        $lock = $indexes->storage().'document--product.lock';
        self::assertFileExists($lock);

        TntSearch::removeDocumentIndexes(self::ENVIRONMENT);

        self::assertFileExists($lock);
        self::assertFileDoesNotExist($indexes->storage().$indexes->fileName('product', 'fr_FR'));
        self::assertFalse($indexes->isComplete('product', 'fr_FR'));
    }

    public function testAnIndexDeletedAfterItWasMarkedCompleteLeavesItsLocaleToTheDatabase(): void
    {
        $providers = [self::provider('product', [4])];
        $memo = new SearchMemo();
        $indexes = self::indexes(self::ENVIRONMENT);
        $engine = self::engine(self::ENVIRONMENT, $providers, $indexes, $memo);
        $completion = new CatalogueCompletion(new SearchDocumentProviders($providers), new SearchLocales(), $indexes, $memo);

        $engine->index([self::product(4, 'Lampe Scarlett')]);
        $completion->mark();
        self::assertSame([], $engine->search(new SearchQuery('lampe', 'en_US'))->hits);

        unlink($indexes->storage().$indexes->fileName('product', 'fr_FR'));
        $memo->reset();

        try {
            $engine->search(self::query('lampe'));
            self::fail('A locale whose index file is gone must leave the search to the database.');
        } catch (IncompleteLocaleException $exception) {
            self::assertSame('fr_FR', $exception->locale);
        }

        self::assertSame([], $engine->search(new SearchQuery('lampe', 'en_US'))->hits);

        $engine->index([self::product(5, 'Lamp Owen', locale: 'en_US')]);
        unlink($indexes->storage().$indexes->fileName('product', 'en_US'));
        $memo->reset();

        $this->expectException(IncompleteLocaleException::class);
        $engine->search(new SearchQuery('lamp', 'en_US'));
    }

    public function testABusyIndexFailsOneSearchWithoutStoppingTheEngine(): void
    {
        $outage = new EngineOutage(new ArrayAdapter());
        $engine = self::engine(self::ENVIRONMENT, [], null, null, $outage);
        $engine->index([self::product(4, 'Lampe Scarlett')]);
        $holder = new \PDO('sqlite:'.TntSearch::INDEXES_DIR.\DIRECTORY_SEPARATOR.self::ENVIRONMENT.\DIRECTORY_SEPARATOR.'document--product--en_US.index');
        $holder->exec('PRAGMA locking_mode = EXCLUSIVE');
        $holder->exec('BEGIN EXCLUSIVE');

        try {
            $engine->search(new SearchQuery('lampe', 'en_US'));
            self::fail('A locked index must fail the search.');
        } catch (\PDOException) {
        } finally {
            $holder->exec('COMMIT');
            $holder = null;
        }

        self::assertFalse($outage->isOngoing());
    }

    public function testAWrongLetterOfACyrillicWordIsToleratedLikeALatinOne(): void
    {
        $this->engine->index([self::product(4, 'Стол'), self::product(7, 'Лампа')]);

        self::assertSame([4], self::ids($this->engine->search(self::query('Стул'))->hits));
    }

    public function testAWriterGivesUpWhenAnotherHoldsTheLockTooLong(): void
    {
        $indexes = self::indexes(self::ENVIRONMENT);
        $holder = fopen($indexes->storage().'document--product.lock', 'c');
        self::assertNotFalse($holder);
        flock($holder, \LOCK_EX);

        try {
            $indexes->exclusively('product', static fn (): bool => true, 100);
            self::fail('A writer must not wait for ever.');
        } catch (\RuntimeException $exception) {
            self::assertStringContainsString('cannot be locked', $exception->getMessage());
        } finally {
            flock($holder, \LOCK_UN);
            fclose($holder);
        }

        self::assertTrue($indexes->exclusively('product', static fn (): bool => true, 100));
    }

    public function testAnIndexRebuiltFromScratchIsReadAfresh(): void
    {
        $this->engine->index([self::product(4, 'Lampe Scarlett')]);
        self::assertSame([4], self::ids($this->engine->search(self::query('lampe'))->hits));

        $this->engine->purge('product');
        $this->engine->index([self::product(9, 'Lampe Ava')]);

        self::assertSame([9], self::ids($this->engine->search(self::query('lampe'))->hits));
    }

    public function testAShortWordIsNotFoundTwoLettersAway(): void
    {
        $this->engine->index([self::product(2, 'Table Diam 80'), self::product(5, 'Divan cuir')]);

        self::assertSame([5], self::ids($this->engine->search(self::query('divan'))->hits));
    }

    public function testTheExactWordComesBeforeAWordFoundDespiteATypo(): void
    {
        $this->engine->index([self::product(3, 'Tabouret Chaisse'), self::product(9, 'Chaise basse'), self::product(12, 'Table')]);

        self::assertSame([9, 3], self::ids($this->engine->search(self::query('chaise'))->hits));
    }

    public function testTheNextPageOfTheSameSearchDoesNotReadTheIndexAgainUntilAWrite(): void
    {
        $this->engine->index(array_map(static fn (int $id): SearchDocument => self::product($id, 'Lampe '.$id), range(1, 5)));
        $this->engine->search(new SearchQuery('lampe', 'fr_FR', limit: 2));
        (new Filesystem())->remove(glob(TntSearch::INDEXES_DIR.\DIRECTORY_SEPARATOR.self::ENVIRONMENT.\DIRECTORY_SEPARATOR.'document--*') ?: []);

        self::assertCount(2, $this->engine->search(new SearchQuery('lampe', 'fr_FR', limit: 2, offset: 2))->hits);

        $this->engine->index([self::product(9, 'Applique')]);

        self::assertSame([], $this->engine->search(new SearchQuery('lampe', 'fr_FR', limit: 2, offset: 2))->hits);
    }

    public function testAnIndexRemovedWhileSearchingFindsNothingWithoutFailing(): void
    {
        $indexes = $this->createStub(DocumentIndexes::class);
        $indexes->method('families')->willReturn(['product']);
        $indexes->method('reader')->willThrowException(new IndexNotFoundException('gone'));

        self::assertSame([], self::engine(self::ENVIRONMENT, [], $indexes)->search(self::query('lampe'))->hits);
    }

    public function testABlankTermFindsNothing(): void
    {
        $this->engine->index([self::product(4, 'Lampe Scarlett')]);

        self::assertSame([], $this->engine->search(self::query('  '))->hits);
    }

    private static function product(int $id, string $title, ?string $chapo = null, bool $visible = true, string $locale = 'fr_FR'): SearchDocument
    {
        return new SearchDocument('product', $id, $locale, SearchScope::Shop, $visible, array_filter(['title' => $title, 'chapo' => $chapo]), self::WEIGHTS);
    }

    /**
     * @param list<int> $ids
     */
    private static function provider(string $family, array $ids): SearchableDocumentProviderInterface
    {
        return new class($family, $ids) implements SearchableDocumentProviderInterface {
            /** @param list<int> $ids */
            public function __construct(private string $family, private array $ids)
            {
            }

            public function family(): string
            {
                return $this->family;
            }

            public function scope(): SearchScope
            {
                return SearchScope::Shop;
            }

            public function isTranslatable(): bool
            {
                return true;
            }

            public function fieldWeights(): array
            {
                return [];
            }

            public function idsAfter(int $afterId, int $limit): array
            {
                return \array_slice(array_values(array_filter($this->ids, static fn (int $id): bool => $id > $afterId)), 0, $limit);
            }

            public function documents(array $ids, ?string $locale): array
            {
                return [];
            }

            public function visibleIds(array $ids): array
            {
                return $ids;
            }
        };
    }

    private static function query(string $term): SearchQuery
    {
        return new SearchQuery($term, 'fr_FR');
    }

    /**
     * @param list<SearchHit> $hits
     *
     * @return list<int>
     */
    private static function ids(array $hits): array
    {
        return array_map(static fn (SearchHit $hit): int => $hit->id, $hits);
    }
}
