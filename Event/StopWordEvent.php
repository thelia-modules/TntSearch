<?php

namespace TntSearch\Event;

use Thelia\Core\Event\ActionEvent;

class StopWordEvent extends ActionEvent
{
    const GET_STOP_WORDS = 'action.tntsearch.get.stop.words';

    /** @var string */
    protected $locale;

    /** @var array */
    protected $stopWords = [];

    /**
     * @return string
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * @param string $locale
     * @return StopWordEvent
     */
    public function setLocale(string $locale): StopWordEvent
    {
        $this->locale = $locale;
        return $this;
    }

    /**
     * @return array
     */
    public function getStopWords(): array
    {
        return $this->stopWords;
    }

    /**
     * @param array $stopWords
     * @return $this
     */
    public function setStopWords(array $stopWords): StopWordEvent
    {
        $this->stopWords = $stopWords;
        return $this;
    }
}