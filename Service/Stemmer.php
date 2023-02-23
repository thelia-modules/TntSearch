<?php

namespace TntSearch\Service;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use TntSearch\Event\StemmerEvent;

class Stemmer
{
    /** @var string */
    protected $defaultStemmer = \TeamTNT\TNTSearch\Stemmer\PorterStemmer::class;

    /** @var string[] */
    protected $stemmers = [
        'fr_FR' => \TntSearch\Stemmer\FrenchStemmer::class,
        'it_IT' => \TeamTNT\TNTSearch\Stemmer\ItalianStemmer::class,
        'de_DE' => \TeamTNT\TNTSearch\Stemmer\GermanStemmer::class,
        'pt_PT' => \TeamTNT\TNTSearch\Stemmer\PolishStemmer::class,
        'ru_RU' => \TeamTNT\TNTSearch\Stemmer\RussianStemmer::class,
    ];

    /** @var EventDispatcherInterface */
    private $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param string|null $locale
     * @return string
     */
    public function getStemmer(string $locale = null): string
    {
        $stemmerEvent = new StemmerEvent();
        $stemmerEvent
            ->setStemmers($this->stemmers)
            ->setDefaultStemmer($this->defaultStemmer);

        $this->dispatcher->dispatch(StemmerEvent::EXTEND_STEMMERS, $stemmerEvent);

        $stemmers = $stemmerEvent->getStemmers();

        if (!$locale || !isset($stemmers[$locale])) {
            return $stemmerEvent->getDefaultStemmer();
        }

        return $stemmers[$locale];
    }
}