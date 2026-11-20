<?php

declare(strict_types=1);

namespace TntSearch\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use TeamTNT\TNTSearch\Stemmer\PorterStemmer;
use TeamTNT\TNTSearch\Stemmer\PortugueseStemmer;
use TntSearch\Service\Stemmer;

final class StemmerTest extends TestCase
{
    public function testPortugueseTextIsStemmedAsPortuguese(): void
    {
        self::assertSame(PortugueseStemmer::class, (new Stemmer(new EventDispatcher()))->getStemmer('pt_PT'));
    }

    public function testALocaleWithoutStemmerOfItsOwnFallsBackOnEnglish(): void
    {
        self::assertSame(PorterStemmer::class, (new Stemmer(new EventDispatcher()))->getStemmer('nl_NL'));
    }
}
