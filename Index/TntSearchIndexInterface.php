<?php

namespace TntSearch\Index;

use TeamTNT\TNTSearch\Indexer\TNTIndexer;

interface TntSearchIndexInterface
{
    public function isTranslatable(): bool;

    public function index(): void;

    public function buildSqlQuery(int $itemId = null, string $locale = null): string;
}