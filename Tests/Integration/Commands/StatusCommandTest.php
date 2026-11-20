<?php

declare(strict_types=1);

namespace TntSearch\Tests\Integration\Commands;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Thelia\Domain\Search\Contract\SearchScope;
use Thelia\Domain\Search\Document\SearchDocument;
use Thelia\Test\IntegrationTestCase;
use TntSearch\Search\TntSearchEngine;

final class StatusCommandTest extends IntegrationTestCase
{
    private const string FAMILY = 'status_probe';

    private TntSearchEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = static::getContainer()->get(TntSearchEngine::class);
        $this->engine->purge(self::FAMILY);
    }

    protected function tearDown(): void
    {
        $this->engine->purge(self::FAMILY);

        parent::tearDown();
    }

    public function testTheStatusCountsTheDocumentsOfEachIndex(): void
    {
        $this->engine->index([
            new SearchDocument(self::FAMILY, 1, 'fr_FR', SearchScope::Shop, true, ['title' => 'Lampe']),
            new SearchDocument(self::FAMILY, 2, 'fr_FR', SearchScope::Shop, false, ['title' => 'Chaise']),
            new SearchDocument(self::FAMILY, 2, 'fr_FR', SearchScope::Shop, true, ['title' => 'Chaise rouge']),
        ]);

        $tester = $this->runStatusCommand();

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertMatchesRegularExpression('/\|\s*'.self::FAMILY.'\s*\|\s*fr_FR\s*\|\s*2\s*\|\s*\d{4}-\d{2}-\d{2} /', $tester->getDisplay());
        self::assertStringContainsString('Engine answering the core search: TntSearch', $tester->getDisplay());
    }

    private function runStatusCommand(): CommandTester
    {
        $tester = new CommandTester((new Application(static::$kernel))->find('tntsearch:status'));
        $tester->execute([]);

        return $tester;
    }
}
