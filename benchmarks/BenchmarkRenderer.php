<?php

use KTemplate\Compile\Compiler;
use KTemplate\Compile\Lexer;
use KTemplate\Renderer;
use KTemplate\Template;
use KTemplate\DataProviderInterface;
use KTemplate\DataKey;

class BenchmarkRenderer {
    private $var_access2_template;
    private $var_access3_template;
    private $multiple_var_access_template;

    /** @var Renderer */
    private $renderer;
    private $array_data_provider;
    private $null_data_provider;

    public function __construct() {
        $this->renderer = new Renderer();
        $this->array_data_provider = new ArrayDataProvider();
        $this->null_data_provider = new NullDataProvider();

        $c = new Compiler();

        $this->var_access2_template = $c->compile('test', '{{ arr.leaf }}');
        $this->var_access3_template = $c->compile('test', '{{ arr.nested.leaf }}');

        $this->multiple_var_access_template = $c->compile('test', '
            {{ arr.leaf }}
            {{ arr.nested.leaf }}
            {{ arr.leaf }}
            {{ arr.nested.leaf }}
            {{ arr.leaf }}
            {{ arr.nested.leaf }}
            {{ arr.leaf }}
            {{ arr.nested.leaf }}
            {{ arr.leaf }}
            {{ arr.nested.leaf }}
        ');
    }

    public function benchmarkVarAccess2Null() {
        return $this->renderer->render($this->var_access2_template, $this->null_data_provider);
    }

    public function benchmarkVarAccess3Null() {
        return $this->renderer->render($this->var_access3_template, $this->null_data_provider);
    }

    public function benchmarkVarAccess2() {
        return $this->renderer->render($this->var_access2_template, $this->array_data_provider);
    }

    public function benchmarkVarAccess3() {
        return $this->renderer->render($this->var_access3_template, $this->array_data_provider);
    }

    public function benchmarkMultipleVarAccess() {
        return $this->renderer->render($this->multiple_var_access_template, $this->array_data_provider);
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