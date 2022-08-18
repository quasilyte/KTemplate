<?php

use KTemplate\Compile\Compiler;
use KTemplate\Compile\Lexer;
use KTemplate\Renderer;
use KTemplate\Template;
use KTemplate\DataProviderInterface;
use KTemplate\DataKey;

class BenchmarkRenderer {
    private $var_access1_template;
    private $var_access1_x2_template;
    private $var_access1_x10_template;
    private $var_access1_x100_template;

    /** @var Renderer */
    private $renderer;
    private $array_data_provider;
    private $null_data_provider;

    public function __construct() {
        $this->renderer = new Renderer();
        $this->array_data_provider = new ArrayDataProvider();
        $this->null_data_provider = new NullDataProvider();

        $c = new Compiler();

        $this->var_access1_template = $c->compile('test', '{{ test_name }}');

        $var_access1_src = '';
        for ($i = 0; $i < 100; $i++) {
            if ($i == 2) {
                $this->var_access1_x2_template = $c->compile('test', $var_access1_src);
            } else if ($i == 10) {
                $this->var_access1_x10_template = $c->compile('test', $var_access1_src);
            }
            $var_access1_src .= '{{ test_name }}';
        }
        $this->var_access1_x100_template = $c->compile('test', $var_access1_src);
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
}

class NullDataProvider implements DataProviderInterface {
    public function getData($key) {
        return null;
    }
}

class ArrayDataProvider implements DataProviderInterface {
    private $arr_data;

    public function __construct() {
        $this->arr_data = [
            'leaf' => 10,
            'nested' => [
                'leaf' => 20,
            ],
        ];
    }

    public function getData($key) {
        switch ($key->part1) {
        case 'test_name':
            return $key->num_parts === 1 ? 'benchmark' : null;
        case 'arr':
            switch ($key->num_parts) {
            case 1:
                return $this->arr_data;
            case 2:
                return $this->arr_data[$key->part2];
            default:
                return $this->arr_data[$key->part2][$key->part3];
            }
        }

        return null;
    }
}