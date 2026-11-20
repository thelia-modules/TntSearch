<?php

declare(strict_types=1);

namespace TntSearch\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Thelia\Command\ContainerAwareCommand;
use TntSearch\Search\CatalogueReindexer;
use TntSearch\Service\Provider\IndexationProvider;

class GenerateIndexesCommand extends ContainerAwareCommand
{
    public function __construct(protected IndexationProvider $indexationProvider, protected CatalogueReindexer $catalogueReindexer)
    {
        parent::__construct();
    }

    protected function configure(): void
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
            $this->catalogueReindexer->rebuild();

            return self::SUCCESS;
        } catch (\Exception $exception) {
            $output->write('<error>'.$exception->getMessage().'</error>');
        }

        return self::FAILURE;
    }
}
