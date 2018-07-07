<?php

namespace Appercode\Services;

use Appercode\Element;
use Appercode\Backend;
use Illuminate\Support\Facades\Cache;
use Appercode\Services\ElementCountingManager;

class ElementManager
{
    protected $backend;
    protected $cachingTtl;
    protected $enableCaching;
    protected $countingManager;

    public function __construct(Backend $backend)
    {
        $this->backend = $backend;
        $this->cachingTtl = config('appercode.elements.caching.Ttl');
        $this->enableCaching = config('appercode.elements.caching.enabled');

        $this->countingManager = new ElementCountingManager($this->backend);
    }

    public function count(string $schemaName, array $query = [])
    {
        return $this->countingManager->count($schemaName, $query);
    }

    public function create(string $schemaName, array $fields)
    {
        $this->countingManager->flushSchema($schemaName);
        return Element::create($schemaName, $fields, $this->backend);
    }
}
