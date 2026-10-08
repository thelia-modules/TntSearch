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
use TntSearch\Service\Provider\IndexationProvider;
use TntSearch\Service\Provider\TntSearchProvider;

/**
 * The indexes are built where activation cannot (after the demo import, after a back-office request when the product
 * index is missing) and never on a front request.
 */
final class IndexBootstrapListenerTest extends TestCase
{
    public function testTheIndexesAreRebuiltAfterASuccessfulDemoImport(): void
    {
        $this->listener(expectedBuilds: 1, productIndexExists: true)
            ->rebuildIndexesAfterDemoImport($this->consoleTerminate('thelia:demo:import', Command::SUCCESS));
    }

    public function testAFailedDemoImportBuildsNothing(): void
    {
        $this->listener(expectedBuilds: 0, productIndexExists: true)
            ->rebuildIndexesAfterDemoImport($this->consoleTerminate('thelia:demo:import', Command::FAILURE));
    }

    public function testAnotherCommandBuildsNothing(): void
    {
        $this->listener(expectedBuilds: 0, productIndexExists: false)
            ->rebuildIndexesAfterDemoImport($this->consoleTerminate('cache:clear', Command::SUCCESS));
    }

    public function testABackOfficeRequestBuildsTheMissingIndexes(): void
    {
        $this->listener(expectedBuilds: 1, productIndexExists: false)
            ->buildMissingIndexesAfterBackOfficeRequest($this->kernelTerminate('/admin/modules'));
    }

    public function testABackOfficeRequestLeavesExistingIndexesAlone(): void
    {
        $this->listener(expectedBuilds: 0, productIndexExists: true)
            ->buildMissingIndexesAfterBackOfficeRequest($this->kernelTerminate('/admin/modules'));
    }

    public function testAFrontRequestNeverBuildsTheIndexes(): void
    {
        $this->listener(expectedBuilds: 0, productIndexExists: false)
            ->buildMissingIndexesAfterBackOfficeRequest($this->kernelTerminate('/search'));
    }

    private function listener(int $expectedBuilds, bool $productIndexExists): IndexBootstrapListener
    {
        $indexationProvider = $this->createMock(IndexationProvider::class);
        $indexationProvider->expects(self::exactly($expectedBuilds))->method('indexAll');

        $tntSearchProvider = $this->createStub(TntSearchProvider::class);
        $tntSearchProvider->method('hasIndexFile')->willReturnMap([['product', $productIndexExists]]);

        return new IndexBootstrapListener($indexationProvider, $tntSearchProvider);
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
