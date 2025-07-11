<?php

namespace TntSearch\Event;

use Thelia\Core\Event\ActionEvent;

class SaveRequestEvent extends ActionEvent
{
    const SAVE_REQUEST = 'action.tntsearch.save.request';
    protected string $searchWords;

    protected string $locale;

    protected string $index;
    protected int $hits;

    /**
     * @return array
     */
    public function getSearchWords(): string
    {
        return $this->searchWords;
    }

    /**
     * @param string $searchWords
     * @return $this
     */
    public function setSearchWords(string $searchWords): SaveRequestEvent
    {
        $this->searchWords = $searchWords;
        return $this;
    }

    /**
     * @return string
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * @param string $locale
     * @return $this
     */
    public function setLocale(string $locale): SaveRequestEvent
    {
        $this->locale = $locale;
        return $this;
    }

    /**
     * @return string
     */
    public function getIndex(): string
    {
        return $this->index;
    }

    /**
     * @param string $index
     * @return SaveRequestEvent
     */
    public function setIndex(string $index): SaveRequestEvent
    {
        $this->index = $index;
        return $this;
    }

    public function getHits(): int
    {
        return $this->hits;
    }

    public function setHits(int $hits): void
    {
        $this->hits = $hits;
    }
}