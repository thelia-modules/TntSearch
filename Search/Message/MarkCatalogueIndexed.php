<?php

declare(strict_types=1);

namespace TntSearch\Search\Message;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
final readonly class MarkCatalogueIndexed
{
    /**
     * @param list<string>      $families
     * @param list<string>|null $locales
     */
    public function __construct(
        public array $families = [],
        public ?array $locales = null,
        public int $postponements = 0,
    ) {
    }
}
