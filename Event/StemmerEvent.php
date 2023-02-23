<?php

namespace TntSearch\Event;

use Thelia\Core\Event\ActionEvent;

class StemmerEvent extends ActionEvent
{
    const EXTEND_STEMMERS = 'action.tntsearch.extended.stemmer';

    /**
     * @var string
     */
    private $defaultStemmer;

    /**
     * @var string[]
     */
    private $stemmers;

    /**
     * @return string
     */
    public function getDefaultStemmer(): string
    {
        return $this->defaultStemmer;
    }

    /**
     * @param string $defaultStemmer
     * @return $this
     */
    public function setDefaultStemmer(string $defaultStemmer): StemmerEvent
    {
        $this->defaultStemmer = $defaultStemmer;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getStemmers(): array
    {
        return $this->stemmers;
    }

    /**
     * @param array $stemmers
     * @return $this
     */
    public function setStemmers(array $stemmers): StemmerEvent
    {
        $this->stemmers = $stemmers;
        return $this;
    }
}