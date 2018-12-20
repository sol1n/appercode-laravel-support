<?php

namespace Appercode\Services;

use Appercode\Element;
use Appercode\Contracts\Backend;
use Illuminate\Support\Collection;
use Appercode\Services\ElementCountingManager;
use Appercode\Traits\SchemaName;

class ElementManager
{
    use SchemaName;

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

    public function count($schema, array $query = [])
    {
        return $this->countingManager->count(self::getSchemaName($schema), $query);
    }

    public function create($schema, array $fields)
    {
        $this->countingManager->flushSchema(self::getSchemaName($schema));
        return Element::create($schema, $fields, $this->backend);
    }

    public function bulk($schema, array $queries): Collection
    {
        return Element::bulk($schema, $queries, $this->backend);
    }
}
