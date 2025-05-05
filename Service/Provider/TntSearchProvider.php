<?php

namespace TntSearch\Service\Provider;

use Symfony\Component\Filesystem\Filesystem;
use TntSearch\Service\Support\TNTGeoSearch;
use TntSearch\Service\Stemmer;
use TntSearch\Service\StopWord;
use TntSearch\Service\Support\TntGeoIndexer;
use TntSearch\Service\Support\TntSearch;

class TntSearchProvider
{
    const INDEXES_DIR = THELIA_LOCAL_DIR . "TNTIndexes";

    public function __construct(
        protected Stemmer  $stemmer,
        protected StopWord $stopWord
    )
    {
    }

    public function getTntSearch(string $tokenizer = null, string $locale = null): TntSearch
    {
        return $this->buildTntSearch(
            $this->stemmer->getStemmer($locale),
            $locale ? $this->stopWord->getStopWords($locale) : [],
            $tokenizer
        );
    }

    public function getGeoTntIndexer(): TntGeoIndexer
    {
        $geoIndexer = new TntGeoIndexer();
        $geoIndexer->loadConfig($this->getConfigs());
        return $geoIndexer;
    }

    public function getGeoTntSearch(string $indexName): TNTGeoSearch
    {
        $geoSearch = new TNTGeoSearch();
        $geoSearch->loadConfig($this->getConfigs());
        $geoSearch->selectIndex($indexName);

        return $geoSearch;
    }

    public function buildTntSearch(string $stemmer, array $stopWords = [], string $tokenizer = null): TntSearch
    {
        $tnt = new TntSearch($this->getConfigs($stemmer, $tokenizer));

        if (!empty($stopWords)) {
            $tnt->setStopWords($stopWords);
        }

        return $tnt;
    }


    protected function getConfigs(?string $stemmer = null, ?string $tokenizer = null): array
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
            'driver' => 'sqlite'
        ]);

        return $config;
    }
}
