<?php

namespace TntSearch\Controller;

use Exception;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Tools\URL;
use TntSearch\Service\Provider\IndexationProvider;
use TntSearch\TntSearch;

/**
 * @Route("/admin/module/TntSearch", name="tntsearch_indexation")
 */
class IndexationController extends BaseAdminController
{
    /**
     * @Route("/generate-indexes", name="_generation", methods="GET")
     */
    public function generateIndexesAction(IndexationProvider $indexationProvider): Response
    {
        ini_set('max_execution_time', 3600);

        try {
            $fs = new Filesystem();

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
