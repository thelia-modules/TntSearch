<?php

declare(strict_types=1);

namespace TntSearch\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Thelia\Command\SearchReindexCommand;
use Thelia\Domain\Search\Message\IndexDocument;
use TntSearch\Search\DocumentIndexes;
use TntSearch\Search\SearchMemo;

final readonly class FailedCatalogueBatchListener
{
    public function __construct(
        private DocumentIndexes $indexes,
        private SearchMemo $memo,
    ) {
    }

    #[AsEventListener(event: WorkerMessageFailedEvent::class)]
    public function forgetTheCompletionOfItsFamily(WorkerMessageFailedEvent $event): void
    {
        $message = $event->getEnvelope()->getMessage();

        if (!$message instanceof IndexDocument || $event->willRetry() || SearchReindexCommand::QUEUE_TRANSPORT !== $event->getReceiverName()) {
            return;
        }

        $this->indexes->recordFailure($message->family);
        $this->indexes->forgetCompletion($message->family);
        $this->memo->reset();
    }
}
