<?php

namespace App\Service;

use Psr\Cache\CacheItemPoolInterface;

class CacheService
{
    private CacheItemPoolInterface $cache;

    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    public function deleteCache(string $cacheKey): void
    {
        $this->cache->deleteItem($cacheKey);
    }
}
