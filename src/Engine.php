<?php

namespace KTemplate;

use KTemplate\Internal\Renderer;
use KTemplate\Internal\Env;

class Engine {
    /** @var Env */
    private $env;

    /** @var Renderer */
    private $renderer;

    /**
     * @param LoaderInterface $loader
     */
    public function __construct($loader) {
        $this->env = new Env($loader);
    }

    /**
     * @param string $path
     * @return Template
     */
    public function getTemplate($path) {
        return $this->env->getTemplate($path);
    }

    /**
     * @param string $path
     * @param DataProviderInterface $data_provider
     * @return string
     */
    public function render($path, $data_provider = null) {
        $t = $this->env->getTemplate($path);
        return $this->renderer->render($this->env, $t, $data_provider);
    }

    /**
     * @param Template $t
     * @param DataProviderInterface $data_provider
     * @return string
     */
    public function renderTemplate($t, $data_provider = null) {
        if ($this->renderer === null) {
            $this->renderer = new Renderer();
        }
        return $this->renderer->render($this->env, $t, $data_provider);
    }

    /**
     * @param Template $t
     * @param int $max_str_len
     * @return string[]
     */
    public static function getBytecode($t, $max_str_len = 32) {
        return Disasm::getBytecode($this->env, $t, $max_str_len);
    }

    /**
     * @param string $name
     * @param callable():mixed $fn
     */
    public function registerFunction0($name, $fn) {
        $this->env->registerFunction0($name, $fn);
    }

    /**
     * @param string $name
     * @param callable(mixed):mixed $fn
     */
    public function registerFunction1($name, $fn) {
        $this->env->registerFunction1($name, $fn);
    }

    /**
     * @param string $name
     * @param callable(mixed,mixed):mixed $fn
     */
    public function registerFunction2($name, $fn) {
        $this->env->registerFunction2($name, $fn);
    }

    /**
     * @param string $name
     * @param callable(mixed,mixed,mixed):mixed $fn
     */
    public function registerFunction3($name, $fn) {
        $this->env->registerFunction3($name, $fn);
    }

    /**
     * @param string $name
     * @param callable(mixed):mixed $fn
     */
    public function registerFilter1($name, $fn) {
        $this->env->registerFilter1($name, $fn);
    }

    /**
     * @param string $name
     * @param callable(mixed,mixed):mixed $fn
     */
    public function registerFilter2($name, $fn) {
        $this->env->registerFilter2($name, $fn);
    }
}
