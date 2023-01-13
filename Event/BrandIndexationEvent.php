<?php

namespace TntSearch\Event;

class BrandIndexationEvent extends IndexesEvent
{
    const BRAND_SQL_QUERY_INDEXATION = 'action.tntsearch.brand.sql.query.indexation';

    public function buildSqlQuery(): void
    {
        $locale = $this->getLocale();

        $this->sqlQuery = '
            SELECT b.id AS id,
            bi.title AS title,
            bi.chapo AS chapo,
            bi.description AS description,
            bi.postscriptum AS postscriptum
            FROM brand AS b LEFT JOIN brand_i18n AS bi ON b.id = bi.id
            WHERE bi.locale=\'' . $locale . '\';
        ';
    }
}