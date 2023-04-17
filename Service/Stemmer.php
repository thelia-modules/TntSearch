<?php

namespace TntSearch\Service;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use TntSearch\Event\StemmerEvent;

class Stemmer
{
    protected string $defaultStemmer = \TeamTNT\TNTSearch\Stemmer\PorterStemmer::class;

    /** @var string[] */
    protected array $stemmers = [
        'fr_FR' => \TntSearch\Stemmer\FrenchStemmer::class,
        'it_IT' => \TeamTNT\TNTSearch\Stemmer\ItalianStemmer::class,
        'de_DE' => \TeamTNT\TNTSearch\Stemmer\GermanStemmer::class,
        'pt_PT' => \TeamTNT\TNTSearch\Stemmer\PolishStemmer::class,
        'ru_RU' => \TeamTNT\TNTSearch\Stemmer\RussianStemmer::class,
    ];

    public function __construct(protected EventDispatcherInterface $dispatcher) {}

    public function getStemmer(string $locale = null): string
    {
        $stemmerEvent = new StemmerEvent();
        $stemmerEvent
            ->setStemmers($this->stemmers)
            ->setDefaultStemmer($this->defaultStemmer);

        $this->dispatcher->dispatch($stemmerEvent, StemmerEvent::EXTEND_STEMMERS);

        $stemmers = $stemmerEvent->getStemmers();

        if (!$locale || !isset($stemmers[$locale])) {
            return $stemmerEvent->getDefaultStemmer();
        }

        return $stemmers[$locale];
    }
}