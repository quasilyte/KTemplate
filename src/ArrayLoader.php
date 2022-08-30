<?php

namespace KTemplate;

use KTemplate\Compile\Compiler;

class ArrayLoader implements LoaderInterface {
    /**
     * @var string[]
     */
    private $sources = [];

    /**
     * @param string[] $sources
     */
    public function __construct($sources = []) {
        $this->sources = $sources;
    }

    /**
     * @param string[] $sources
     */
    public function setSources($sources) {
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
        $key->modification_time = 0;
        $key->source_size = strlen($this->sources[$path]);
    }
}
