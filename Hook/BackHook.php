<?php
/**
 * Created by PhpStorm.
 * User: nicolasbarbey
 * Date: 30/07/2020
 * Time: 10:53
 */

namespace TntSearch\Hook;


use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Hook\BaseHook;
use TntSearch\TntSearch;

class BackHook extends BaseHook
{
    public function onModuleConfiguration(HookRenderEvent $event)
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
}
