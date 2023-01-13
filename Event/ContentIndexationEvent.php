<?php

namespace TntSearch\Event;

class ContentIndexationEvent extends IndexesEvent
{
    const CONTENT_SQL_QUERY_INDEXATION = 'action.tntsearch.content.sql.query.indexation';

    public function buildSqlQuery(): void
    {
        $locale = $this->getLocale();

        $this->sqlQuery = '
            SELECT c.id AS id,
            ci.title AS title,
            ci.chapo AS chapo,
            ci.description AS description,
            ci.postscriptum AS postscriptum
            FROM content AS c LEFT JOIN content_i18n AS ci ON c.id = ci.id
            WHERE ci.locale=\'' . $locale . '\';
        ';
    }
}