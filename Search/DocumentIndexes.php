<?php

declare(strict_types=1);

namespace TntSearch\Search;

use Symfony\Contracts\Service\ResetInterface;
use TntSearch\Engine\DocumentEngine;
use TntSearch\Service\Provider\TntSearchProvider;
use TntSearch\Service\Stemmer;
use TntSearch\Service\StopWord;
use TntSearch\Service\Support\TntSearch;
use TntSearch\Tokenizer\Tokenizer;

class DocumentIndexes implements ResetInterface
{
    public const int READ_TIMEOUT_MILLISECONDS = 250;

    public const int WRITE_TIMEOUT_MILLISECONDS = 10000;

    /** @var array<string, TntSearch> */
    private array $readers = [];

    /** @var array<string, list<string>> */
    private array $stopWords = [];

    private const string PREFIX = 'document--';

    private const string SEPARATOR = '--';

    private const string WITHOUT_LOCALE = 'all';

    private const string EXTENSION = '.index';

    private const string COMPLETE = '.complete';
    private const string EMPTY_LOCALE = 'empty';

    private const string FAILED = '.failed';

    public const int LOCK_WAIT_MILLISECONDS = 10_000;

    private const int LOCK_RETRY_MICROSECONDS = 20_000;

    public function __construct(
        private readonly TntSearchProvider $tntSearchProvider,
        private readonly Stemmer $stemmer,
        private readonly StopWord $stopWord,
    ) {
    }

    public function fileName(string $family, ?string $locale): string
    {
        self::assertSafe($family, $locale);

        return self::PREFIX.$family.self::SEPARATOR.($locale ?? self::WITHOUT_LOCALE).self::EXTENSION;
    }

    public function states(string $family, ?string $locale): ?DocumentEngine
    {
        $engine = new DocumentEngine();
        $engine->loadConfig($this->config($locale));
        $engine->disableOutput(true);

        return $engine->open($this->fileName($family, $locale), create: false, forWriting: false) ? $engine : null;
    }

    public function writer(string $family, ?string $locale, bool $create): ?DocumentEngine
    {
        $engine = new DocumentEngine();
        $engine->loadConfig($this->config($locale));
        $engine->disableOutput(true);

        $existed = is_file($this->storage().$this->fileName($family, $locale));

        if (!$engine->open($this->fileName($family, $locale), $create)) {
            return null;
        }

        $marker = $this->storage().$this->markerName($family, $locale);

        if (!$existed && is_file($marker) && self::EMPTY_LOCALE === file_get_contents($marker)) {
            $this->writeMarker($marker, '');
        }

        $engine->waitAtMost(self::WRITE_TIMEOUT_MILLISECONDS);
        $engine->setStemmer(new ($this->stemmer->getStemmer($locale))());
        $engine->setTokenizer(new Tokenizer());
        $engine->setStopWords($this->stopWords($locale));

        return $engine;
    }

    public function reader(string $family, ?string $locale): ?TntSearch
    {
        $fileName = $this->fileName($family, $locale);

        if (!is_file($this->storage().$fileName)) {
            unset($this->readers[$fileName]);

            return null;
        }

        if (!isset($this->readers[$fileName])) {
            $search = new TntSearch($this->config($locale));
            $search->setStopWords($this->stopWords($locale));
            $search->selectIndex($fileName);

            if ($search->engine instanceof DocumentEngine) {
                $search->engine->waitAtMost(self::READ_TIMEOUT_MILLISECONDS);
            }

            $this->readers[$fileName] = $search;
        }

        return $this->readers[$fileName];
    }

    /**
     * @return list<string>
     */
    public function families(): array
    {
        $families = [];

        foreach ($this->files() as $file) {
            $families[] = substr($file, \strlen(self::PREFIX), (int) strrpos($file, self::SEPARATOR) - \strlen(self::PREFIX));
        }

        return array_values(array_unique($families));
    }

    /**
     * @return list<string>
     */
    public function files(?string $family = null): array
    {
        if (null !== $family) {
            self::assertSafe($family, null);
        }

        $pattern = $this->storage().self::PREFIX.(null === $family ? '*' : $family).self::SEPARATOR.'*'.self::EXTENSION;

        return array_values(array_filter(
            array_map(basename(...), glob($pattern) ?: []),
            static fn (string $file): bool => null === $family || str_starts_with($file, self::PREFIX.$family.self::SEPARATOR) && !str_contains(substr($file, \strlen(self::PREFIX.$family.self::SEPARATOR)), self::SEPARATOR),
        ));
    }

