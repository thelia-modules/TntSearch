<?php

declare(strict_types=1);

namespace TntSearch\Search\Message;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;
use Symfony\Component\Messenger\Transport\Receiver\MessageCountAwareInterface;
use Thelia\Command\SearchReindexCommand;
use TntSearch\Search\CatalogueCompletion;

#[AsMessageHandler]
final readonly class MarkCatalogueIndexedHandler
{
    public const int DELAY_MILLISECONDS = 30_000;

    public const int MAX_POSTPONEMENTS = 120;

    public function __construct(
        private CatalogueCompletion $completion,
        private MessageBusInterface $bus,
        #[Autowire(service: 'messenger.receiver_locator')]
        private ContainerInterface $receivers,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(MarkCatalogueIndexed $message): void
    {
        if (!$this->batchesAreWaiting()) {
            $failed = $this->completion->mark($message->families, $message->locales);

            if ([] !== $failed) {
                $this->logger->warning('TntSearch: a batch of {families} failed during the rebuild, their index is not marked complete and the database answers their searches; run thelia:search:reindex.', ['families' => implode(', ', $failed)]);
            }

            return;
        }

        if ($message->postponements >= self::MAX_POSTPONEMENTS) {
            $this->logger->warning('TntSearch: the catalogue batches were still waiting after {count} checks, the index is not marked complete and the database answers the searches; run thelia:search:reindex.', ['count' => $message->postponements]);

            return;
        }

        $this->bus->dispatch(new Envelope(
            new MarkCatalogueIndexed($message->families, $message->locales, $message->postponements + 1),
            [new DelayStamp(self::DELAY_MILLISECONDS), new TransportNamesStamp([SearchReindexCommand::QUEUE_TRANSPORT])],
        ));
    }

    private function batchesAreWaiting(): bool
    {
        if (!$this->receivers->has(SearchReindexCommand::QUEUE_TRANSPORT)) {
            return false;
        }

        $receiver = $this->receivers->get(SearchReindexCommand::QUEUE_TRANSPORT);

        return $receiver instanceof MessageCountAwareInterface && $receiver->getMessageCount() > 0;
    }
}
