<?php

namespace TntSearch\Index;

class Content extends BaseIndex
{
    public function isTranslatable(): bool
    {
        return true;
    }

    public function buildSqlQuery(int $itemId = null, string $locale = null): string
    {
        $query = '
           SELECT c.id AS id,
            ci.title AS title,
            ci.chapo AS chapo,
            ci.description AS description,
            ci.postscriptum AS postscriptum
            FROM content AS c LEFT JOIN content_i18n AS ci ON c.id = ci.id
            WHERE ci.locale=\'' . $locale . '\'
        ';

        if ($itemId) {
            $query .= ' AND c.id =' . $itemId;
        }

        return $query;
    }
}