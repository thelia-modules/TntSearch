<?php

declare(strict_types=1);

namespace TntSearch\Search;

use Thelia\Domain\Search\Contract\SearchScope;
use TntSearch\Engine\DocumentEngine;
use TntSearch\Service\Support\TntSearch;

final readonly class TermScorer
{
    public const int SHORT_WORD = 6;

    public const float FUZZY_PENALTY = 0.5;

    /**
     * @return array<int, float>
     */
    public function scores(TntSearch $search, DocumentEngine $engine, string $term, SearchScope $scope, bool $visibleOnly): array
    {
        $tokens = array_values($search->breakIntoTokens($term));

        if ([] === $tokens || null === $search->stemmer) {
            return [];
        }

        $documents = max(1, $engine->documentCount());
        $scores = null;
        $last = \count($tokens) - 1;

        foreach ($tokens as $position => $token) {
            $stem = $search->stemmer->stem($token);
            $wordScores = $this->wordScores($engine, $stem, mb_strlen($token) < self::SHORT_WORD ? 1 : 2, $documents, $last === $position ? [$token, $stem] : [], $scope, $visibleOnly);

            if (null === $scores) {
                $scores = $wordScores;
                continue;
            }

            $kept = [];

            foreach ($scores as $id => $score) {
                if (isset($wordScores[$id])) {
                    $kept[$id] = $score + $wordScores[$id];
                }
            }

            $scores = $kept;

            if ([] === $scores) {
                return [];
            }
        }

        return $scores;
    }

    /**
     * @param list<string> $prefixes
     *
     * @return array<int, float>
     */
    private function wordScores(DocumentEngine $engine, string $stem, int $maxDistance, int $documents, array $prefixes, SearchScope $scope, bool $visibleOnly): array
    {
        $hits = $engine->weightedHits($stem, $maxDistance, $prefixes, $scope, $visibleOnly, self::FUZZY_PENALTY);

        if ([] === $hits) {
            return [];
        }

        $rarity = log(1 + $documents / \count($hits));

        foreach ($hits as $id => $weightedHits) {
            $hits[$id] = $rarity * $weightedHits;
        }

        return $hits;
    }
}
