<?php

declare(strict_types=1);

namespace TntSearch\Search;

use Symfony\Contracts\Service\ResetInterface;
use Thelia\Domain\Search\Document\SearchQuery;

final class SearchMemo implements ResetInterface
{
    private ?string $key = null;

    private ?bool $available = null;

    /** @var array<string, bool> */
    private array $completeLocales = [];

    /** @var array<string, float> */
    private array $hits = [];

    /**
     * @param callable(): array<string, float> $search
     *
     * @return array<string, float>
     */
    public function hitsOf(SearchQuery $query, callable $search): array
    {
        $key = self::keyOf($query);

        if ($key !== $this->key) {
            $this->hits = $search();
            $this->key = $key;
        }

        return $this->hits;
    }

    /**
     * @param callable(): bool $check
     */
    public function availability(callable $check): bool
    {
        return $this->available ??= $check();
    }

    /**
     * @param callable(): bool $check
     */
    public function completeness(string $key, callable $check): bool
    {
        return $this->completeLocales[$key] ??= $check();
    }

    public function reset(): void
    {
        $this->completeLocales = [];
        $this->key = null;
        $this->hits = [];
        $this->available = null;
    }

    private static function keyOf(SearchQuery $query): string
    {
        return serialize([$query->term, $query->alternativeTerms, $query->families, $query->locale, $query->scope->value, $query->visibleOnly]);
    }
}
