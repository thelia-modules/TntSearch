<?php

namespace TntSearch\Controller;

use Symfony\Component\Filesystem\Filesystem;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Tools\URL;
use TntSearch\Event\GenerateIndexesEvent;
use TntSearch\TntSearch;

class TntSearchController extends BaseAdminController
{
    public function updateConfigAction()
    {
        $onTheFlyUpdate = (bool) $this->getRequest()->get('on-the-fly-update', false);

        TntSearch::setConfigValue(TntSearch::ON_THE_FLY_UPDATE, $onTheFlyUpdate);

        return $this->generateRedirect(URL::getInstance()->absoluteUrl("/admin/module/TntSearch"));
    }

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

            return $this->generateRedirect(URL::getInstance()->absoluteUrl("/admin/module/TntSearch", ['error' => $error]));
        }

        return $this->generateRedirect(URL::getInstance()->absoluteUrl("/admin/module/TntSearch", ['success' => true]));
    }
}
