<?php

namespace KTemplate;

use KTemplate\Internal\Compile\Compiler;

/**
 * ArrayLoader implements a simple templates loader from array.
 * 
 * Array keys are template paths.
 * Array values are template sources.
 * 
 * It's possible to replace the underlying array using the setSources method.
 * This will invalidate all cache keys.
 */
class ArrayLoader implements LoaderInterface {
    /** @var string[] */
    private $sources = [];

    /** @var int */
    private $cache_seq = 0;

    /**
     * @param string[] $sources - maps a template path to its sources
     */
    public function __construct($sources = []) {
        $this->sources = $sources;
    }

    /**
     * @param string[] $sources - maps a template path to its sources
     */
    public function setSources($sources) {
        $this->cache_seq++;
        $this->sources = $sources;
    }

    public function load($env, $path) {
        if (!isset($this->sources[$path])) {
            throw new \Exception("can't resolve $path path");
        }
        return $this->sources[$path];
    }

    public function updateCacheKey($path, $key) {
        if (!isset($this->sources[$path])) {
            throw new \Exception("can't resolve $path path");
        }
        $key->full_name = $path;
        $key->modification_time = $this->cache_seq;
        $key->source_size = strlen($this->sources[$path]);
    }
}
