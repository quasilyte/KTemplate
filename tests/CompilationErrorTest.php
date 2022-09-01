<?php

use PHPUnit\Framework\TestCase;
use KTemplate\Internal\Compile\Compiler;
use KTemplate\CompilationException;
use KTemplate\Context;
use KTemplate\Engine;
use KTemplate\ArrayLoader;
use KTemplate\Internal\Strings;

class CompilationErrorTest extends TestCase {
    /** @var Engine */
    private static $engine;

    /** @var ArrayLoader */
    private static $loader;

    /** @var Context */
    private static $context;

    public static function setUpBeforeClass(): void {
        self::$context = new Context();
        self::$loader = new ArrayLoader();
        self::$engine = new Engine(self::$context, self::$loader);

        self::$engine->registerFunction1('strlen', function ($x) { return strlen($x); });
    }

    public function testSimpleErrors() {
        $tests_no_escape_func = [
            '{{ x|escape }}' => 'escape is used, but $ctx->escape_func is null',
            '{{ x|e }}' => 'e is used, but $ctx->escape_func is null',
            '{{ x|escape("a") }}' => 'escape is used, but $ctx->escape_func is null',
            '{{ x|e("a") }}' => 'e is used, but $ctx->escape_func is null',
        ];

        $tests = [
            '{{ x matches "/" }}' => 'matches operator rhs contains invalid pattern',
            '{{ x matches $x }}' => 'matches operator rhs pattern should be a const expr string',

            '}}' => 'unexpected top-level token: }}',
            '%}' => 'unexpected top-level token: %}',

            '{{ g() }}' => 'g function is not defined',
            '{{ g( }}' => 'expected ) to close a call expr argument list, found eof',
            '{{ g(1, 2, 3, 4) }}' => 'call expr is limited to 3 arguments',

            '{{ 10|10 }}' => 'invalid filter, expected a call or ident',
            '{{ 10|bad }}' => 'bad filter is not defined',
            '{{ 10|length(1, 2) }}' => 'too many arguments for a filter',

            '{{ $x }}' => 'referenced undefined local var x',

            '{{ a.b.c.d }}' => 'dot access expression is too complex',

            '{{ x|escape(y) }}' => 'escape filter expects a const expr string argument',
            '{{ x|e(y) }}' => 'escape filter expects a const expr string argument',

            '{% for x in %}' => 'for loop var names should be identifiers with leading $, found ident',
            '{% for $x $y %}' => 'expected in, found dollar_ident',
            '{% for $x, 3 in x %}' => 'for loop var names should be identifiers with leading $, found int_lit',
            '{% for $x in xs }}' => 'expected %} or -%}, found }}',
            '{% for $x in xs %}{% else %}{% else %}' => 'unexpected control token: else',

            '{% set x = 1 }}' => 'set names should be identifiers with leading $, found ident',
            '{% let x = 1 }}' => 'let names should be identifiers with leading $, found ident',
            '{% let $x = 1 %}{% let $x = 2 %}' => 'variable x is already declared in this scope',
            '{% let $x 10 %}' => 'expected = or %} or -%}, found int_lit',
            '{% let $x = 10 %}{% set $x 10 %}' => 'expected = or %} or -%}, found int_lit',
            '{% let $x = 10 }}' => 'expected %} or -%}, found }}',
            '{% let $x = 10 %}{% set $x = 10 }}' => 'expected %} or -%}, found }}',

            '{% what %}' => 'unexpected control token: what',
            '{% + %}' => 'unexpected control token: +',
            '{% arg $x = 10 %}' => 'unexpected control token: arg',
            '{{ 1' => 'expected }} or -}}, found eof',

            '{% if 1 }}' => 'expected %} or -%}, found }}',
            '{% if 1 %}{% end 1' => 'expected %} or -%}, found int_lit',
            '{% if 1 %}{% else "a"' => 'expected %} or -%}, found string_lit_q2',

            '{{ (1 }}' => 'missing )',

            '{{ "a }}' => 'unterminated string literal',
            '{# aa' => 'missing #}',

            '{% include $x %}' => 'include expects a const expr string argument',
            '{% param $x = null %}' => "x param default initializer can't have null value",

            '{% include "a" %}{% include "b"}' => 'include block can only contain args and whitespace',
            '{% include "a" %}xxx{% end %}' => 'include block can only contain args and whitespace',
            '{% include "a" %}' => 'include block can only contain args and whitespace',

            '{% include "a" %}{% arg $x = null %}{% end %}' => 'passing null will cause the param to be default-initialized',

            '{% include "a" %}{% arg $x = 1 %}{% arg $x = 2 %}' => 'duplicated x argument',

            '{% include "example" %}{% arg $foo = 5 %}{% end %}' => "template example doesn't have foo param",

            '{{ s|escape() }}' => 'omit the () for 0-arguments filter call',

            '{% let $x = 1 %}{% param $y = 2 %}' => 'param can only be used in the beginning of template',
            '{% let $x = 1 %}{% param $x = 2 %}' => "can't declare x param: name is already in use",
            '{% param $x = 1 %}{% param $x = 2 %}' => "can't declare x param: name is already in use",
            '{% param x = 1 %}' => 'param names should be identifiers with leading $, found ident',

            '{% let $x = 0 %}{% set $x %}1{% let $y %}{% end %}{% end %}' => 'unsupported block-assign let inside set',
            '{% let $x = 0 %}{% set $x %}1{% set $x %}{% end %}{% end %}' => 'unsupported block-assign set inside set',
            '{% let $x %}1{% let $y %}{% end %}{% end %}' => 'unsupported block-assign let inside let',
            '{% let $x %}1{% set $x %}{% end %}{% end %}' => 'unsupported block-assign set inside let',
        ];

        $run_test = function($input) {
            self::$loader->setSources([
                'example' => '
                    {% param $title = "Example" %}
                    {{ $title }}
                ',
                'test' => (string)$input,
            ]);
            $have = '';
            try {
                $t = self::$engine->load('test');
            } catch (CompilationException $e) {
                $have = $e->getFullMessage();
            }
            return $have;
        };
        
        foreach ($tests as $input => $want) {
            $have = $run_test($input);
            $this->assertTrue(Strings::contains($have, $want), "input=$input have=$have\n");
        }

        $escape_func = self::$context->escape_func;
        self::$context->escape_func = null;
        foreach ($tests_no_escape_func as $input => $want) {
            $have = $run_test($input);
            $this->assertTrue(Strings::contains($have, $want), "input=$input have=$have\n");
        }
        self::$context->escape_func = $escape_func;
    }

