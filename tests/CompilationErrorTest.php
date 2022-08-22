<?php

use PHPUnit\Framework\TestCase;
use KTemplate\Compile\Compiler;
use KTemplate\Compile\CompilationException;
use KTemplate\Env;
use KTemplate\Internal\Strings;

class CompilationErrorTest extends TestCase {
    /** @var Compiler */
    private static $compiler;

    public static function setUpBeforeClass(): void {
        self::$compiler = new Compiler();
    }

    public function testSimpleErrors() {
        $tests = [
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

            '{% for x in %}' => 'for loop var names should be identifiers with leading $, found ident',
            '{% for $x $y %}' => 'expected in, found dollar_ident',
            '{% for $x, 3 in x %}' => 'for loop var names should be identifiers with leading $, found int_lit',
            '{% for $x in xs }}' => 'expected %}, found }}',
            '{% for $x in xs %}{% else %}{% else %}' => 'unexpected control token: else',

            '{% set x = 1 }}' => 'set names should be identifiers with leading $, found ident',
            '{% let x = 1 }}' => 'let names should be identifiers with leading $, found ident',
            '{% let $x = 1 %}{% let $x = 2 %}' => 'variable x is already declared in this scope',
            '{% let $x 10 %}' => 'expected =, found int_lit',
            '{% let $x = 10 %}{% set $x 10 %}' => 'expected =, found int_lit',
            '{% let $x = 10 }}' => 'expected %}, found }}',
            '{% let $x = 10 %}{% set $x = 10 }}' => 'expected %}, found }}',

            '{% what %}' => 'unexpected control token: what',
            '{% + %}' => 'unexpected control token: +',
            '{{ 1' => 'expected }}, found eof',

            '{% if 1 }}' => 'expected %}, found }}',
            '{% if 1 %}{% endif 1' => 'expected %}, found int_lit',
            '{% if 1 %}{% else "a"' => 'expected %}, found string_lit_q2',

            '{{ (1 }}' => 'missing )',

            '{{ "a }}' => 'unterminated string literal',
            '{# aa' => 'missing #}',
        ];

        $env = new Env();
        $env->registerFunction1('strlen', function ($x) { return strlen($x); });
        foreach ($tests as $input => $want) {
            $have = '';
            try {
                $t = self::$compiler->compile($env, 'test', (string)$input);
            } catch (CompilationException $e) {
                $have = $e->getFullMessage();
            }
            $this->assertTrue(Strings::contains($have, $want), "input=$input have=$have\n");
        }
    }
}
