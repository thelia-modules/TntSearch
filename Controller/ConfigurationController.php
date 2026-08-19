<?php

namespace TntSearch\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Tools\TokenProvider;
use Thelia\Tools\URL;
use TntSearch\TntSearch;

#[Route("/admin/module/TntSearch/configuration", name: "tntsearch_configuration_")]
class ConfigurationController extends BaseAdminController
{
    #[Route("", name: "_on_the_fly", methods: ["POST"])]
    public function configuration(Request $request, TokenProvider $tokenProvider): RedirectResponse
    {
        $tokenProvider->checkToken((string) $request->request->get('_token'));

        $onTheFlyUpdate = (bool)$request->request->get('on-the-fly-update', false);

        TntSearch::setConfigValue(TntSearch::ON_THE_FLY_UPDATE, $onTheFlyUpdate);

        return new RedirectResponse(URL::getInstance()->absoluteUrl("/admin/module/TntSearch"));
    }
}