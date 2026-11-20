<?php

declare(strict_types=1);

namespace TntSearch\Engine;

use TeamTNT\TNTSearch\Support\Collection;

trait WeightedWordlistTrait
{
    abstract protected function weightOf(string $field): int;

    public function saveWordlist(Collection $stems)
    {
        $terms = [];
        $stems->map(function ($column, $field) use (&$terms) {
            $weight = $this->weightOf((string) $field);
            foreach ($column as $term) {
                if (\array_key_exists($term, $terms)) {
                    $terms[$term]['hits'] += $weight;
                    $terms[$term]['docs'] = 1;
                } else {
                    $terms[$term] = [
                        'hits' => $weight,
                        'docs' => 1,
                        'id' => 0,
                    ];
                }
            }
        });

        foreach ($terms as $key => $term) {
            try {
                $this->insertWordlistStmt->bindParam(':keyword', $key);
                $this->insertWordlistStmt->bindParam(':hits', $term['hits']);
                $this->insertWordlistStmt->bindParam(':docs', $term['docs']);
                $this->insertWordlistStmt->execute();

                $terms[$key]['id'] = $this->index->query('SELECT MAX(id) FROM wordlist')->fetchColumn();
                if ($this->inMemory) {
                    $this->inMemoryTerms[$key] = $terms[$key]['id'];
                }
            } catch (\Exception $e) {
                if ($e->getCode() == 23000) {
                    $this->updateWordlistStmt->bindValue(':docs', $term['docs']);
                    $this->updateWordlistStmt->bindValue(':hits', $term['hits']);
                    $this->updateWordlistStmt->bindValue(':keyword', $key);
                    $this->updateWordlistStmt->execute();
                    if (!$this->inMemory || !\array_key_exists($key, $this->inMemoryTerms)) {
                        $this->selectWordlistStmt->bindValue(':keyword', $key);
                        $this->selectWordlistStmt->execute();
                        $res = $this->selectWordlistStmt->fetch(\PDO::FETCH_ASSOC);
                        $terms[$key]['id'] = $res['id'];
                    } else {
                        $terms[$key]['id'] = $this->inMemoryTerms[$key];
                    }
                } else {
                    throw $e;
                }

                $this->statementsPrepared = false;
                $this->prepareStatementsForIndex();
            }
        }

        return $terms;
    }
}
