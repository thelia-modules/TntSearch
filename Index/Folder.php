<?php

namespace TntSearch\Index;

class Folder extends BaseIndex
{
    public function isTranslatable(): bool
    {
        return true;
    }

    public function buildSqlQuery(int $itemId = null, string $locale = null): string
    {
        $query = '
            SELECT f.id AS id,
            fi18n.title AS title,
            fi18n.chapo AS chapo,
            fi18n.description AS description,
            fi18n.postscriptum AS postscriptum
            FROM folder AS f LEFT JOIN folder_i18n AS fi18n ON f.id = fi18n.id
            WHERE fi18n.locale=\'' . $locale . '\'
        ';

        if ($itemId) {
            $query .= ' AND f.id =' . $itemId;
        }

        return $query;
    }
}