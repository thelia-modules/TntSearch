<?php

namespace TntSearch\Event;

class FolderIndexationEvent extends IndexesEvent
{
    const FOLDER_SQL_QUERY_INDEXATION = 'action.tntsearch.folder.sql.query.indexation';

    public function buildSqlQuery(): void
    {
        $locale = $this->getLocale();

        $this->sqlQuery = '
            SELECT f.id AS id,
            fi18n.title AS title,
            fi18n.chapo AS chapo,
            fi18n.description AS description,
            fi18n.postscriptum AS postscriptum
            FROM folder AS f LEFT JOIN folder_i18n AS fi18n ON f.id = fi18n.id
            WHERE fi18n.locale=\'' . $locale . '\';
        ';
    }
}