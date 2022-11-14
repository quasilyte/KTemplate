<?php

use KTemplate\Internal\Compile\Compiler;
use KTemplate\Internal\Compile\Lexer;
use KTemplate\Internal\Env;
use KTemplate\Internal\Renderer;
use KTemplate\Template;
use KTemplate\DataProviderInterface;
use KTemplate\DataKey;
use KTemplate\Context;

class BenchmarkRenderer {
    private $trivial_template;
    private $var_access1_template;
    private $var_access1_x2_template;
    private $var_access1_x10_template;
    private $var_access1_x100_template;
    private $length_filter_template;
    private $default_filter_template;
    private $slot0_template;
    private $concat2_template;
    private $concat3_template;
    private $concat4_template;
    private $concat5_template;
    private $mixed_template;

    /** @var Env */
    private $env;

    /** @var Renderer */
    private $renderer;
    private $array_data_provider;
    private $null_data_provider;

    public function __construct() {
        $ctx = new Context();
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
        $this->null_data_provider = new NullDataProvider();

        $c = new Compiler();

        $this->trivial_template = $c->compile($env, 'test', 'hello');

        $this->var_access1_template = $c->compile($env, 'test', '{{ test_name }}');

        $var_access1_src = '';
        for ($i = 0; $i < 100; $i++) {
            if ($i == 2) {
                $this->var_access1_x2_template = $c->compile($env, 'test', $var_access1_src);
            } else if ($i == 10) {
                $this->var_access1_x10_template = $c->compile($env, 'test', $var_access1_src);
            }
            $var_access1_src .= '{{ test_name }}';
        }
        $this->var_access1_x100_template = $c->compile($env, 'test', $var_access1_src);

        $this->length_filter_template = $c->compile($env, 'test', '{% let $s = "" %}{{ $s|length }}{{ $s|length }}{{ $s|length }}{{ $s|length }}');
        $this->default_filter_template = $c->compile($env, 'test', '{% let $x = null %}{{ $x|default(0) }}{{ $x|default("a") }}{{ $x|default(1) }}{{ $x|default(null)|default(1) }}');
        $this->slot0_template = $c->compile($env, 'test', '{{null}}{{null}}{{null}}{{null}}');

        $this->concat2_template = $c->compile($env, 'test', '{% let $s = "a" %}{{ $s ~ $s }}');
        $this->concat3_template = $c->compile($env, 'test', '{% let $s = "a" %}{{ $s ~ $s ~ $s }}');
        $this->concat4_template = $c->compile($env, 'test', '{% let $s = "a" %}{{ $s ~ $s ~ $s ~ $s }}');
        $this->concat5_template = $c->compile($env, 'test', '{% let $s = "a" %}{{ $s ~ $s ~ $s ~ $s ~ $s }}');

        $this->mixed_template = $c->compile($env, 'test', '
            {% let $v = y %}
            {% for $item in items %}
                {# comment #}
                {% let $s = $item ~ x ~ $v ~ x %}
                {% if $item %}
                    > {{ $s }}
                {% end %}
            {% end %}
        ');
    }

    public function benchmarkTrivial() {
        return $this->renderer->render($this->trivial_template, $this->array_data_provider);
    }

    public function benchmarkConcat2() {
        return $this->renderer->render($this->concat2_template, $this->array_data_provider);
    }

    public function benchmarkConcat3() {
        return $this->renderer->render($this->concat3_template, $this->array_data_provider);
    }

    public function benchmarkConcat4() {
        return $this->renderer->render($this->concat4_template, $this->array_data_provider);
    }

    public function benchmarkConcat5() {
        return $this->renderer->render($this->concat5_template, $this->array_data_provider);
    }

    public function benchmarkSlot0() {
        return $this->renderer->render($this->slot0_template, $this->array_data_provider);
    }

    public function benchmarkLengthFilter() {
        return $this->renderer->render($this->length_filter_template, $this->array_data_provider);
    }

    public function benchmarkDefaultFilter() {
        return $this->renderer->render($this->default_filter_template, $this->array_data_provider);
    }

    public function benchmarkVarAccess1() {
        return $this->renderer->render($this->var_access1_template, $this->array_data_provider);
    }

    public function benchmarkVarAccess1x2() {
        return $this->renderer->render($this->var_access1_x2_template, $this->array_data_provider);
    }

    public function benchmarkVarAccess1x10() {
        return $this->renderer->render($this->var_access1_x10_template, $this->array_data_provider);
    }

    public function benchmarkVarAccess1x100() {
        return $this->renderer->render($this->var_access1_x100_template, $this->array_data_provider);
    }

    public function benchmarkMixed() {
        return $this->renderer->render($this->mixed_template, $this->array_data_provider);
    }
}

class NullDataProvider implements DataProviderInterface {
    public function getData($key) {
        return null;
    }
}
