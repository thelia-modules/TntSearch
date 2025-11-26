<?php

namespace TntSearch\Tokenizer;

use TntSearch\Model\TntSynonymQuery;
use TntSearch\Model\TntSynonym;

trait SynonymResolverTrait
{
    /** @var array<string, string> */
    private array $lookup = [];
    private bool $initialized = false;

    public function normalizeToken(string $word): string
    {
        $this->initializeLookup();
        $key = mb_strtolower(trim($word));
        return $this->lookup[$key] ?? $key;
    }

    public function normalizePhrase(array $words): array
    {
        return array_map(fn(string $word) => $this->normalizeToken($word), $words);
    }

    public function clearCache(): void
    {
        $this->lookup = [];
        $this->initialized = true;
    }

    private function initializeLookup(): void
    {
        if ($this->initialized) {
            return;
        }

        $synonymTerms = TntSynonymQuery::create()
            ->filterByEnabled(true)
            ->orderByPosition()
            ->find();

        $groups = [];
        /** @var TntSynonym $synonym */
        foreach ($synonymTerms as $synonym) {
            $groupId = $synonym->getGroupId();
            $term = mb_strtolower($synonym->getTerm());

            if (!isset($groups[$groupId])) {
                $groups[$groupId] = $term;
            }

            $this->lookup[$term] = $groups[$groupId];
        }

        $this->initialized = true;
    }
}