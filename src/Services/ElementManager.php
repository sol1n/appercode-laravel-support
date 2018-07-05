<?php

namespace Appercode\Services;

use Appercode\Element;
use Appercode\Backend;
use Illuminate\Support\Facades\Cache;

class ElementManager
{
    protected $backend;
    protected $cachingTtl;
    protected $enableCaching;

    public function __construct(Backend $backend)
    {
        $this->backend = $backend;
    }

    public function count(string $schemaName, array $query = [])
    {
        $cacheTag = 'count-' . $schemaName;
        $cacheKey = 'count-' . $schemaName . '-' . md5(serialize($query));

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        } else {
            $count = Element::count($schemaName, $this->backend, $query);
            Cache::tags(['count', 'elements-count', 'elements-count-' . $schemaName])->put($cacheKey, $count);
            return $count;
        }
    }
}
