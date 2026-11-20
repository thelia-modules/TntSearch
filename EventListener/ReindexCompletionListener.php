<?php

declare(strict_types=1);

namespace TntSearch\EventListener;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;
use Thelia\Command\SearchReindexCommand;
use TntSearch\Search\CatalogueCompletion;
use TntSearch\Search\Message\MarkCatalogueIndexed;

final readonly class ReindexCompletionListener implements EventSubscriberInterface
{
    private const string REINDEX_COMMAND = 'thelia:search:reindex';

    public function __construct(
        private CatalogueCompletion $completion,
        private MessageBusInterface $bus,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => ['onReindexStart', -128],
            ConsoleEvents::TERMINATE => ['onReindexEnd', -128],
        ];
    }

    public function onReindexStart(ConsoleCommandEvent $event): void
    {
        if (self::REINDEX_COMMAND !== $event->getCommand()?->getName()) {
            return;
        }

        /** @var list<string> $families */
        $families = array_values((array) $event->getInput()->getOption('family'));
        $this->completion->startRebuild($families);
    }

    public function onReindexEnd(ConsoleTerminateEvent $event): void
    {
        $input = $event->getInput();

        if (self::REINDEX_COMMAND !== $event->getCommand()?->getName() || 0 !== $event->getExitCode()) {
            return;
        }

        /** @var list<string> $families */
        $families = array_values((array) $input->getOption('family'));
        /** @var list<string> $locales */
        $locales = array_values((array) $input->getOption('locale'));
        $message = new MarkCatalogueIndexed($families, [] === $locales ? null : $locales);

        if (true === $input->getOption('queue')) {
            $this->bus->dispatch(new Envelope($message, [new TransportNamesStamp([SearchReindexCommand::QUEUE_TRANSPORT])]));

            return;
        }

        $this->completion->mark($message->families, $message->locales);
    }
}
