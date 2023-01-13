<?php

namespace TntSearch\Event;

use Thelia\Core\Event\ActionEvent;

abstract class IndexesEvent extends ActionEvent
{
    /** @var string|null */
    protected $locale;

    /** @var string|null */
    protected $sqlQuery;

    /**
     * @return string|null
     */
    public function getLocale(): ?string
    {
        return $this->locale;
    }

    /**
     * @param string|null $locale
     * @return IndexesEvent
     */
    public function setLocale(?string $locale): IndexesEvent
    {
        $this->locale = $locale;
        return $this;
    }

    /**
     * @param string $sqlQuery
     * @return IndexesEvent
     */
    public function setSqlQuery(string $sqlQuery): IndexesEvent
    {
        $this->sqlQuery = $sqlQuery;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSqlQuery(): ?string
    {
        return $this->sqlQuery;
    }

    abstract public function buildSqlQuery(): void;
}