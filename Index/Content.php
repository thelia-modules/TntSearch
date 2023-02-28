<?php

namespace TntSearch\Index;

class Content extends BaseIndex
{
    public function isTranslatable(): bool
    {
        return true;
    }

    /**
     * @param int|null $itemId
     * @param string|null $locale
     * @return string
     */
    public function buildSqlQuery(int $itemId = null, string $locale = null): string
    {
        return '
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