<?php
/**
 * Created by PhpStorm.
 * User: nicolasbarbey
 * Date: 03/08/2020
 * Time: 09:55
 */

namespace TntSearch\Commands;


use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Thelia\Command\ContainerAwareCommand;
use TntSearch\Event\GenerateIndexesEvent;
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
        if (is_dir(TntSearch::INDEXES_DIR)) {
            $fs->remove(TntSearch::INDEXES_DIR);
        }

        $this->getDispatcher()->dispatch(
            GenerateIndexesEvent::GENERATE_INDEXES,
            new GenerateIndexesEvent()
        );
    }

}