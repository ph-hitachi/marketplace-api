<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Pagination\Paginator;

class CacheBuilder extends EloquentBuilder
{
    private const DEFAULT_CACHE_TTL = 86400; // Fallback to 24 hours

    protected bool $shouldCache = false;
    protected ?int $cacheTtl = null;
    protected array $cacheTags = [];

    /**
     * Mark this query for cache intercept.
     *
     * @param int|null $ttl Cache lifetime in seconds (defaults to DEFAULT_CACHE_TTL)
     * @param array $tags Extra tags to group this query under (useful for custom groupings)
     */
    public function cached(?int $ttl = null, array $tags = []): self
    {
        $this->shouldCache = true;
        $this->cacheTtl = $ttl;
        $this->cacheTags = $tags;

        return $this;
    }

    /**
     * Intercept default Collection retrieval and serve from cache.
     */
    public function get($columns = ['*'])
    {
        if (!$this->shouldCache) {
            return parent::get($columns);
        }

        $this->shouldCache = false;

        return $this->remember(fn() => parent::get($columns));
    }

    /**
     * Intercept default first-record retrieval and serve from cache.
     */
    public function first($columns = ['*'])
    {
        if (!$this->shouldCache) {
            return parent::first($columns);
        }

        $this->shouldCache = false;

        return $this->remember(fn() => parent::first($columns));
    }

    /**
     * Intercept paginated query execution and serve from cache.
     *
     * Clones the query builder and applies pagination parameters (forPage)
     * BEFORE generating the raw SQL. This ensures that the generated cache key
     * is completely deterministic and includes the exact SQL limit and offset.
     */
    public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null, $total = null)
    {
        if (!$this->shouldCache) {
            return parent::paginate($perPage, $columns, $pageName, $page, $total);
        }

        $pageNumber = $page ?: Paginator::resolveCurrentPage($pageName);
        $perPage = $perPage ?: $this->model->getPerPage();

        // Apply limit/offset to a clone of the builder to ensure it is in the generated SQL key
        $paginatedBuilder = $this->clone()->forPage($pageNumber, $perPage);
        $cacheKey = md5($paginatedBuilder->toRawSql());

        $ttl = $this->cacheTtl ?? self::DEFAULT_CACHE_TTL;
        $cacheTags = $this->generateCacheTags();

        $this->shouldCache = false;

        return Cache::remember(
            tag:      $cacheTags,
            key:      $cacheKey,
            ttl:      $ttl,
            callback: fn() => parent::paginate($perPage, $columns, $pageName, $page, $total)
        );
    }

    /**
     * Build the unique cache key for non-paginated queries.
     */
    protected function generateCacheKey(): string
    {
        return md5($this->toRawSql());
    }

    /**
     * Build the array of cache tags using the model class.
     */
    protected function generateCacheTags(): array
    {
        return array_merge([get_class($this->getModel())], $this->cacheTags);
    }

    /**
     * Core wrapper caching pipeline for non-paginated queries.
     */
    protected function remember(\Closure $callback)
    {
        $cacheKey = $this->generateCacheKey();
        $cacheTags = $this->generateCacheTags();
        $ttl = $this->cacheTtl ?? self::DEFAULT_CACHE_TTL;

        return Cache::remember(
            tag:      $cacheTags,
            key:      $cacheKey,
            ttl:      $ttl,
            callback: $callback
        );
    }
}
