<?php

namespace TntSearch\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Thelia\Command\ContainerAwareCommand;
use TntSearch\Service\IndexationService;
use TntSearch\TntSearch;

class GenerateIndexesCommand extends ContainerAwareCommand
{
    public function configure()
    {
        $this
            ->setName('tntsearch:indexes')
            ->setDescription('Generate indexes for TntSearch module');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $fs = new Filesystem();

        /** @var IndexationService $indexationService */
        $indexationService = $this->getContainer()->get('tntsearch.indexation.service');

        if (is_dir(TntSearch::INDEXES_DIR)) {
            $fs->remove(TntSearch::INDEXES_DIR);
        }

        ini_set('max_execution_time', 3600);

        try {
            $indexationService->generateIndexes();

        } catch (\Exception $exception) {
            $output->write($exception->getMessage());
        }
        return 1;
    }
}