<?php

namespace TntSearch\Controller;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Tools\URL;
use TntSearch\Event\GenerateIndexesEvent;
use TntSearch\TntSearch;

class TntSearchController extends BaseAdminController
{
    public function updateConfigAction(Request $request)
    {
        $onTheFlyUpdate = (bool) $request->get('on-the-fly-update', false);

        TntSearch::setConfigValue(TntSearch::ON_THE_FLY_UPDATE, $onTheFlyUpdate);

        return $this->generateRedirect(URL::getInstance()->absoluteUrl("/admin/module/TntSearch"));
    }

    public function generateIndexesAction(EventDispatcherInterface $dispatcher)
    {
        $fs = new Filesystem();

        if (is_dir(TntSearch::INDEXES_DIR)) {
            $fs->remove(TntSearch::INDEXES_DIR);
        }

        ini_set('max_execution_time', 3600);

        try {
            $dispatcher->dispatch(
                new GenerateIndexesEvent(),
                GenerateIndexesEvent::GENERATE_INDEXES
            );

        } catch (\Exception $exception) {
            $error = $exception->getMessage();

            return $this->generateRedirect(URL::getInstance()->absoluteUrl("/admin/module/TntSearch", ['error' => $error]));
        }

        return $this->generateRedirect(URL::getInstance()->absoluteUrl("/admin/module/TntSearch", ['success' => true]));
    }
}
