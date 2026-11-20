<?php

declare(strict_types=1);

namespace TntSearch\Search;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class EngineOutage
{
    public const int SECONDS = 30;

    private const string KEY = 'tntsearch_engine_outage';

    public function __construct(
        #[Autowire(service: 'cache.app')]
        private CacheItemPoolInterface $cache,
    ) {
    }

    public function isOngoing(): bool
    {
        return $this->cache->getItem(self::KEY)->isHit();
    }

    public function begin(): void
    {
        $this->cache->save($this->cache->getItem(self::KEY)->set(true)->expiresAfter(self::SECONDS));
    }
}
