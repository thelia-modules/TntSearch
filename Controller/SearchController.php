<?php
/**
 * Created by PhpStorm.
 * User: nicolasbarbey
 * Date: 26/09/2019
 * Time: 16:20
 */

namespace TntSearch\Controller;

use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Thelia;

class SearchController extends BaseAdminController
{
    /**
     * @return \Thelia\Core\HttpFoundation\Response
     */
    public function searchAdminAction()
    {
        if (version_compare(Thelia::THELIA_VERSION, '2.4.0-alpha2', 'lt')) {
            return $this->render('tntSearch/search2_3_4');
        }

        return $this->render('tntSearch/search');
    }
}