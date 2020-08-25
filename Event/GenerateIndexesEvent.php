<?php
/**
 * Created by PhpStorm.
 * User: nicolasbarbey
 * Date: 31/07/2020
 * Time: 16:06
 */

namespace TntSearch\Event;

use Thelia\Core\Event\ActionEvent;

class GenerateIndexesEvent extends ActionEvent
{
    const GENERATE_INDEXES = 'action.tntsearch.generate.indexes';
}