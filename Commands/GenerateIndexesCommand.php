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
    public function __construct(protected IndexationProvider $indexationProvider)
    {
        parent::__construct();
    }

    public function configure()
    {
        $this
            ->setName('tntsearch:indexes')
            ->setDescription('Generate indexes for TntSearch module');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initRequest();

        $fs = new Filesystem();

        if (is_dir(TntSearch::INDEXES_DIR)) {
            $fs->remove(TntSearch::INDEXES_DIR);
        }

        try {
            $this->indexationProvider->indexAll($output);

        } catch (Exception $exception) {
            $output->write('<error>' . $exception->getMessage() . '</error>');
        }

        return 1;
    }
}