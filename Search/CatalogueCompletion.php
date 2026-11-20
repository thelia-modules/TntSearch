<?php

declare(strict_types=1);

namespace TntSearch\Search;

use Thelia\Domain\Search\SearchDocumentProviders;
use Thelia\Domain\Search\SearchLocales;

final readonly class CatalogueCompletion
{
    public function __construct(
        private SearchDocumentProviders $providers,
        private SearchLocales $locales,
        private DocumentIndexes $indexes,
        private SearchMemo $memo,
    ) {
    }

    /**
     * @param list<string>      $families
     * @param list<string>|null $locales
     *
     * @return list<string> the families left unmarked because a batch of theirs failed since their last rebuild started
     */
    public function mark(array $families = [], ?array $locales = null): array
    {
        $failed = [];

        foreach ([] === $families ? $this->providers->families() : $families as $family) {
            $provider = $this->providers->get($family);

            if (null === $provider) {
                continue;
            }

            if ($this->indexes->hasFailed($family)) {
                $failed[] = $family;

                continue;
            }

            foreach ($provider->isTranslatable() ? ($locales ?? $this->locales->indexed()) : [null] as $locale) {
                $this->indexes->markComplete($family, $locale);
            }
        }

        $this->memo->reset();

        return $failed;
    }

    /**
     * @param list<string> $families
     */
    public function startRebuild(array $families = []): void
    {
        foreach ([] === $families ? $this->providers->families() : $families as $family) {
            if (null !== $this->providers->get($family)) {
                $this->indexes->forgetFailure($family);
            }
        }
    }

    public function isEveryFamilyMarked(): bool
    {
        $locales = null;

        foreach ($this->providers->families() as $family) {
            $provider = $this->providers->get($family);

            if (null === $provider) {
                continue;
            }

            foreach ($provider->isTranslatable() ? ($locales ??= $this->locales->indexed()) : [null] as $locale) {
                if (!$this->indexes->isComplete($family, $locale)) {
                    return false;
                }
            }
        }

        return true;
    }
}
