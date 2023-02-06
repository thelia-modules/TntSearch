<?php

namespace TntSearch\Event;

class CategoryIndexationEvent extends IndexesEvent
{
    const CATEGORY_SQL_QUERY_INDEXATION = 'action.tntsearch.category.sql.query.indexation';

    public function buildSqlQuery(): void
    {
        $locale = $this->getLocale();

        $this->sqlQuery = '
            SELECT c.id AS id,
            ci.title AS title,
            ci.chapo AS chapo,
            ci.description AS description,
            ci.postscriptum AS postscriptum
            FROM category AS c LEFT JOIN category_i18n AS ci ON c.id = ci.id
            WHERE ci.locale=\'' . $locale . '\';
        ';
    }
}