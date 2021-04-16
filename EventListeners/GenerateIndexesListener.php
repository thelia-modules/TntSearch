<?php
/**
 * Created by PhpStorm.
 * User: nicolasbarbey
 * Date: 31/07/2020
 * Time: 16:03
 */

namespace TntSearch\EventListeners;


use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Model\Base\LangQuery;
use TntSearch\Event\GenerateIndexesEvent;
use TntSearch\TntSearch;

class GenerateIndexesListener implements EventSubscriberInterface
{
    public function generateIndexes()
    {
        $langs = LangQuery::create()->filterByActive(1)->find();

        $tnt = TntSearch::getTntSearch();

        TntSearch::generateCustomerIndex($tnt);
        TntSearch::generateOrderIndex($tnt);
        TntSearch::generatePseIndex($tnt);

        foreach ($langs as $lang) {

            $locale = $lang->getLocale();

            TntSearch::generateProductIndex($tnt, $locale);
            TntSearch::generateCategoryIndex($tnt, $locale);
            TntSearch::generateContentIndex($tnt, $locale);
            TntSearch::generateFolderIndex($tnt, $locale);
            TntSearch::generateBrandIndex($tnt, $locale);
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            GenerateIndexesEvent::GENERATE_INDEXES => 'generateIndexes',
        ];
    }
}