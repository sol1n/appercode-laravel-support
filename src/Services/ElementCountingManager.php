<?php

namespace Appercode\Services;

use Appercode\Element;
use Appercode\Contracts\Backend;
use Illuminate\Support\Facades\Cache;

class ElementCountingManager
{
    protected $backend;
    protected $cachingTtl;
    protected $enableCaching;

    public function __construct(Backend $backend)
    {
        $this->backend = $backend;
        $this->cachingTtl = config('appercode.elements.caching.Ttl');
        $this->enableCaching = config('appercode.elements.caching.enabled');
    }

    private function key(string $schemaName, array $query = [])
    {
        return 'count-' . $schemaName . '-' . md5(serialize($query));
    }

    private function tags(string $schemaName, array $query = [])
    {
        $tags = ['count', 'elements-count', 'elements-count-' . $schemaName];
        if (isset($query['where']) && is_array($query['where'])) {
            foreach ($query['where'] as $field => $condition) {
                $tags[] = 'elements-count-' . $schemaName . '-' . $field;
            }
        }
        return $tags;
    }

    public function flushSchema(string $schemaName)
    {
        if ($this->enableCaching) {
            Cache::tags(['elements-count-' . $schemaName])->flush();
        }
    }

    public function flushFields(string $schemaName, array $fields = [])
    {
        if ($this->enableCaching) {
            $tags = [];
            foreach ($fields as $field => $value) {
                $tags[] = 'elements-count-' . $schemaName . '-' . $field;
            }
            Cache::tags($tags)->flush();
        }
    }

    public function count(string $schemaName, array $query = [])
    {
        if ($this->enableCaching) {
            $cacheKey = $this->key($schemaName, $query);
            $cache = Cache::tags($this->tags($schemaName, $query));

            if ($cache->has($cacheKey)) {
                return $cache->get($cacheKey);
            } else {
                $count = Element::count($schemaName, $this->backend, $query);
                $cache->put($cacheKey, $count, $this->cachingTtl);
                return $count;
            }
        } else {
            return Element::count($schemaName, $this->backend, $query);
        }
    }
}
