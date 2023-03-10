<?php

namespace TntSearch\Controller;

use Exception;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Tools\URL;
use TntSearch\Service\Provider\IndexationProvider;
use TntSearch\TntSearch;

class IndexationController extends BaseAdminController
{
    public function generateIndexesAction(): Response
    {
        ini_set('max_execution_time', 3600);

        try {
            $fs = new Filesystem();

            /** @var IndexationProvider $indexationProvider */
            $indexationProvider = $this->getContainer()->get('tntsearch.indexation.provider');

            if (is_dir(TntSearch::INDEXES_DIR)) {
                $fs->remove(TntSearch::INDEXES_DIR);
            }

            $indexationProvider->indexAll();

        } catch (Exception $exception) {
            $error = $exception->getMessage();

            return $this->generateRedirect(URL::getInstance()->absoluteUrl("/admin/module/TntSearch", ['error' => $error]));
        }

        return $this->generateRedirect(URL::getInstance()->absoluteUrl("/admin/module/TntSearch", ['success' => true]));
    }
}
