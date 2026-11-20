<?php

declare(strict_types=1);

namespace TntSearch\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Thelia\Domain\Search\SearchEngineRegistry;
use TntSearch\Search\CatalogueReindexer;
use TntSearch\Search\DocumentIndexes;
use TntSearch\Search\TntSearchEngine;
use TntSearch\Service\Provider\TntSearchProvider;
use TntSearch\TntSearch;

#[AsCommand(name: 'tntsearch:status', description: 'Tells whether the TntSearch indexes answer, how many documents they hold and when they were last written')]
final class StatusCommand extends Command
{
    private const array LEGACY_INDEXES = ['customer', 'order'];

    public function __construct(
        private readonly DocumentIndexes $documentIndexes,
        private readonly TntSearchEngine $engine,
        private readonly SearchEngineRegistry $engines,
        private readonly TntSearchProvider $tntSearchProvider,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(\sprintf('Storage: %s', $this->documentIndexes->storage()));
        $output->writeln(\sprintf('Engine answering the core search: %s', $this->engines->engine() instanceof TntSearchEngine ? 'TntSearch' : 'another engine'));
        $output->writeln(\sprintf('Available: %s', $this->engine->isAvailable() ? 'yes' : 'no, the database answers the searches'));

        $table = new Table($output);
        $table->setHeaders(['Family', 'Locale', 'Documents', 'Last write']);

        foreach ($this->documentIndexes->statuses() as $status) {
            $table->addRow([$status->family, $status->locale ?? '-', $status->documents, $status->lastWrite->format('Y-m-d H:i:s')]);
        }

        $table->render();

        foreach (self::LEGACY_INDEXES as $index) {
            $output->writeln(\sprintf('Back-office %s index: %s', $index, $this->tntSearchProvider->hasIndexFile($index) ? 'built' : 'missing'));
        }

        $queuedAt = (int) TntSearch::getConfigValue(CatalogueReindexer::QUEUED);

        if (0 !== $queuedAt) {
            $output->writeln(\sprintf('Catalogue rebuild handed to the queue on %s.', date('Y-m-d H:i:s', $queuedAt)));
        }

        if (!$this->engine->isAvailable()) {
            $output->writeln('<comment>Run thelia:search:reindex to build the catalogue indexes.</comment>');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
