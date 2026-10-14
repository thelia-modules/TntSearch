<?php

declare(strict_types=1);

namespace TntSearch\Search;

use FlexyBundle\Search\ProductSearchEngineInterface;
use TeamTNT\TNTSearch\Exceptions\IndexNotFoundException;
use Thelia\Log\Tlog;
use TntSearch\Index\Product;
use TntSearch\Service\Provider\TntSearchProvider;

/**
 * The product search of the Flexy theme, answered from the product index of the module.
 *
 * Typos are tolerated (fuzzy matching on the indexed words), every word of the term must match, and the ids come
 * back most relevant first. Flexy logs the submitted searches itself (ProductSearchedEvent, which the module logs):
 * this engine dispatches no SaveRequestEvent, so a search is logged once.
 *
 * Only registered when the Flexy theme ships the interface (see TntSearch::configureServices()).
 */
final readonly class FlexyProductSearchEngine implements ProductSearchEngineInterface
{
    public function __construct(
        private TntSearchProvider $tntSearchProvider,
        private Product $productIndex,
    ) {
    }

    public function productIds(string $term, string $locale, int $limit): array
    {
        if ('' === trim($term) || $limit < 1) {
            return [];
        }

        try {
            $tntSearch = $this->tntSearchProvider->getTntSearch($this->productIndex->getTokenizer(), $locale);
            $tntSearch->selectIndex($this->productIndex->getIndexFileName($locale));
            $tntSearch->fuzziness(true);

            $phrase = implode(' ', $tntSearch->breakIntoTokens($term));

            if ('' === $phrase) {
                return [];
            }

            $matchingIds = $tntSearch->searchBoolean($phrase, $limit)['ids'];

            if ([] === $matchingIds) {
                return [];
            }

            $scores = $tntSearch->search($phrase, $limit)['docScores'] ?? [];
        } catch (IndexNotFoundException) {
            // No index for this locale yet: no result, the index is built at activation or with tntsearch:indexes.
            Tlog::getInstance()->addInfo(\sprintf('TntSearch: no product index for the locale %s.', $locale));

            return [];
        } catch (\Throwable $exception) {
            Tlog::getInstance()->addError('TntSearch: product search failed: '.$exception->getMessage());

            return [];
        }

        $ids = array_values(array_unique(array_map('intval', $matchingIds)));

        usort($ids, static fn (int $a, int $b): int => ($scores[$b] ?? 0) <=> ($scores[$a] ?? 0));

        return \array_slice($ids, 0, $limit);
    }
}
