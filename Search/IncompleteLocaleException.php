<?php

declare(strict_types=1);

namespace TntSearch\Search;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
final class IncompleteLocaleException extends \RuntimeException
{
    public function __construct(public readonly string $locale)
    {
        parent::__construct(\sprintf('The search index is not complete in %s yet, the database answers.', $locale));
    }
}
