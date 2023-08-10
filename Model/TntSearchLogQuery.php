<?php

namespace TntSearch\Model;

use TntSearch\Model\Base\TntSearchLogQuery as BaseTntSearchLogQuery;

/**
 * Skeleton subclass for performing query and update operations on the 'tnt_search_log' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 */
class TntSearchLogQuery extends BaseTntSearchLogQuery
{
    /**
     * Check if a delivery module is suitable for the given country.
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return TntSearchLog|null
     */
    public function findOneBySearchWordsAndLocaleAndIndex(string $searchWords, string $locale, string $index): ?TntSearchLog
    {
        return self::create()->filterBySearchWords($searchWords)
            ->filterByLocale($locale)
            ->filterByIndex($index)
            ->findOne()
        ;
    }
}
