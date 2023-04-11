<?php

namespace TntSearch\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Tools\URL;
use TntSearch\TntSearch;

class ConfigurationController extends BaseAdminController
{
    public function configuration(): RedirectResponse
    {
        $request = $this->getRequest();

        $onTheFlyUpdate = (bool)$request->get('on-the-fly-update',false);

        TntSearch::setConfigValue(TntSearch::ON_THE_FLY_UPDATE, $onTheFlyUpdate);

        return new RedirectResponse(URL::getInstance()->absoluteUrl("/admin/module/TntSearch"));
    }
}