    /**
     * @return list<DocumentIndexStatus>
     */
    public function statuses(): array
    {
        $statuses = [];

        foreach ($this->files() as $file) {
            $name = substr($file, \strlen(self::PREFIX), -\strlen(self::EXTENSION));
            $separator = (int) strrpos($name, self::SEPARATOR);
            $family = substr($name, 0, $separator);
            $locale = substr($name, $separator + \strlen(self::SEPARATOR));
            $locale = self::WITHOUT_LOCALE === $locale ? null : $locale;
            $states = $this->states($family, $locale);
            $lastWrite = max(array_map(fn (string $path): int => is_file($this->storage().$path) ? (int) filemtime($this->storage().$path) : 0, [$file, $file.'-wal']));

            $statuses[] = new DocumentIndexStatus($family, $locale, $states?->documentCount() ?? 0, (new \DateTimeImmutable())->setTimestamp($lastWrite));
        }

        return $statuses;
    }

    public function markComplete(string $family, ?string $locale): void
    {
        $this->exclusively($family, function () use ($family, $locale): void {
            $holdsDocuments = is_file($this->storage().$this->fileName($family, $locale));

            $this->writeMarker($this->storage().$this->markerName($family, $locale), $holdsDocuments ? '' : self::EMPTY_LOCALE);
        });
    }

    private function writeMarker(string $marker, string $content): void
    {
        $temporary = $marker.'.'.bin2hex(random_bytes(4));

        if (false === file_put_contents($temporary, $content) || !rename($temporary, $marker)) {
            @unlink($temporary);

            throw new \RuntimeException(\sprintf('The search index marker %s cannot be written.', basename($marker)));
        }
    }

    public function isComplete(string $family, ?string $locale): bool
    {
        $marker = $this->storage().$this->markerName($family, $locale);

        if (!is_file($marker)) {
            return false;
        }

        return is_file($this->storage().$this->fileName($family, $locale)) || self::EMPTY_LOCALE === file_get_contents($marker);
    }

    public function forgetCompletion(string $family): void
    {
        self::assertSafe($family, null);

        foreach (glob($this->storage().self::PREFIX.$family.self::SEPARATOR.'*'.self::COMPLETE) ?: [] as $marker) {
            unlink($marker);
        }
    }

    public function recordFailure(string $family): void
    {
        touch($this->failureMarker($family));
    }

    public function hasFailed(string $family): bool
    {
        return is_file($this->failureMarker($family));
    }

    public function forgetFailure(string $family): void
    {
        if (is_file($this->failureMarker($family))) {
            unlink($this->failureMarker($family));
        }
    }

    private function failureMarker(string $family): string
    {
        self::assertSafe($family, null);

        return $this->storage().self::PREFIX.$family.self::FAILED;
    }

    public function remove(string $file): void
    {
        foreach ([$file, $file.'-wal', $file.'-shm'] as $path) {
            if (is_file($this->storage().$path)) {
                unlink($this->storage().$path);
            }
        }
    }

    private function markerName(string $family, ?string $locale): string
    {
        return substr($this->fileName($family, $locale), 0, -\strlen(self::EXTENSION)).self::COMPLETE;
    }

    /**
     * @template T
     *
     * @param callable(): T $write
     *
     * @return T
     */
    public function exclusively(string $family, callable $write, int $waitMilliseconds = self::LOCK_WAIT_MILLISECONDS): mixed
    {
        self::assertSafe($family, null);
        $lock = fopen($this->storage().self::PREFIX.$family.'.lock', 'c');
        $deadline = hrtime(true) + $waitMilliseconds * 1_000_000;

        while (false !== $lock && !flock($lock, \LOCK_EX | \LOCK_NB)) {
            if (hrtime(true) >= $deadline) {
                fclose($lock);
                $lock = false;

                break;
            }

            usleep(self::LOCK_RETRY_MICROSECONDS);
        }

        if (false === $lock) {
            throw new \RuntimeException(\sprintf('The search index of the family %s cannot be locked.', $family));
        }

        try {
            return $write();
        } finally {
            flock($lock, \LOCK_UN);
            fclose($lock);
        }
    }

    public function reset(): void
    {
        $this->readers = [];
        $this->stopWords = [];
    }

    public function storage(): string
    {
        return rtrim($this->tntSearchProvider->storageDirectory(), \DIRECTORY_SEPARATOR).\DIRECTORY_SEPARATOR;
    }

    private static function assertSafe(string $family, ?string $locale): void
    {
        if (1 !== preg_match('/^[a-z0-9_]+$/', $family) || (null !== $locale && 1 !== preg_match('/^[a-z]{2,3}_[A-Za-z]{2,4}$/', $locale))) {
            throw new \InvalidArgumentException(\sprintf('"%s" in "%s" does not name a search index.', $family, $locale ?? '-'));
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function config(?string $locale): array
    {
        return [
            'storage' => $this->storage(),
            'engine' => DocumentEngine::class,
            'driver' => 'sqlite',
            'database' => ':memory:',
            'stemmer' => $this->stemmer->getStemmer($locale),
            'tokenizer' => Tokenizer::class,
        ];
    }

    /**
     * @return list<string>
     */
    private function stopWords(?string $locale): array
    {
        if (null === $locale) {
            return [];
        }

        return $this->stopWords[$locale] ??= array_values($this->stopWord->getStopWords($locale));
    }
}
