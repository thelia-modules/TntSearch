<?php

declare(strict_types=1);

namespace TntSearch\EventListener;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Thelia\Log\Tlog;
use TntSearch\Service\Provider\IndexationProvider;
use TntSearch\Service\Provider\TntSearchProvider;

/**
 * Builds the indexes where activation cannot, so the front search never meets a missing index and never builds one
 * itself:
 * - after `thelia:demo:import`, which writes the catalogue after the modules were activated (fresh install);
 * - after the response of a back-office request, when the product index does not exist yet (the module was just
 *   activated from the back-office, by a container that did not know it yet).
 */
final readonly class IndexBootstrapListener implements EventSubscriberInterface
{
    private const DEMO_IMPORT_COMMAND = 'thelia:demo:import';

    private const PRODUCT_INDEX = 'product';

    public function __construct(
        private IndexationProvider $indexationProvider,
        private TntSearchProvider $tntSearchProvider,
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

        if ($this->tntSearchProvider->hasIndexFile(self::PRODUCT_INDEX)) {
            return;
        }

        $this->indexAll();
    }

    public function rebuildIndexesAfterDemoImport(ConsoleTerminateEvent $event): void
    {
        if (self::DEMO_IMPORT_COMMAND !== $event->getCommand()?->getName() || 0 !== $event->getExitCode()) {
            return;
        }

        $this->indexAll();
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
