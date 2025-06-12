<?php

namespace Laraduck\EloquentDuckDB\Cache;

use Illuminate\Contracts\Cache\Store;
use Illuminate\Database\Query\Builder;
use Closure;

/**
 * Analytical query cache for DuckDB operations.
 * 
 * Provides caching strategies optimized for analytical workloads
 * where queries are expensive but results can be cached for longer periods.
 */
class AnalyticalQueryCache
{
    protected Store $cache;
    protected int $defaultTtl;
    protected string $keyPrefix = 'duckdb_query:';
    
    public function __construct(Store $cache, int $defaultTtl = 3600)
    {
        $this->cache = $cache;
        $this->defaultTtl = $defaultTtl;
    }
    
    /**
     * Remember a query result with the given key and TTL.
     */
    public function remember(string $key, $query, ?int $ttl = null): mixed
    {
        $cacheKey = $this->keyPrefix . $key;
        $ttl = $ttl ?? $this->defaultTtl;
        
        return $this->cache->remember($cacheKey, $ttl, function () use ($query) {
            return $this->executeQuery($query);
        });
    }
    
    /**
     * Remember a query result forever (until manually invalidated).
     */
    public function rememberForever(string $key, $query): mixed
    {
        $cacheKey = $this->keyPrefix . $key;
        
        return $this->cache->rememberForever($cacheKey, function () use ($query) {
            return $this->executeQuery($query);
        });
    }
    
    /**
     * Cache a query result based on its SQL and bindings.
     */
    public function rememberQuery(Builder $query, ?int $ttl = null): mixed
    {
        $key = $this->generateQueryKey($query);
        
        return $this->remember($key, $query, $ttl);
    }
    
    /**
     * Cache an aggregation query result.
     */
    public function rememberAggregation(Builder $query, string $function, array $columns = ['*'], ?int $ttl = null): mixed
    {
        $key = $this->generateAggregationKey($query, $function, $columns);
        
        return $this->remember($key, function () use ($query, $function, $columns) {
            return $query->aggregate($function, $columns);
        }, $ttl);
    }
    
    /**
     * Cache a file-based query result.
     */
    public function rememberFileQuery(string $filePath, Builder $query, ?int $ttl = null): mixed
    {
        $key = $this->generateFileQueryKey($filePath, $query);
        
        // Use file modification time as part of the cache key for auto-invalidation
        $fileModTime = file_exists($filePath) ? filemtime($filePath) : 0;
        $key .= ':' . $fileModTime;
        
        return $this->remember($key, $query, $ttl);
    }
    
    /**
     * Invalidate cache entries matching a pattern.
     */
    public function invalidatePattern(string $pattern): void
    {
        // This is a simplified implementation - in production, you might want
        // to use Redis SCAN or maintain a key registry for efficient pattern matching
        $this->cache->flush();
    }
    
    /**
     * Invalidate all cached queries for a specific table.
     */
    public function invalidateTable(string $table): void
    {
        $this->invalidatePattern("*:table:{$table}:*");
    }
    
    /**
     * Invalidate all cached file queries for a specific file.
     */
    public function invalidateFile(string $filePath): void
    {
        $this->invalidatePattern("*:file:" . md5($filePath) . ":*");
    }
    
    /**
     * Clear all cached queries.
     */
    public function flush(): void
    {
        $this->cache->flush();
    }
    
    /**
     * Get cache statistics (if supported by the cache store).
     */
    public function getStats(): array
    {
        return [
            'prefix' => $this->keyPrefix,
            'default_ttl' => $this->defaultTtl,
            'cache_driver' => get_class($this->cache),
        ];
    }
    
    /**
     * Generate a cache key for a query.
     */
    protected function generateQueryKey(Builder $query): string
    {
        $sql = $query->toSql();
        $bindings = $query->getBindings();
        $connection = $query->getConnection()->getName();
        
        $components = [
            'connection' => $connection,
            'sql' => $sql,
            'bindings' => $bindings,
        ];
        
        // Add table name if available for targeted invalidation
        if (isset($query->from)) {
            $components['table'] = $query->from;
        }
        
        return md5(json_encode($components));
    }
    
    /**
     * Generate a cache key for aggregation queries.
     */
    protected function generateAggregationKey(Builder $query, string $function, array $columns): string
    {
        $baseKey = $this->generateQueryKey($query);
        $aggregationKey = md5($function . ':' . implode(',', $columns));
        
        return "agg:{$baseKey}:{$aggregationKey}";
    }
    
    /**
     * Generate a cache key for file-based queries.
     */
    protected function generateFileQueryKey(string $filePath, Builder $query): string
    {
        $baseKey = $this->generateQueryKey($query);
        $fileKey = md5($filePath);
        
        return "file:{$fileKey}:{$baseKey}";
    }
    
    /**
     * Execute a query based on its type.
     */
    protected function executeQuery($query): mixed
    {
        if ($query instanceof Builder) {
            return $query->get();
        }
        
        if ($query instanceof Closure) {
            return $query();
        }
        
        if (is_callable($query)) {
            return call_user_func($query);
        }
        
        throw new \InvalidArgumentException('Query must be a Builder instance, Closure, or callable');
    }
    
    /**
     * Set the default TTL for cached queries.
     */
    public function setDefaultTtl(int $ttl): self
    {
        $this->defaultTtl = $ttl;
        return $this;
    }
    
    /**
     * Set the cache key prefix.
     */
    public function setKeyPrefix(string $prefix): self
    {
        $this->keyPrefix = $prefix;
        return $this;
    }
    
    /**
     * Create a cached query scope.
     */
    public function scope(string $scope): CachedQueryScope
    {
        return new CachedQueryScope($this, $scope);
    }
}

/**
 * Scoped cache manager for organizing related queries.
 */
class CachedQueryScope
{
    protected AnalyticalQueryCache $cache;
    protected string $scope;
    
    public function __construct(AnalyticalQueryCache $cache, string $scope)
    {
        $this->cache = $cache;
        $this->scope = $scope;
    }
    
    /**
     * Remember a query within this scope.
     */
    public function remember(string $key, $query, ?int $ttl = null): mixed
    {
        $scopedKey = $this->scope . ':' . $key;
        return $this->cache->remember($scopedKey, $query, $ttl);
    }
    
    /**
     * Invalidate all queries within this scope.
     */
    public function invalidate(): void
    {
        $this->cache->invalidatePattern("*{$this->scope}:*");
    }
    
    /**
     * Create a sub-scope.
     */
    public function subScope(string $subScope): self
    {
        return new self($this->cache, $this->scope . ':' . $subScope);
    }
}