<?php

namespace TntSearch\Event;

use Thelia\Core\Event\ActionEvent;

class ExtendQueryEvent extends ActionEvent
{
    const EXTEND_QUERY = 'action.tntsearch.extend.query.';

    protected string $itemType;
    protected ?int $itemId;
    protected string $query;

    /**
     * @return string
     */
    public function getItemType(): string
    {
        return $this->itemType;
    }

    /**
     * @param string $itemType
     * @return ExtendQueryEvent
     */
    public function setItemType(string $itemType): ExtendQueryEvent
    {
        $this->itemType = $itemType;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getItemId(): ?int
    {
        return $this->itemId;
    }

    /**
     * @param int|null $itemId
     * @return ExtendQueryEvent
     */
    public function setItemId(?int $itemId): ExtendQueryEvent
    {
        $this->itemId = $itemId;
        return $this;
    }

    /**
     * @return string
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * @param string $query
     * @return ExtendQueryEvent
     */
    public function setQuery(string $query): ExtendQueryEvent
    {
        $this->query = $query;
        return $this;
    }
}