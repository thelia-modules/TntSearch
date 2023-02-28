<?php

namespace TntSearch\Service\Provider;

use ReflectionClass;
use ReflectionException;
use TntSearch\Index\TntSearchIndexInterface;

class IndexationProvider
{
    /** @var TntSearchIndexInterface[] */
    protected array $indexes;

    /**
     * @param $index
     * @return void
     * @throws ReflectionException
     */
    public function addIndex($index): void
    {
        $reflectionClass = new ReflectionClass($index);
        $indexType = strtolower($reflectionClass->getShortName());

        $this->indexes[$indexType] = $index;
    }

    /**
     * @return void
     */
    public function indexAll(): void
    {
        foreach ($this->indexes as $index) {
            $index->index();
        }
    }

    /**
     * @param string $indexType
     * @return TntSearchIndexInterface
     */
    public function getIndex(string $indexType): TntSearchIndexInterface
    {
        return $this->indexes[$indexType];
    }

    /**
     * @return TntSearchIndexInterface[]
     */
    public function getIndexes(): array
    {
        return $this->indexes;
    }

    /**
     * @param array $indexTypes
     * @return TntSearchIndexInterface[]
     */
    public function findIndexes(array $indexTypes): array
    {
        return array_intersect_key(
            $this->getIndexes(),
            array_flip($indexTypes)
        );
    }
}