<?php

namespace TntSearch\Hook;

use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Hook\BaseHook;
use TntSearch\Model\TntSearchIndexQuery;
use TntSearch\TntSearch;

class BackHook extends BaseHook
{
    public function onModuleConfiguration(HookRenderEvent $event)
    {
        $event->add(
            $this->render(
                "module_configuration.html",
                [
                    'tnt_search_index_list' => TntSearchIndexQuery::create()->find()->toArray(),
                    'on_the_fly_update' => TntSearch::getConfigValue(TntSearch::ON_THE_FLY_UPDATE, true)
                ]
            )
        );
    }
}
