<?php

declare(strict_types=1);

namespace TntSearch\Search;

final readonly class DocumentIndexStatus
{
    public function __construct(
        public string $family,
        public ?string $locale,
        public int $documents,
        public \DateTimeImmutable $lastWrite,
    ) {
    }
}
