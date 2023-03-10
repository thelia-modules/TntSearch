<?php

namespace TntSearch\Commands;

use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Thelia\Command\ContainerAwareCommand;
use TntSearch\Service\Provider\IndexationProvider;
use TntSearch\TntSearch;

class GenerateIndexesCommand extends ContainerAwareCommand
{
    /** @var IndexationProvider $indexationProvider */
    private $indexationProvider;

    public function __construct()
    {
        parent::__construct();
        $this->indexationProvider = $this->getContainer()->get('tntsearch.indexation.provider');
    }

    public function configure() : void
    {
        $this
            ->setName('tntsearch:indexes')
            ->setDescription('Generate indexes for TntSearch module');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $fs = new Filesystem();

        if (is_dir(TntSearch::INDEXES_DIR)) {
            $fs->remove(TntSearch::INDEXES_DIR);
        }

        ini_set('max_execution_time', 3600);

        try {
            $this->indexationProvider->indexAll();

        } catch (Exception $exception) {
            $output->write($exception->getMessage());
        }

        return 1;
    }
}