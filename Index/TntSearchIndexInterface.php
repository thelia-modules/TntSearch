<?php

namespace TntSearch\Index;

interface TntSearchIndexInterface
{
    public function getFieldWeights(string $field): int;

    public function isTranslatable(): bool;

    public function isGeoIndexable(): bool;

    public function buildSqlQuery(int $itemId = null, string $locale = null): string;

    public function buildSqlGeoQuery(int $itemId = null): ?string;
}