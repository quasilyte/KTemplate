<?php

namespace KTemplate;

use KTemplate\Internal\Renderer;
use KTemplate\Internal\Env;
use KTemplate\Internal\Disasm;

/**
 * Engine provides the ways to compile, configure and render templates.
 *
 * It works as both the environment that holds things like user-defined functions
 * and API entrypoint.
 *
 * If you need to compile a template, use load().
 * If you need to render a template, use render() or renderTemplate().
 * If you have a template, you can use disassembleTemplate() to get its bytecode.
 *
 * registerFunctionX() and registerFilterX() methods can be used to
 * define symbols accessible from the compiled templates.
 */
class Engine {
    /** @var Env */
    private $env;

    /** @var Renderer */
    private $renderer;

    /** @var Context */
    private $ctx;

    /**
     * @param Context $ctx
     * @param LoaderInterface $loader
     */
    public function __construct($ctx, $loader) {
        $this->env = new Env($ctx, $loader);
    }

    /**
     * load returns the template associated with a given path.
     *
     * This may involve LoaderInterface call to find the sources
     * and template compilation (if it's not compiled yet).
     *
     * After the first load() call, the template is cached and
     * will not involve any actual loading.
     *
     * If compilation will be triggered,
     * a CompilationException can be thrown in case of an error.
     *
     * @param string $path - a template to be loaded
     * @return Template - a compiled template
     */
    public function load($path) {
        return $this->env->getTemplate($path);
    }

    /**
     * renderTemplate executes a compiled template and returns the rendered result.
     *
     * @param Template $t - a template to be executed
     * @param DataProviderInterface $data_provider
     * @return string - rendering result
     */
    public function renderTemplate($t, $data_provider = null) {
        if ($this->renderer === null) {
            $this->renderer = new Renderer($this->env);
        }
        return $this->renderer->render($t, $data_provider);
    }

    /**
     * render is a shorthand for load()+renderTemplate().
     * 
     * @param string $path - a template path, will be used as load() argument
     * @param DataProviderInterface $data_provider
     * @return string - rendering result
     */
    public function render($path, $data_provider = null) {
        $t = $this->env->getTemplate($path);
        return $this->renderTemplate($t, $data_provider);
    }

    /**
     * disassembleTemplate returns a bytecode that is stored inside a template.
     *
     * @param Template $t - a template to be disassembled
     * @param int $max_str_len - truncate string values longer than this threshold
     * @return string[] - disassembled listing in form of string lines
     */
    public function disassembleTemplate($t, $max_str_len = 32) {
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
