<?php

namespace KTemplate;

use KTemplate\Compile\Compiler;

class ArrayLoader implements LoaderInterface {
    /**
     * @var Template[]
     */
    private $cache = [];

    /**
     * @var string[]
     */
    private $sources = [];

    /**
     * @var Compiler
     */
    private $compiler;

    /**
     * @param string[] $sources
     */
    public function __construct($sources) {
        $this->sources = $sources;
        $this->compiler = new Compiler();
    }

    /**
     * @param Env $env
     * @param string $path
     * @return Template
     */
    public function load($env, $path) {
        if (isset($this->cache[$path])) {
            return $this->cache[$path];
        }

        if (!isset($this->sources[$path])) {
            throw new \Exception("can't resolve $path path");
        }

        $template = $this->compiler->compile($env, $path, $this->sources[$path]);
        $this->cache[$path] = $template;
        return $template;
    }
}
