<?php

namespace TntSearch\Hook;

use Thelia\Core\Event\Hook\HookRenderBlockEvent;
use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Hook\BaseHook;
use Thelia\Tools\URL;
use TntSearch\TntSearch;

class BackHook extends BaseHook
{
    public function onModuleConfig(HookRenderEvent $event)
    {
        $event->add(
            $this->render(
                "module_configuration.html",
                [
                    'on_the_fly_update' => TntSearch::getConfigValue(TntSearch::ON_THE_FLY_UPDATE, true)
                ]
            )
        );
    }

    public function onMainTopMenuTools(HookRenderBlockEvent $event)
    {
        $event->add(
            [
                'id' => 'search_log_menu_tags',
                'class' => '',
                'url' => URL::getInstance()->absoluteUrl('/admin/search_log'),
                'title' => $this->trans("Search logs", [], TntSearch::DOMAIN_NAME)
            ]
        );

        $event->add(
            [
                'id' => 'search_synonyme_menu',
                'class' => '',
                'url' => URL::getInstance()->absoluteUrl('/admin/module/TntSearch/synonym'),
                'title' => $this->trans("Synonym Management", [], TntSearch::DOMAIN_NAME)
            ]
        );
    }
}