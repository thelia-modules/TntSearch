<?php

namespace TntSearch\Commands;

use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
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

        try {
            $this->indexationProvider->indexAll($output);
            return self::SUCCESS;

        } catch (Exception $exception) {
            $output->write('<error>' . $exception->getMessage() . '</error>');
        }

        return self::FAILURE;
    }
}