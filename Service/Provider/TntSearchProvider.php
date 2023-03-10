<?php

namespace TntSearch\Service\Provider;

use Symfony\Component\Filesystem\Filesystem;
use TntSearch\Service\Stemmer;
use TntSearch\Service\StopWord;
use TntSearch\Service\Support\TntSearch;

class TntSearchProvider
{
    /** @var string */
    public const INDEXES_DIR = THELIA_LOCAL_DIR . "TNTIndexes";

    /** @var Stemmer */
    protected $stemmer;

    /** @var StopWord */
    protected $stopWord;

    public function __construct( Stemmer $stemmer, StopWord $stopWord )
    {
        $this->stopWord = $stopWord;
        $this->stemmer = $stemmer;
    }

    /**
     * @param string|null $locale
     * @param string|null $tokenizer
     * @return TntSearch
     */
    public function getTntSearch(string $tokenizer = null , string $locale = null): TntSearch
    {
        return $this->buildTntSearch(
            $this->stemmer->getStemmer($locale),
            $locale ? $this->stopWord->getStopWords($locale) : [],
            $tokenizer
        );
    }

    /**
     * @param string $stemmer
     * @param array $stopWords
     * @param string|null $tokenizer
     * @return TntSearch
     */
    public function buildTntSearch(string $stemmer, array $stopWords = [], string $tokenizer = null): TntSearch
    {
        if (!is_dir(self::INDEXES_DIR)) {
            $fs = new Filesystem();
            $fs->mkdir(self::INDEXES_DIR);
        }

        // You need to empty the modes to set the sql_mode parameter to null
        $config = array_merge([
            'storage' => self::INDEXES_DIR,
            'modes' => [''],
            'stemmer' => $stemmer,
            'tokenizer' => $tokenizer
        ]);

        $tnt = new TntSearch($config);

        if (!empty($stopWords)) {
            $tnt->setStopWords($stopWords);
        }

        return $tnt;
    }
}