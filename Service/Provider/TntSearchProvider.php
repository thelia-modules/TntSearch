<?php

namespace TntSearch\Service\Provider;

use Symfony\Component\Filesystem\Filesystem;
use TntSearch\Service\Stemmer;
use TntSearch\Service\StopWord;
use TntSearch\Service\Support\TntSearch;

class TntSearchProvider
{
    const INDEXES_DIR = THELIA_LOCAL_DIR . "TNTIndexes";

    public function __construct(
        protected Stemmer $stemmer,
        protected StopWord $stopWord
    ) {}

    public function getTntSearch(string $tokenizer = null , string $locale = null): TntSearch
    {
        return $this->buildTntSearch(
            $this->stemmer->getStemmer($locale),
            $locale ? $this->stopWord->getStopWords($locale) : [],
            $tokenizer
        );
    }

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
            'tokenizer' => $tokenizer,
            'driver' => ''
        ]);

        $tnt = new TntSearch($config);

        if (!empty($stopWords)) {
            $tnt->setStopWords($stopWords);
        }

        return $tnt;
    }
}