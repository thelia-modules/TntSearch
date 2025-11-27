<?php

namespace TntSearch\Service\Provider;

use ReflectionClass;
use ReflectionException;
use Symfony\Component\Console\Output\OutputInterface;
use Thelia\Log\Tlog;
use TntSearch\Index\BaseIndex;
use TntSearch\Index\TntSearchIndexInterface;

class IndexationProvider
{
    /** @var BaseIndex[] */
    protected array $indexes;

    /**
     * @throws ReflectionException
     */
    public function addIndex($index): void
    {
        $reflectionClass = new ReflectionClass($index);
        $indexType = strtolower($reflectionClass->getShortName());

        $this->indexes[$indexType] = $index;
    }

    public function indexAll(?OutputInterface $output = null): void
    {
        foreach ($this->indexes as $index) {
            try {
                $output?->write('<info>' . $index::class . ' </info>');
                $index->index();
            } catch (\Exception $e) {
                $output?->write('<error>' . $e->getMessage() . '</error>');
                Tlog::getInstance()->addError('Error on TntSearch indexation ' . $e->getMessage());
            }
        }
    }

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