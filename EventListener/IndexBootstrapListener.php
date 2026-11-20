<?php

declare(strict_types=1);

namespace TntSearch\EventListener;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Thelia\Log\Tlog;
use TntSearch\Search\CatalogueReindexer;
use TntSearch\Service\Provider\IndexationProvider;
use TntSearch\Service\Provider\TntSearchProvider;

/**
 * Builds the indexes where activation cannot, so the front search never meets a missing index and never builds one
 * itself:
 * - after `thelia:demo:import`, which writes the catalogue after the modules were activated (fresh install);
 * - after the response of a back-office request, when the customer index is missing (the module was just activated
 *   from the back-office, by a container that did not know it yet); a missing catalogue is handed once to the queue.
 */
final readonly class IndexBootstrapListener implements EventSubscriberInterface
{
    private const DEMO_IMPORT_COMMAND = 'thelia:demo:import';

    private const CUSTOMER_INDEX = 'customer';

    public function __construct(
        private IndexationProvider $indexationProvider,
        private TntSearchProvider $tntSearchProvider,
        private CatalogueReindexer $catalogueReindexer,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::TERMINATE => ['buildMissingIndexesAfterBackOfficeRequest', -128],
            ConsoleEvents::TERMINATE => ['rebuildIndexesAfterDemoImport', -128],
        ];
    }

    public function buildMissingIndexesAfterBackOfficeRequest(TerminateEvent $event): void
    {
        if (!$event->isMainRequest() || !str_starts_with($event->getRequest()->getPathInfo(), '/admin')) {
            return;
        }

        if (!$this->tntSearchProvider->hasIndexFile(self::CUSTOMER_INDEX)) {
            $this->indexAll();

            return;
        }

        try {
            $this->catalogueReindexer->queueOnceWhenMissing();
        } catch (\Throwable $exception) {
            Tlog::getInstance()->addError('TntSearch: the catalogue rebuild could not be queued: '.$exception->getMessage());
        }
    }

    public function rebuildIndexesAfterDemoImport(ConsoleTerminateEvent $event): void
    {
        if (self::DEMO_IMPORT_COMMAND !== $event->getCommand()?->getName() || 0 !== $event->getExitCode()) {
            return;
        }

        $this->indexAll();

        try {
            $this->catalogueReindexer->rebuild();
        } catch (\Throwable $exception) {
            Tlog::getInstance()->addError('TntSearch: the catalogue could not be indexed after the demo import: '.$exception->getMessage());
        }
    }

    private function indexAll(): void
    {
        try {
            $this->indexationProvider->indexAll();
        } catch (\Throwable $exception) {
            Tlog::getInstance()->addError('TntSearch: the indexes could not be built: '.$exception->getMessage());
        }
    }
}
