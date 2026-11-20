<?php

declare(strict_types=1);

namespace TntSearch\Tests\Unit\EventListener;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use TntSearch\EventListener\IndexBootstrapListener;
use TntSearch\Search\CatalogueReindexer;
use TntSearch\Service\Provider\IndexationProvider;
use TntSearch\Service\Provider\TntSearchProvider;

/**
 * The indexes are built where activation cannot (after the demo import, after a back-office request when an index is
 * missing) and never on a front request.
 */
final class IndexBootstrapListenerTest extends TestCase
{
    public function testTheIndexesAreRebuiltAfterASuccessfulDemoImport(): void
    {
        $this->listener(fullBuilds: 1, catalogueRebuilds: 1)
            ->rebuildIndexesAfterDemoImport($this->consoleTerminate('thelia:demo:import', Command::SUCCESS));
    }

    public function testAFailedDemoImportBuildsNothing(): void
    {
        $this->listener()
            ->rebuildIndexesAfterDemoImport($this->consoleTerminate('thelia:demo:import', Command::FAILURE));
    }

    public function testAnotherCommandBuildsNothing(): void
    {
        $this->listener(customerIndexExists: false)
            ->rebuildIndexesAfterDemoImport($this->consoleTerminate('cache:clear', Command::SUCCESS));
    }

    public function testABackOfficeRequestBuildsEveryIndexWhenTheCustomerIndexIsMissing(): void
    {
        $this->listener(customerIndexExists: false, fullBuilds: 1)
            ->buildMissingIndexesAfterBackOfficeRequest($this->kernelTerminate('/admin/modules'));
    }

    public function testABackOfficeRequestHandsAMissingCatalogueToTheQueueInsteadOfBuildingIt(): void
    {
        $this->listener(catalogueQueued: 1)
            ->buildMissingIndexesAfterBackOfficeRequest($this->kernelTerminate('/admin/modules'));
    }

    public function testAFrontRequestNeverBuildsTheIndexes(): void
    {
        $this->listener(customerIndexExists: false)
            ->buildMissingIndexesAfterBackOfficeRequest($this->kernelTerminate('/search'));
    }

    private function listener(bool $customerIndexExists = true, int $fullBuilds = 0, int $catalogueQueued = 0, int $catalogueRebuilds = 0): IndexBootstrapListener
    {
        $indexationProvider = $this->createMock(IndexationProvider::class);
        $indexationProvider->expects(self::exactly($fullBuilds))->method('indexAll');

        $tntSearchProvider = $this->createStub(TntSearchProvider::class);
        $tntSearchProvider->method('hasIndexFile')->willReturnMap([['customer', $customerIndexExists]]);

        $catalogueReindexer = $this->createMock(CatalogueReindexer::class);
        $catalogueReindexer->expects(self::exactly($catalogueQueued))->method('queueOnceWhenMissing');
        $catalogueReindexer->expects(self::exactly($catalogueRebuilds))->method('rebuild');

        return new IndexBootstrapListener($indexationProvider, $tntSearchProvider, $catalogueReindexer);
    }

    private function consoleTerminate(string $commandName, int $exitCode): ConsoleTerminateEvent
    {
        return new ConsoleTerminateEvent(new Command($commandName), new ArrayInput([]), new NullOutput(), $exitCode);
    }

    private function kernelTerminate(string $path): TerminateEvent
    {
        return new TerminateEvent($this->createStub(HttpKernelInterface::class), Request::create($path), new Response());
    }
}
