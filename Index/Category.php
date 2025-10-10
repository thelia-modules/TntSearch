<?php

namespace TntSearch\Index;

class Category extends BaseIndex
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
            FROM category AS c LEFT JOIN category_i18n AS ci ON c.id = ci.id
            WHERE ci.locale=\'' . $locale . '\'
        ';

        if ($itemId) {
            $query .= ' AND c.id =' . $itemId;
        }

        return $query;
    }
}