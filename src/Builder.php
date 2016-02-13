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
    protected $cache;

    /**
     * @var string
     */
    protected $cacheTag;

    /**
     * @var bool
     */
    protected $isTimeAwareQuery = false;

    /**
     * @var int Minute
     */
    protected $cacheLifeTime;

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
        $this->cacheTag = $cacheTag;

        // Get cache life time from config file
        // Default set to 5 minutes
        // This lifetime used in time aware queries
        $this->cacheLifeTime = config('infinity-cache.lifeTime', 5);

        parent::__construct($connection, $grammar, $processor);
    }

    /**
     * Execute the query as a "select" statement.
     * All Cached results will be flushed after every CUD operations
     *
     * @param  array $columns
     * @return array|static[]
     */
    public function get($columns = ['*'])
    {
        $cacheKey = $this->generateCacheKey();

        if (null === ($results = $this->cache->tags($this->cacheTag)->get($cacheKey))) {
            $results = parent::get($columns);

            if ($this->isTimeAwareQuery) {
                // Cache results for $cacheLifeTime minutes
                $this->cache->tags($this->cacheTag)->put($cacheKey, $results, $this->cacheLifeTime);
            } else {
                // Cache results forever
                $this->cache->tags($this->cacheTag)->forever($cacheKey, $results);
            }
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
        return new static(
            $this->cache,
            $this->connection,
            $this->grammar,
            $this->processor,
            $this->cacheTag
        );
    }

    /**
     * Generate unique cache key from query and it bindings parameters
     *
     * @return string
     */
    protected function generateCacheKey()
    {
        $bindings = array_map(function ($param) {

            // Round datetime
            if ($param instanceof \DateTime) {
                $this->isTimeAwareQuery = true;
                return $this->getRoundedDateTime($param);
            }

            return $param;

        }, $this->getBindings());


        return sha1($this->connection->getName() . $this->toSql() . serialize($bindings));
    }

    /**
     * @param \DateTime $dateTime
     * @return string
     */
    protected function getRoundedDateTime(\DateTime $dateTime)
    {
        // Get cache life time in minutes and convert to second
        $cacheLifeTime = $this->cacheLifeTime * 60;

        $rounded = ceil($dateTime->getTimestamp() / $cacheLifeTime) * $cacheLifeTime;


        return date('Y-m-d H:i:s', $rounded);
    }


}
