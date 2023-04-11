<?php

namespace TntSearch\Service;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use TeamTNT\TNTSearch\Stemmer\GermanStemmer;
use TeamTNT\TNTSearch\Stemmer\ItalianStemmer;
use TeamTNT\TNTSearch\Stemmer\PolishStemmer;
use TeamTNT\TNTSearch\Stemmer\PorterStemmer;
use TeamTNT\TNTSearch\Stemmer\RussianStemmer;
use TntSearch\Event\StemmerEvent;
use TntSearch\Stemmer\FrenchStemmer;

class Stemmer
{
    /** @var string */
    protected $defaultStemmer = PorterStemmer::class;

    /** @var string[] */
    protected $stemmers = [
        'fr_FR' => FrenchStemmer::class,
        'it_IT' => ItalianStemmer::class,
        'de_DE' => GermanStemmer::class,
        'pt_PT' => PolishStemmer::class,
        'ru_RU' => RussianStemmer::class,
    ];

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    public function __construct( EventDispatcherInterface $dispatcher )
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