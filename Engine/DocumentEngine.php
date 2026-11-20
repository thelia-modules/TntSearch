<?php

declare(strict_types=1);

namespace TntSearch\Engine;

use TeamTNT\TNTSearch\Engines\SqliteEngine;
use Thelia\Domain\Search\Contract\SearchScope;
use Thelia\Domain\Search\Document\SearchDocument;

class DocumentEngine extends SqliteEngine
{
    use WeightedWordlistTrait;

    public const int PREFIX_EXPANSIONS = 50;

    /** @var array<string, int> */
    private array $weights = [];

    public function open(string $indexName, bool $create, bool $forWriting = true): bool
    {
        if (!is_file($this->config['storage'].$indexName)) {
            if (!$create) {
                return false;
            }

            $this->createIndex($indexName);
        } else {
            $this->selectIndex($indexName);
        }

        if ($forWriting) {
            $this->index->exec('CREATE TABLE IF NOT EXISTS thelia_document (doc_id INTEGER PRIMARY KEY, visible INTEGER NOT NULL, scope TEXT NOT NULL)');
        }

        return true;
    }

    public function waitAtMost(int $milliseconds): void
    {
        $this->index->exec('PRAGMA busy_timeout = '.max(0, $milliseconds));
    }

    public function write(SearchDocument $document): void
    {
        $this->weights = $document->weights;
        $this->update($document->id, ['id' => $document->id] + $document->fields);

        $statement = $this->index->prepare('INSERT OR REPLACE INTO thelia_document (doc_id, visible, scope) VALUES (:id, :visible, :scope)');
        $statement->execute([':id' => $document->id, ':visible' => $document->visible ? 1 : 0, ':scope' => $document->scope->value]);
    }

    public function erase(int $id): void
    {
        $this->delete($id);
        $this->index->prepare('DELETE FROM thelia_document WHERE doc_id = :id')->execute([':id' => $id]);
    }

    /**
     * @param list<string> $prefixes
     *
     * @return array<int, float>
     */
    public function weightedHits(string $stem, int $maxDistance, array $prefixes, SearchScope $scope, bool $visibleOnly, float $approximation): array
    {
        $exactWords = [];
        $approachedWords = [];
        $exact = $this->index->prepare('SELECT id FROM wordlist WHERE term = :term');
        $exact->execute([':term' => mb_strtolower($stem)]);

        foreach ($exact->fetchAll(\PDO::FETCH_COLUMN) as $id) {
            $exactWords[(int) $id] = true;
        }

        if ($maxDistance > 0) {
            foreach ($this->wordsAround($stem, $maxDistance) as $id) {
                $approachedWords[$id] = true;
            }
        }

        foreach (array_unique(array_map(mb_strtolower(...), $prefixes)) as $prefix) {
            $beginning = $this->index->prepare("SELECT id FROM wordlist WHERE term LIKE :prefix ESCAPE '\\' ORDER BY num_hits DESC LIMIT ".self::PREFIX_EXPANSIONS);
            $beginning->execute([':prefix' => addcslashes($prefix, '%_\\').'%']);

            foreach ($beginning->fetchAll(\PDO::FETCH_COLUMN) as $id) {
                $approachedWords[(int) $id] = true;
            }
        }

        $approachedWords = array_diff_key($approachedWords, $exactWords);

        if ([] === $exactWords && [] === $approachedWords) {
            return [];
        }

        $exactIds = array_keys($exactWords) ?: [0];
        $allIds = [...array_keys($exactWords), ...array_keys($approachedWords)];
        $statement = $this->index->prepare(\sprintf(
            'SELECT d.doc_id, MAX(d.hit_count * CASE WHEN d.term_id IN (%s) THEN 1.0 ELSE ? END) FROM doclist d JOIN thelia_document t ON t.doc_id = d.doc_id WHERE d.term_id IN (%s) AND t.scope = ?%s GROUP BY d.doc_id',
            implode(',', array_fill(0, \count($exactIds), '?')),
            implode(',', array_fill(0, \count($allIds), '?')),
            $visibleOnly ? ' AND t.visible = 1' : '',
        ));
        $statement->execute([...$exactIds, $approximation, ...$allIds, $scope->value]);

        return array_map(floatval(...), $statement->fetchAll(\PDO::FETCH_KEY_PAIR));
    }

    public function documentCount(): int
    {
        return (int) $this->index->query('SELECT COUNT(*) FROM thelia_document')->fetchColumn();
    }

    public function beginWrites(): void
    {
        $this->index->beginTransaction();
    }

    public function commitWrites(): void
    {
        $this->index->commit();
    }

    public function rollBackWrites(): void
    {
        if ($this->index->inTransaction()) {
            $this->index->rollBack();
        }
    }

    protected function weightOf(string $field): int
    {
        return $this->weights[$field] ?? 1;
    }

    /**
     * @return list<int>
     */
    private function wordsAround(string $stem, int $maxDistance): array
    {
        $stem = mb_strtolower($stem);
        $candidates = $this->index->prepare('SELECT id, term FROM wordlist WHERE term LIKE :prefix ESCAPE \'\\\' ORDER BY num_hits DESC LIMIT '.$this->fuzzy_max_expansions);
        $candidates->execute([':prefix' => addcslashes(mb_substr($stem, 0, $this->fuzzy_prefix_length), '%_\\').'%']);
        $ids = [];

        foreach ($candidates->fetchAll(\PDO::FETCH_ASSOC) as $candidate) {
            if (self::characterDistance((string) $candidate['term'], $stem) <= $maxDistance) {
                $ids[] = (int) $candidate['id'];
            }
        }

        return $ids;
    }

    private static function characterDistance(string $left, string $right): int
    {
        $leftCharacters = mb_str_split($left);
        $rightCharacters = mb_str_split($right);
        $previous = range(0, \count($rightCharacters));

        foreach ($leftCharacters as $leftIndex => $leftCharacter) {
            $current = [$leftIndex + 1];

            foreach ($rightCharacters as $rightIndex => $rightCharacter) {
                $current[] = min(
                    $previous[$rightIndex + 1] + 1,
                    $current[$rightIndex] + 1,
                    $previous[$rightIndex] + ($leftCharacter === $rightCharacter ? 0 : 1),
                );
            }

            $previous = $current;
        }

        return $previous[\count($rightCharacters)];
    }
}