    public function testLimitErrors() {
        $tests = [
            [
                '{% if cond %}' . str_repeat('{{ x }}', 35000) . '{% end %}',
                "jump offset 35000 doesn't fit into int16",
            ],
            [
                $this->createTemplateSource(0xffff+10, function ($i) {
                    return "{% if $i %}1{% end %}";
                }),
                'too many jump targets',
            ],
            [
                $this->createTemplateSource(0xffff+10, function ($i) {
                    return "{{ $i }}";
                }),
                'too many int const values',
            ],
            [
                $this->createTemplateSource(0xff+10, function ($i) {
                    return "{{ $i.1 }}";
                }),
                'too many float const values',
            ],
            [
                $this->createTemplateSource(0xffff+10, function ($i) {
                    return "{{ 'str$i' }}";
                }),
                'too many string const values',
            ],
        ];
        
        foreach ($tests as $test) {
            [$input, $want] = $test;
            self::$loader->setSources([
                'example' => '
                    {% param $title = "Example" %}
                    {{ $title }}
                ',
                'test' => (string)$input,
            ]);
            $have = '';
            try {
                $t = self::$engine->load('test');
            } catch (CompilationException $e) {
                $have = $e->getFullMessage();
            }
            $this->assertTrue(Strings::contains($have, $want), "want=$want have=$have\n");
        }
    }

    /**
     * @param int $n
     * @param callable(int):string $fn
     */
    private function createTemplateSource($n, $fn) {
        $result = '';
        for ($i = 0; $i < $n; $i++) {
            $result .= $fn($i);
        }
        return $result;
    }
}
