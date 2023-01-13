<?php

namespace TntSearch\Controller;

use Thelia\Controller\Admin\BaseAdminController;
use TntSearch\Model\TntSearchIndexQuery;
use TntSearch\TntSearch;

class ConfigurationController extends BaseAdminController
{
    public function viewAction(){
        return $this->render(
            "tnt_search_module_configuration",
            [
                'tnt_search_index_list' => TntSearchIndexQuery::create()->find()->toArray(),
                'on_the_fly_update' => TntSearch::getConfigValue(TntSearch::ON_THE_FLY_UPDATE, true)
            ]
        );
    }
}