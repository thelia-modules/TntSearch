<?php

namespace TntSearch\Controller;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Tools\URL;
use TntSearch\Service\IndexationService;
use TntSearch\TntSearch;

class IndexationController extends BaseAdminController
{
    public function updateConfigAction()
    {
        $onTheFlyUpdate = (bool)$this->getRequest()->get('on-the-fly-update', false);

        TntSearch::setConfigValue(TntSearch::ON_THE_FLY_UPDATE, $onTheFlyUpdate);

        return $this->generateRedirect(URL::getInstance()->absoluteUrl("/admin/module/TntSearch"));
    }

    /**
     * @return Response
     */
    public function generateIndexesAction(): Response
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
            $error = $exception->getMessage();

            return $this->generateRedirect(URL::getInstance()->absoluteUrl("/admin/module/TntSearch", ['error' => $error]));
        }

        return $this->generateRedirect(URL::getInstance()->absoluteUrl("/admin/module/TntSearch", ['success' => true]));
    }
}
