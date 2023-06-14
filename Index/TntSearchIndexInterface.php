<?php

namespace TntSearch\Index;

interface TntSearchIndexInterface
{
    public function getFieldWeights(string $field): int;

    public function isTranslatable(): bool;

    public function index(): void;

    public function buildSqlQuery(int $itemId = null, string $locale = null): string;
}