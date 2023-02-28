<?php

namespace TntSearch\Index;

use TeamTNT\TNTSearch\Indexer\TNTIndexer;

interface TntSearchIndexInterface
{
    /** @return bool */
    public function isTranslatable(): bool;

    /**
     * @return void
     */
    public function index(): void;

    /**
     * @param int|null $itemId
     * @param string|null $locale
     * @return string
     */
    public function buildSqlQuery(int $itemId = null, string $locale = null): string;
}