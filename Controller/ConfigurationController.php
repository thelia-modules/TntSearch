<?php

namespace TntSearch\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Tools\URL;
use TntSearch\TntSearch;

/**
 * @Route("/admin/module/TntSearch/configuration", name="tntsearch_configuration_")
 */
class ConfigurationController extends BaseAdminController
{
    /**
     * @Route("", name="_on_the_fly", methods="POST")
     */
    public function configuration(Request $request): RedirectResponse
    {
        $onTheFlyUpdate = (bool)$request->get('on-the-fly-update', false);

        TntSearch::setConfigValue(TntSearch::ON_THE_FLY_UPDATE, $onTheFlyUpdate);

        return new RedirectResponse(URL::getInstance()->absoluteUrl("/admin/module/TntSearch"));
    }
}