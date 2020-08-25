<?php

namespace TntSearch\Controller;


use ColissimoLabel\Exception\Exception;
use Symfony\Component\Filesystem\Filesystem;
use Thelia\Controller\Admin\BaseAdminController;
use TntSearch\Event\GenerateIndexesEvent;
use TntSearch\TntSearch;

class TntSearchController extends BaseAdminController
{

    public function generateIndexesAction()
    {
        $fs = new Filesystem();

        if (is_dir(TntSearch::INDEXES_DIR)) {
            $fs->remove(TntSearch::INDEXES_DIR);
        }

        ini_set('max_execution_time', 3600);

        try {

            $this->dispatch(
                GenerateIndexesEvent::GENERATE_INDEXES,
                new GenerateIndexesEvent()
            );

        } catch (\Exception $exception) {

            $error = $exception->getMessage();
            return $this->generateRedirect("/admin/module/TntSearch?error=$error");

        }

        return $this->generateRedirect("/admin/module/TntSearch?success=true");
    }
}