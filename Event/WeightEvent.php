<?php

namespace TntSearch\Event;

use Thelia\Core\Event\ActionEvent;

class WeightEvent extends ActionEvent
{
    const WEIGHT = 'action.tntsearch.weight';

    protected array $fieldWeights;

    public function getFieldWeights(): array
    {
        return $this->fieldWeights;
    }

    public function getFieldWeight(string $key): int
    {
        return $this->fieldWeights[$key] ?? 1;
    }

    public function setFieldWeights(array $fieldWeights): void
    {
        $this->fieldWeights = $fieldWeights;
    }
}
