<?php
namespace Morilog\InfinityCache;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Database\Query\Builder as QueryBuilder;

class Builder extends QueryBuilder
{
    /**
     * @var Repository
     */
    private $cache;

    /**
     * @var
     */
    private $cacheTag;

    /**
     * @param Repository $cache
     * @param ConnectionInterface $connection
     * @param Grammar $grammar
     * @param Processor $processor
     * @param $cacheTag
     */
    public function __construct(
        Repository $cache,
        ConnectionInterface $connection,
        Grammar $grammar,
        Processor $processor,
        $cacheTag
    ) {
        $this->cache = $cache;
        parent::__construct($connection, $grammar, $processor);
        $this->cacheTag = $cacheTag;
    }

    /**
     * Generate unique cache key from query and it bindings parameters
     *
     * @return string
     */
    public function generateCacheKey()
    {
        return sha1($this->connection->getName() . $this->toSql() . serialize($this->getBindings()));
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param  array $columns
     * @return array|static[]
     */
    public function get($columns = ['*'])
    {
        $cacheKey = $this->generateCacheKey();

        // Check cache for any result of query
        // if results exists, retrieve it from cache
        // else querying from db and store result to cache storage
        if (null === ($results = $this->cache->tags($this->cacheTag)->get($cacheKey))) {
            $results = parent::get($columns);
            $this->cache->tags($this->cacheTag)->forever($cacheKey, $results);
        }


        return $results;
    }

    /**
     * Get a new instance of the query builder.
     *
     * @return Builder
     */
    public function newQuery()
    {
        return new static($this->cache, $this->connection, $this->grammar, $this->processor, $this->cacheTag);
    }
}
