<?php

use KTemplate\Internal\Compile\Compiler;
use KTemplate\Internal\Compile\Lexer;
use KTemplate\Internal\Env;
use KTemplate\Internal\Renderer;
use KTemplate\Template;
use KTemplate\DataProviderInterface;
use KTemplate\DataKey;
use KTemplate\Context;

class BenchmarkRendererNoEscape {
    private $concat2_output_template;
    private $concat3_output_template;
    private $concat4_output_template;
    private $concat5_output_template;

    /** @var Env */
    private $env;

    /** @var Renderer */
    private $renderer;
    private $array_data_provider;

    public function __construct() {
        $ctx = new Context();
        $ctx->auto_escape_expr = false;
        $env = new Env($ctx, null);
        $this->env = $env;

        $this->renderer = new Renderer($env);
        $this->array_data_provider = new \KTemplate\ArrayDataProvider([
            'leaf' => 10,
            'nested' => [
                'leaf' => 20,
            ],
            'x' => 123,
            'y' => 'foo',
            'items' => [
                'a',
                'b',
                'c',
                '',
                'd',
                'e',
                'f',
                '',
            ],
        ]);

        $c = new Compiler();

        $this->concat2_output_template = $c->compile($env, 'test', '{% let $s = "abcdefg" %}{{ $s ~ $s }}{{ $s ~ $s }}{{ $s ~ $s }}');
        $this->concat3_output_template = $c->compile($env, 'test', '{% let $s = "abcdefg" %}{{ $s ~ $s ~ $s }}{{ $s ~ $s ~ $s }}{{ $s ~ $s ~ $s }}');
        $this->concat4_output_template = $c->compile($env, 'test', '{% let $s = "abcdefg" %}{{ $s ~ $s ~ $s ~ $s }}{{ $s ~ $s ~ $s ~ $s }}{{ $s ~ $s ~ $s ~ $s }}');
        $this->concat5_output_template = $c->compile($env, 'test', '{% let $s = "abcdefg" %}{{ $s ~ $s ~ $s ~ $s ~ $s }}{{ $s ~ $s ~ $s ~ $s ~ $s }}{{ $s ~ $s ~ $s ~ $s ~ $s }}');
    }

    public function benchmarkConcat2Output() {
        return $this->renderer->render($this->concat2_output_template, $this->array_data_provider);
    }

    public function benchmarkConcat3Output() {
        return $this->renderer->render($this->concat3_output_template, $this->array_data_provider);
    }

    public function benchmarkConcat4Output() {
        return $this->renderer->render($this->concat4_output_template, $this->array_data_provider);
    }

    public function benchmarkConcat5Output() {
        return $this->renderer->render($this->concat5_output_template, $this->array_data_provider);
    }
}
