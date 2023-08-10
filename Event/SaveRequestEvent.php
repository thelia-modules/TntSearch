<?php

namespace TntSearch\Event;

use Thelia\Core\Event\ActionEvent;

class SaveRequestEvent extends ActionEvent
{
    const SAVE_REQUEST = 'action.tntsearch.save.request';

    /** @var string */
    protected string $searchWords;

    /** @var string */
    protected string $locale;

    /** @var string */
    protected string $index;

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



}