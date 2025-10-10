<?php

namespace TntSearch\Index;

class Brand extends BaseIndex
{
    public function isTranslatable(): bool
    {
        return true;
    }

    public function buildSqlQuery(int $itemId = null, string $locale = null): string
    {
        $query = '
             SELECT b.id AS id,
            bi.title AS title,
            bi.chapo AS chapo,
            bi.description AS description,
            bi.postscriptum AS postscriptum
            FROM brand AS b LEFT JOIN brand_i18n AS bi ON b.id = bi.id
            WHERE bi.locale=\'' . $locale . '\'
        ';

        if ($itemId) {
            $query .= ' AND b.id =' . $itemId;
        }

        return $query;
    }
}