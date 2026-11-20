<?php

declare(strict_types=1);

namespace TntSearch\Tests\Integration\Search;

use Psr\Log\AbstractLogger;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Transport\Receiver\MessageCountAwareInterface;
use Symfony\Component\Messenger\Transport\Receiver\ReceiverInterface;
use Thelia\Command\SearchReindexCommand;
use Thelia\Domain\Search\Message\IndexDocument;
use Thelia\Domain\Search\Provider\BrandDocumentProvider;
use Thelia\Domain\Search\SearchDocumentProviders;
use Thelia\Domain\Search\SearchLocales;
use Thelia\Test\IntegrationTestCase;
use TntSearch\EventListener\FailedCatalogueBatchListener;
use TntSearch\Search\CatalogueCompletion;
use TntSearch\Search\DocumentIndexes;
use TntSearch\Search\Message\MarkCatalogueIndexed;
use TntSearch\Search\Message\MarkCatalogueIndexedHandler;
use TntSearch\Search\SearchMemo;
use TntSearch\TntSearch;

final class MarkCatalogueIndexedHandlerTest extends IntegrationTestCase
{
    private const string ENVIRONMENT = 'phpunit-mark-catalogue-indexed';

    private DocumentIndexes $indexes;

    /** @var list<Envelope> */
    private array $dispatched = [];

    /** @var list<string> */
    private array $warnings = [];

    protected function setUp(): void
    {
        parent::setUp();
        (new Filesystem())->remove(TntSearch::INDEXES_DIR.\DIRECTORY_SEPARATOR.self::ENVIRONMENT);
        $this->indexes = TntSearchEngineTest::indexes(self::ENVIRONMENT);
        $this->dispatched = [];
        $this->warnings = [];
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove(TntSearch::INDEXES_DIR.\DIRECTORY_SEPARATOR.self::ENVIRONMENT);

        parent::tearDown();
    }

    public function testBatchesStillWaitingInTheQueuePostponeTheMarker(): void
    {
        $this->handler(waitingMessages: 3)(new MarkCatalogueIndexed(['brand'], ['en_US']));

        self::assertFalse($this->indexes->isComplete('brand', 'en_US'));
        self::assertCount(1, $this->dispatched);
        $postponed = $this->dispatched[0]->getMessage();
        self::assertInstanceOf(MarkCatalogueIndexed::class, $postponed);
        self::assertSame(1, $postponed->postponements);
        self::assertSame(MarkCatalogueIndexedHandler::DELAY_MILLISECONDS, $this->dispatched[0]->last(DelayStamp::class)?->getDelay());
    }

    public function testAnEmptyQueueLetsTheMarkerBePosed(): void
    {
        $this->handler(waitingMessages: 0)(new MarkCatalogueIndexed(['brand'], ['en_US']));

        self::assertTrue($this->indexes->isComplete('brand', 'en_US'));
        self::assertSame([], $this->dispatched);
    }

    public function testTheMarkerGivesUpWithoutMarkingAfterTooManyChecks(): void
    {
        $this->handler(waitingMessages: 1)(new MarkCatalogueIndexed(['brand'], ['en_US'], MarkCatalogueIndexedHandler::MAX_POSTPONEMENTS));

        self::assertFalse($this->indexes->isComplete('brand', 'en_US'));
        self::assertSame([], $this->dispatched);
    }

    public function testABatchThatFailedForGoodForgetsTheCompletionOfItsFamily(): void
    {
        $this->indexes->markComplete('brand', 'en_US');
        $listener = new FailedCatalogueBatchListener($this->indexes, new SearchMemo());

        $retried = new WorkerMessageFailedEvent(new Envelope(new IndexDocument('brand', [1])), SearchReindexCommand::QUEUE_TRANSPORT, new \RuntimeException());
        $retried->setForRetry();
        $listener->forgetTheCompletionOfItsFamily($retried);

        self::assertTrue($this->indexes->isComplete('brand', 'en_US'));

        $listener->forgetTheCompletionOfItsFamily(new WorkerMessageFailedEvent(new Envelope(new IndexDocument('brand', [1])), SearchReindexCommand::QUEUE_TRANSPORT, new \RuntimeException()));

        self::assertFalse($this->indexes->isComplete('brand', 'en_US'));
    }

    public function testAnEverydayWriteThatFailedForGoodLeavesItsFamilyComplete(): void
    {
        $this->indexes->markComplete('brand', 'en_US');
        $listener = new FailedCatalogueBatchListener($this->indexes, new SearchMemo());

        $listener->forgetTheCompletionOfItsFamily(new WorkerMessageFailedEvent(new Envelope(new IndexDocument('brand', [1])), 'async', new \RuntimeException()));

        self::assertTrue($this->indexes->isComplete('brand', 'en_US'));
        self::assertFalse($this->indexes->hasFailed('brand'));
    }

    public function testABatchThatFailedDuringTheRebuildKeepsItsFamilyUnmarkedUntilTheNextRebuild(): void
    {
        $listener = new FailedCatalogueBatchListener($this->indexes, new SearchMemo());
        $listener->forgetTheCompletionOfItsFamily(new WorkerMessageFailedEvent(new Envelope(new IndexDocument('brand', [1])), SearchReindexCommand::QUEUE_TRANSPORT, new \RuntimeException()));

        $this->handler(waitingMessages: 0)(new MarkCatalogueIndexed(['brand'], ['en_US']));

        self::assertFalse($this->indexes->isComplete('brand', 'en_US'));
        self::assertCount(1, $this->warnings);

        $this->completion()->startRebuild();
        $this->handler(waitingMessages: 0)(new MarkCatalogueIndexed(['brand'], ['en_US']));

        self::assertTrue($this->indexes->isComplete('brand', 'en_US'));
    }

    private function completion(): CatalogueCompletion
    {
        return new CatalogueCompletion(new SearchDocumentProviders([new BrandDocumentProvider()]), new SearchLocales(), $this->indexes, new SearchMemo());
    }

    private function handler(int $waitingMessages): MarkCatalogueIndexedHandler
    {
        $dispatched = &$this->dispatched;
        $receiver = new class($waitingMessages) implements ReceiverInterface, MessageCountAwareInterface {
            public function __construct(private int $waitingMessages)
            {
            }

            public function get(): iterable
            {
                return [];
            }

            public function ack(Envelope $envelope): void
            {
            }

            public function reject(Envelope $envelope): void
            {
            }

            public function getMessageCount(): int
            {
                return $this->waitingMessages;
            }
        };

        $warnings = &$this->warnings;

        return new MarkCatalogueIndexedHandler(
            $this->completion(),
            new class($dispatched) implements MessageBusInterface {
                /** @param list<Envelope> $dispatched */
                public function __construct(private array &$dispatched)
                {
                }

                public function dispatch(object $message, array $stamps = []): Envelope
                {
                    $envelope = Envelope::wrap($message, $stamps);
                    $this->dispatched[] = $envelope;

                    return $envelope;
                }
            },
            new ServiceLocator([SearchReindexCommand::QUEUE_TRANSPORT => static fn () => $receiver]),
            new class($warnings) extends AbstractLogger {
                /** @param list<string> $warnings */
                public function __construct(private array &$warnings)
                {
                }

                public function log($level, \Stringable|string $message, array $context = []): void
                {
                    $this->warnings[] = (string) $message;
                }
            },
        );
    }
}
