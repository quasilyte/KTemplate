<?php

use PHPUnit\Framework\TestCase;
use KTemplate\Internal\Compile\Compiler;
use KTemplate\LoaderInterface;
use KTemplate\ArrayLoader;
use KTemplate\Internal\Env;
use KTemplate\Internal\Disasm;

class CompilerTest extends TestCase {
    /** @var Compiler */
    private static $compiler;

    public static function setUpBeforeClass(): void {
        self::$compiler = new Compiler();
    }

    public function testCompileInclude() {
        $tests = [
            [
                'sources' => [
                    'main' => '{% include "a" %}{% end %}',
                    'a' => 'hello',
                ],
                'disasm' => [
                    'main' => [
                        '  PREPARE_TEMPLATE `a`',
                        '  INCLUDE_TEMPLATE',
                        '  RETURN',
                    ],
                    'a' => [
                        '  OUTPUT_SAFE_STRING_CONST `hello`',
                        '  RETURN',
                    ],
                ],
            ],

            [
                'sources' => [
                    'main' => '{% include "a" %}{% arg $x = 10 %}{% end %}',
                    'a' => '{% param $x = 0 %}{{ $x }}',
                ],
                'disasm' => [
                    'main' => [
                        '  PREPARE_TEMPLATE `a`',
                        '  LOAD_INT_CONST arg1 10',
                        '  INCLUDE_TEMPLATE',
                        '  RETURN',
                    ],
                    'a' => [
                        '  OUTPUT slot1',
                        '  RETURN',
                    ],
                ],
            ],

            [
                'sources' => [
                    'main' => '{% include "a" %}{% arg $x = 10 %}{% arg $foo = 0 %}{% end %}',
                    'a' => '{% param $foo = 0 %}{% param $x = 0 %}{% include "b" %}{% arg $x = $x %}{% end %}',
                    'b' => '{% param $x = 0 %}{{ $x }}',
                ],
                'disasm' => [
                    'main' => [
                        '  PREPARE_TEMPLATE `a`',
                        '  LOAD_INT_CONST arg2 10',
                        '  LOAD_INT_CONST arg1 0',
                        '  INCLUDE_TEMPLATE',
                        '  RETURN',
                    ],
                    'a' => [
                        '  PREPARE_TEMPLATE `b`',
                        '  MOVE arg1 slot2',
                        '  INCLUDE_TEMPLATE',
                        '  RETURN',
                    ],
                    'b' => [
                        '  OUTPUT slot1',
                        '  RETURN',
                    ],
                ],
            ],

            [
                'sources' => [
                    'main' => '{% include "a" %}{% arg $x %}10{% end %}{% end %}',
                    'a' => '{% param $x = 0 %}{{ $x + date.year }}',
                ],
                'disasm' => [
                    'main' => [
                        '  PREPARE_TEMPLATE `a`',
                        '  START_TMP_OUTPUT',
                        '  OUTPUT_SAFE_STRING_CONST `10`',
                        '  FINISH_TMP_OUTPUT arg2',
                        '  INCLUDE_TEMPLATE',
                        '  RETURN',
                    ],
                    'a' => [
                        '  LOAD_EXTDATA_2 slot3 slot1 date.year',
                        '  ADD_SLOT0 *slot0 slot2 slot3',
                        '  OUTPUT_SAFE_SLOT0 *slot0',
                        '  RETURN',
                    ],
                ],
            ],

            // Test param initialized by another param.
            [
                'sources' => [
                    'main' => '{% include "a" %}{% end %}',
                    'a' => '{% param $x = 0 %}{% param $y = $x %}{% param $z = $x + 10 %}{{ $x + $y + $z }}',
                ],
                'disasm' => [
                    'main' => [
                        '  PREPARE_TEMPLATE `a`',
                        '  INCLUDE_TEMPLATE',
                        '  RETURN',
                    ],
                    'a' => [
                        '  JUMP_NOT_NULL L0 slot2',
                        '  MOVE slot2 slot1',
                        'L0:',
                        '  JUMP_NOT_NULL L1 slot3',
                        '  LOAD_INT_CONST slot4 10',
                        '  ADD slot3 slot1 slot4',
                        'L1:',
                        '  ADD slot4 slot1 slot2',
                        '  ADD_SLOT0 *slot0 slot4 slot3',
                        '  OUTPUT_SAFE_SLOT0 *slot0',
                        '  RETURN',
                    ],
                ],
            ],
        ];

        foreach ($tests as $test) {
            $sources = [];
            foreach ($test['sources'] as $path => $src) {
                $sources[$path] = (string)$src;
            }
            $loader = new ArrayLoader($sources);
            $env = $this->newTestEnv($loader);

            self::$compiler->compile($env, 'main', (string)$test['sources']['main']);
            foreach ($test['disasm'] as $name => $want) {
                $input = (string)$test['sources'][$name];
                $t = $env->getTemplate((string)$name);
                $have = Disasm::getBytecode($env, $t);
                $have_pretty = [];
                foreach ($have as $s) {
                    $have_pretty[] = "'$s',";
                }
                $this->assertEquals($want, $have, "input=$input\n" . implode("\n", $have_pretty));
            }
        }
    }

    public function testCompileDefault() {
        $tests = [
            // These expressions should be escaped.
            '{{ x.y.z }}' => [
                '  OUTPUT_EXTDATA_3 slot1 x.y.z $1',
                '  RETURN',
            ],
            '{{ x.y }}' => [
                '  OUTPUT_EXTDATA_2 slot1 x.y $1',
                '  RETURN',
            ],
            '{{ x }}' => [
                '  OUTPUT_EXTDATA_1 slot1 x $1',
                '  RETURN',
            ],
            '{{ (x~y) }}' => [
                '  LOAD_EXTDATA_1 slot3 slot1 x',
                '  LOAD_EXTDATA_1 slot4 slot2 y',
                '  CONCAT_SLOT0 *slot0 slot3 slot4',
                '  OUTPUT_SLOT0 *slot0',
                '  RETURN',
            ],
            '{% let $x = "a" %}{{ $x }}' => [
                '  LOAD_STRING_CONST slot1 `a`',
                '  OUTPUT slot1',
                '  RETURN',
            ],
            '{{ (x|escape) ~ "x" }}' => [
                '  LOAD_EXTDATA_1 slot3 slot1 x',
                '  ESCAPE_FILTER1 slot2 slot3',
                '  LOAD_STRING_CONST slot4 `x`',
                '  CONCAT_SLOT0 *slot0 slot2 slot4',
                '  OUTPUT_SLOT0 *slot0',
                '  RETURN',
            ],

            // Safe strings are not escaped.
            '{{ x|escape }}' => [
                '  LOAD_EXTDATA_1 slot2 slot1 x',
                '  ESCAPE_SLOT0_FILTER1 *slot0 slot2',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ x|escape("url") }}' => [
                '  LOAD_EXTDATA_1 slot2 slot1 x',
                '  ESCAPE_SLOT0_FILTER2 *slot0 slot2 `url`',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ x|escape("url")|escape("html") }}' => [
                '  LOAD_EXTDATA_1 slot3 slot1 x',
                '  ESCAPE_FILTER2 slot2 slot3 `url`',
                '  ESCAPE_SLOT0_FILTER2 *slot0 slot2 `html`',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ x|e }}' => [
                '  LOAD_EXTDATA_1 slot2 slot1 x',
                '  ESCAPE_SLOT0_FILTER1 *slot0 slot2',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ x|e("url") }}' => [
                '  LOAD_EXTDATA_1 slot2 slot1 x',
                '  ESCAPE_SLOT0_FILTER2 *slot0 slot2 `url`',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],

            // Some expression types are never escaped.
            '{{ x or y }}' => [
                '  LOAD_SLOT0_EXTDATA_1 *slot0 slot1 x',
                '  JUMP_SLOT0_TRUTHY *slot0 L0',
                '  LOAD_SLOT0_EXTDATA_1 *slot0 slot2 y',
                'L0:',
                '  CONV_SLOT0_BOOL *slot0',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ x + y }}' => [
                '  LOAD_EXTDATA_1 slot3 slot1 x',
                '  LOAD_EXTDATA_1 slot4 slot2 y',
                '  ADD_SLOT0 *slot0 slot3 slot4',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ x|length }}' => [
                '  LOAD_EXTDATA_1 slot2 slot1 x',
                '  LENGTH_SLOT0_FILTER slot2 slot1',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],

            // Raw filter suppresses escaping.
            '{{ x|raw }}' => [
                '  LOAD_SLOT0_EXTDATA_1 *slot0 slot1 x',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ x.y|raw }}' => [
                '  LOAD_SLOT0_EXTDATA_2 *slot0 slot1 x.y',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ x.y.z|raw }}' => [
                '  LOAD_SLOT0_EXTDATA_3 *slot0 slot1 x.y.z',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ (x.y|raw) ~ "a" }}' => [
                '  LOAD_EXTDATA_2 slot2 slot1 x.y',
                '  LOAD_STRING_CONST slot3 `a`',
                '  CONCAT_SLOT0 *slot0 slot2 slot3',
                '  OUTPUT_SLOT0 *slot0',
                '  RETURN',
            ],

            // Text is not escaped by default.
            'abc' => [
                '  OUTPUT_SAFE_STRING_CONST `abc`',
                '  RETURN',
            ],

            // Constant expressions are not escaped by default.
            '{{ "a" }}' => [
                '  OUTPUT_SAFE_STRING_CONST `a`',
                '  RETURN',
            ],
            '{{ 13 }}' => [
                '  OUTPUT_SAFE_INT_CONST 13',
                '  RETURN',
            ],
            '{{ "a" ~ "b" }}' => [
                '  OUTPUT_SAFE_STRING_CONST `ab`',
                '  RETURN',
            ],
        ];

        $env = $this->newTestEnv();
        foreach ($tests as $input => $want) {
            $t = self::$compiler->compile($env, 'test', (string)$input);
            $have = Disasm::getBytecode($env, $t);
            $have_pretty = [];
            foreach ($have as $s) {
                $have_pretty[] = "'$s',";
            }
            $this->assertEquals($want, $have, "input=$input\n" . implode("\n", $have_pretty));
        }
    }

    public function testCompileNoEscape() {
        $tests = [
            // Numeric constants.
            '{{ -1 }}' => [
                '  OUTPUT_SAFE_INT_CONST -1',
                '  RETURN',
            ],
            '{{ 2.4 }}' => [
                '  LOAD_SLOT0_FLOAT_CONST *slot0 2.4',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],

            // Local variables.
            '{% let $s = "abc" %}{{ $s }}{% set $s = "a" %}{{ $s }}' => [
                '  LOAD_STRING_CONST slot1 `abc`',
                '  OUTPUT_SAFE slot1',
                '  LOAD_STRING_CONST slot1 `a`',
                '  OUTPUT_SAFE slot1',
                '  RETURN',
            ],

            // Extdata.
            '{{ x }}{% let $y = 2 %}{{ x }}{{ $y }}' => [
                '  OUTPUT_EXTDATA_1 slot1 x $0',
                '  LOAD_INT_CONST slot2 2',
                '  OUTPUT_EXTDATA_1 slot1 x $0',
                '  OUTPUT_SAFE slot2',
                '  RETURN',
            ],
            '{{ x }}{% let $s = "a" %}{{ $s == "a" }}' => [
                '  OUTPUT_EXTDATA_1 slot1 x $0',
                '  LOAD_STRING_CONST slot2 `a`',
                '  LOAD_STRING_CONST slot3 `a`',
                '  EQ_SLOT0 *slot0 slot2 slot3',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ x + x }}' => [
                '  LOAD_EXTDATA_1 slot2 slot1 x',
                '  LOAD_EXTDATA_1 slot3 slot1 x',
                '  ADD_SLOT0 *slot0 slot2 slot3',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{% let $i=1 %}{{ x.y }}{{$i}}' => [
                '  LOAD_INT_CONST slot2 1',
                '  OUTPUT_EXTDATA_2 slot1 x.y $0',
                '  OUTPUT_SAFE slot2',
                '  RETURN',
            ],
            '{% let $i=1 %}{{ x.y.z }}{{$i}}' => [
                '  LOAD_INT_CONST slot2 1',
                '  OUTPUT_EXTDATA_3 slot1 x.y.z $0',
                '  OUTPUT_SAFE slot2',
                '  RETURN',
            ],
            '{{ x }}{{ x.y }}{{ x.y.z }}{{ x }}{{ x.y }}' => [
                '  OUTPUT_EXTDATA_1 slot1 x $0',
                '  OUTPUT_EXTDATA_2 slot2 x.y $0',
                '  OUTPUT_EXTDATA_3 slot3 x.y.z $0',
                '  OUTPUT_EXTDATA_1 slot1 x $0',
                '  OUTPUT_EXTDATA_2 slot2 x.y $0',
                '  RETURN',
            ],
            '{{ x.y + x.y.z }}' => [
                '  LOAD_EXTDATA_2 slot3 slot1 x.y',
                '  LOAD_EXTDATA_3 slot4 slot2 x.y.z',
                '  ADD_SLOT0 *slot0 slot3 slot4',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],

            // Operators.
            '{% let $x = 1 %}{{ $x + $x }}' => [
                '  LOAD_INT_CONST slot1 1',
                '  ADD_SLOT0 *slot0 slot1 slot1',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{% let $x = 1 %}{{ $x == 1 }}' => [
                '  LOAD_INT_CONST slot1 1',
                '  LOAD_INT_CONST slot2 1',
                '  EQ_SLOT0 *slot0 slot1 slot2',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{% let $s = "a" %}{{ $s ~ $s ~ $s }}' => [
                '  LOAD_STRING_CONST slot1 `a`',
                '  CONCAT slot2 slot1 slot1',
                '  CONCAT_SLOT0 *slot0 slot2 slot1',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{% let $b = true %}{{ not $b }}' => [
                '  LOAD_BOOL slot1 $1',
                '  NOT_SLOT0 *slot0 slot1',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{% let $b = true %}{{ not not $b }}' => [
                '  LOAD_BOOL slot1 $1',
                '  NOT slot2 slot1',
                '  NOT_SLOT0 *slot0 slot2',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ -x }}' => [
                '  LOAD_EXTDATA_1 slot2 slot1 x',
                '  NEG_SLOT0 *slot0 slot2',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ -x - 10 }}' => [
                '  LOAD_EXTDATA_1 slot3 slot1 x',
                '  NEG slot2 slot3',
                '  LOAD_INT_CONST slot4 10',
                '  SUB_SLOT0 *slot0 slot2 slot4',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ x / y / z }}' => [
                '  LOAD_EXTDATA_1 slot5 slot1 x',
                '  LOAD_EXTDATA_1 slot6 slot2 y',
                '  QUO slot4 slot5 slot6',
                '  LOAD_EXTDATA_1 slot7 slot3 z',
                '  QUO_SLOT0 *slot0 slot4 slot7',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ x % y % z }}' => [
                '  LOAD_EXTDATA_1 slot5 slot1 x',
                '  LOAD_EXTDATA_1 slot6 slot2 y',
                '  MOD slot4 slot5 slot6',
                '  LOAD_EXTDATA_1 slot7 slot3 z',
                '  MOD_SLOT0 *slot0 slot4 slot7',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],

            // Comparisons.
            '{{ x < y }}' => [
                '  LOAD_EXTDATA_1 slot3 slot1 x',
                '  LOAD_EXTDATA_1 slot4 slot2 y',
                '  LT_SLOT0 *slot0 slot3 slot4',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ x > y }}' => [
                '  LOAD_EXTDATA_1 slot3 slot1 x',
                '  LOAD_EXTDATA_1 slot4 slot2 y',
                '  LT_SLOT0 *slot0 slot4 slot3',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ x <= y }}' => [
                '  LOAD_EXTDATA_1 slot3 slot1 x',
                '  LOAD_EXTDATA_1 slot4 slot2 y',
                '  LT_EQ_SLOT0 *slot0 slot3 slot4',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ x >= y }}' => [
                '  LOAD_EXTDATA_1 slot3 slot1 x',
                '  LOAD_EXTDATA_1 slot4 slot2 y',
                '  LT_EQ_SLOT0 *slot0 slot4 slot3',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],

            // Array indexing.
            '{{ x[10] }}' => [
                '  LOAD_EXTDATA_1 slot2 slot1 x',
                '  INDEX_SLOT0_INT_KEY *slot0 slot2 10',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ x["aaa"] }}' => [
                '  LOAD_EXTDATA_1 slot2 slot1 x',
                '  INDEX_SLOT0_STRING_KEY *slot0 slot2 `aaa`',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ x[y] }}' => [
                '  LOAD_EXTDATA_1 slot3 slot1 x',
                '  LOAD_EXTDATA_1 slot4 slot2 y',
                '  INDEX_SLOT0 *slot0 slot3 slot4',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ x[1][2] }}' => [
                '  LOAD_EXTDATA_1 slot3 slot1 x',
                '  INDEX_INT_KEY slot2 slot3 1',
                '  INDEX_SLOT0_INT_KEY *slot0 slot2 2',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ x["y"]["z"] }}' => [
                '  LOAD_EXTDATA_1 slot3 slot1 x',
                '  INDEX_STRING_KEY slot2 slot3 `y`',
                '  INDEX_SLOT0_STRING_KEY *slot0 slot2 `z`',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],

            // And/or.
            '{% let $x = 1 %}{% let $y = 2 %}{{ $x and $y }}' => [
                '  LOAD_INT_CONST slot1 1',
                '  LOAD_INT_CONST slot2 2',
                '  AND_SLOT0 *slot0 slot1 slot2',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{% let $x = 1 %}{% let $y = 2 %}{{ $x and $y and $x }}' => [
                '  LOAD_INT_CONST slot1 1',
                '  LOAD_INT_CONST slot2 2',
                '  MOVE_SLOT0_BOOL *slot0 slot1',
                '  JUMP_SLOT0_FALSY *slot0 L0',
                '  AND_SLOT0 *slot0 slot2 slot1',
                'L0:',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{% let $x = 1 %}{% let $y = 2 %}{{ $x or $y }}' => [
                '  LOAD_INT_CONST slot1 1',
                '  LOAD_INT_CONST slot2 2',
                '  OR_SLOT0 *slot0 slot1 slot2',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{% let $x = 1 %}{% let $y = 2 %}{{ $x or $y or $x }}' => [
                '  LOAD_INT_CONST slot1 1',
                '  LOAD_INT_CONST slot2 2',
                '  MOVE_SLOT0_BOOL *slot0 slot1',
                '  JUMP_SLOT0_TRUTHY *slot0 L0',
                '  OR_SLOT0 *slot0 slot2 slot1',
                'L0:',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],

            '{% let $x = 1 %}{% let $y = 2 %}{{ $x or $y or $x or 1 }}' => [
                '  LOAD_INT_CONST slot1 1',
                '  LOAD_INT_CONST slot2 2',
                '  MOVE_SLOT0_BOOL *slot0 slot1',
                '  JUMP_SLOT0_TRUTHY *slot0 L0',
                '  MOVE_SLOT0_BOOL *slot0 slot2',
                '  JUMP_SLOT0_TRUTHY *slot0 L0',
                '  MOVE_SLOT0_BOOL *slot0 slot1',
                '  JUMP_SLOT0_TRUTHY *slot0 L0',
                '  LOAD_SLOT0_INT_CONST *slot0 1',
                '  CONV_SLOT0_BOOL *slot0',
                'L0:',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],

            // Bool constants.
            '{% let $x = true %}' => [
                '  LOAD_BOOL slot1 $1',
                '  RETURN',
            ],
            '{% let $x = false %}' => [
                '  LOAD_BOOL slot1 $0',
                '  RETURN',
            ],

            // Funcs.
            '{{ testfunc0() }}' => [
                '  CALL_SLOT0_FUNC0 *slot0 testfunc0',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ testfunc1(testfunc0()) }}' => [
                '  CALL_FUNC0 slot1 testfunc0',
                '  CALL_SLOT0_FUNC1 *slot0 slot1 testfunc1',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ testfunc3(1, 2, 3) }}' => [
                '  LOAD_INT_CONST slot1 1',
                '  LOAD_INT_CONST slot2 2',
                '  LOAD_INT_CONST slot3 3',
                '  CALL_SLOT0_FUNC3 *slot0 slot1 slot2 slot3 testfunc3',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ testfunc1(testfunc3(1, 2, 3)) }}' => [
                '  LOAD_INT_CONST slot2 1',
                '  LOAD_INT_CONST slot3 2',
                '  LOAD_INT_CONST slot4 3',
                '  CALL_FUNC3 slot1 slot2 slot3 slot4 testfunc3',
                '  CALL_SLOT0_FUNC1 *slot0 slot1 testfunc1',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],

            // Filters.
            '{{ s|strlen }}' => [
                '  LOAD_EXTDATA_1 slot2 slot1 s',
                '  CALL_SLOT0_FILTER1 *slot0 slot2 strlen',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ s|strlen + 1 }}{{ s|strlen }}' => [
                '  LOAD_EXTDATA_1 slot3 slot1 s',
                '  CALL_FILTER1 slot2 slot3 strlen',
                '  LOAD_INT_CONST slot4 1',
                '  ADD_SLOT0 *slot0 slot2 slot4',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  LOAD_EXTDATA_1 slot2 slot1 s',
                '  CALL_SLOT0_FILTER1 *slot0 slot2 strlen',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ 10|add1|add1 }}' => [
                '  LOAD_INT_CONST slot2 10',
                '  CALL_FILTER1 slot1 slot2 add1',
                '  CALL_SLOT0_FILTER1 *slot0 slot1 add1',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ 10|add1|sub1|add1 }}' => [
                '  LOAD_INT_CONST slot3 10',
                '  CALL_FILTER1 slot2 slot3 add1',
                '  CALL_FILTER1 slot1 slot2 sub1',
                '  CALL_SLOT0_FILTER1 *slot0 slot1 add1',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ s|length }}' => [
                '  LOAD_EXTDATA_1 slot2 slot1 s',
                '  LENGTH_SLOT0_FILTER slot2 slot1',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ (s|length) + 1 }}' => [
                '  LOAD_EXTDATA_1 slot3 slot1 s',
                '  LENGTH_FILTER slot2 slot3',
                '  LOAD_INT_CONST slot4 1',
                '  ADD_SLOT0 *slot0 slot2 slot4',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ x|default(0) }}' => [
                '  LOAD_EXTDATA_1 slot2 slot1 x',
                '  LOAD_INT_CONST slot3 0',
                '  DEFAULT_SLOT0_FILTER slot2 slot3 slot1',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ x|mydefault(0) }}' => [
                '  LOAD_EXTDATA_1 slot2 slot1 x',
                '  LOAD_INT_CONST slot3 0',
                '  CALL_SLOT0_FILTER2 *slot0 slot2 slot3 mydefault',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ x|mydefault(0)|add1|mydefault(10) }}' => [
                '  LOAD_EXTDATA_1 slot4 slot1 x',
                '  LOAD_INT_CONST slot5 0',
                '  CALL_FILTER2 slot3 slot4 slot5 mydefault',
                '  CALL_FILTER1 slot2 slot3 add1',
                '  LOAD_INT_CONST slot6 10',
                '  CALL_SLOT0_FILTER2 *slot0 slot2 slot6 mydefault',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ x|mydefault(y) }}' => [
                '  LOAD_EXTDATA_1 slot3 slot1 x',
                '  LOAD_EXTDATA_1 slot4 slot2 y',
                '  CALL_SLOT0_FILTER2 *slot0 slot3 slot4 mydefault',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ x|escape }}' => [
                '  LOAD_EXTDATA_1 slot2 slot1 x',
                '  ESCAPE_SLOT0_FILTER1 *slot0 slot2',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ (x|escape) ~ "a" }}' => [
                '  LOAD_EXTDATA_1 slot3 slot1 x',
                '  ESCAPE_FILTER1 slot2 slot3',
                '  LOAD_STRING_CONST slot4 `a`',
                '  CONCAT_SLOT0 *slot0 slot2 slot4',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ x|escape("html") }}' => [
                '  LOAD_EXTDATA_1 slot2 slot1 x',
                '  ESCAPE_SLOT0_FILTER2 *slot0 slot2 `html`',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ (x|escape("url")) ~ "a" }}' => [
                '  LOAD_EXTDATA_1 slot3 slot1 x',
                '  ESCAPE_FILTER2 slot2 slot3 `url`',
                '  LOAD_STRING_CONST slot4 `a`',
                '  CONCAT_SLOT0 *slot0 slot2 slot4',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],

            // If blocks.
            '{% if 1 %}a{% end %}' => [
                '  LOAD_SLOT0_INT_CONST *slot0 1',
                '  JUMP_SLOT0_FALSY *slot0 L0',
                '  OUTPUT_SAFE_STRING_CONST `a`',
                'L0:',
                '  RETURN',
            ],
            '{% if 1 %}a{% else %}b{% end %}' => [
                '  LOAD_SLOT0_INT_CONST *slot0 1',
                '  JUMP_SLOT0_FALSY *slot0 L0',
                '  OUTPUT_SAFE_STRING_CONST `a`',
                '  JUMP L1',
                'L0:',
                '  OUTPUT_SAFE_STRING_CONST `b`',
                'L1:',
                '  RETURN',
            ],
            '{% if 1 %}a{% elseif 2 %}b{% end %}' => [
                '  LOAD_SLOT0_INT_CONST *slot0 1',
                '  JUMP_SLOT0_FALSY *slot0 L0',
                '  OUTPUT_SAFE_STRING_CONST `a`',
                '  JUMP L1',
                'L0:',
                '  LOAD_SLOT0_INT_CONST *slot0 2',
                '  JUMP_SLOT0_FALSY *slot0 L1',
                '  OUTPUT_SAFE_STRING_CONST `b`',
                'L1:',
                '  RETURN',
            ],
            '{% if 1 %}a{% elseif 2 %}b{% elseif 3 %}c{% end %}' => [
                '  LOAD_SLOT0_INT_CONST *slot0 1',
                '  JUMP_SLOT0_FALSY *slot0 L0',
                '  OUTPUT_SAFE_STRING_CONST `a`',
                '  JUMP L1',
                'L0:',
                '  LOAD_SLOT0_INT_CONST *slot0 2',
                '  JUMP_SLOT0_FALSY *slot0 L2',
                '  OUTPUT_SAFE_STRING_CONST `b`',
                '  JUMP L1',
                'L2:',
                '  LOAD_SLOT0_INT_CONST *slot0 3',
                '  JUMP_SLOT0_FALSY *slot0 L1',
                '  OUTPUT_SAFE_STRING_CONST `c`',
                'L1:',
                '  RETURN',
            ],
            '{% if 1 %}a{% elseif 2 %}b{% else %}c{% end %}' => [
                '  LOAD_SLOT0_INT_CONST *slot0 1',
                '  JUMP_SLOT0_FALSY *slot0 L0',
                '  OUTPUT_SAFE_STRING_CONST `a`',
                '  JUMP L1',
                'L0:',
                '  LOAD_SLOT0_INT_CONST *slot0 2',
                '  JUMP_SLOT0_FALSY *slot0 L2',
                '  OUTPUT_SAFE_STRING_CONST `b`',
                '  JUMP L1',
                'L2:',
                '  OUTPUT_SAFE_STRING_CONST `c`',
                'L1:',
                '  RETURN',
            ],

            '{{ null }}' => [
                '  LOAD_SLOT0_NULL',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            
            // Loops.
            '{% for $x in xs %}{{ $x }}{% end %}' => [
                '  LOAD_SLOT0_EXTDATA_1 *slot0 slot1 xs',
                '  FOR_VAL *slot0 L0 slot2',
                '  OUTPUT_SAFE slot2',
                '  RETURN',
                'L0:',
                '  RETURN',
            ],
            '{% for $k, $v in xs %}{{ $k ~ "/" ~ $v }}{% end %}' => [
                '  LOAD_SLOT0_EXTDATA_1 *slot0 slot1 xs',
                '  FOR_KEY_VAL *slot0 L0 slot2 slot3',
                '  LOAD_STRING_CONST slot5 `/`',
                '  CONCAT slot4 slot2 slot5',
                '  CONCAT_SLOT0 *slot0 slot4 slot3',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
                'L0:',
                '  RETURN',
            ],
            '{% let $arr = arr %}{% for $x in $arr %}{{ $x }}{% end %}' => [
                '  LOAD_EXTDATA_1 slot2 slot1 arr',
                '  MOVE_SLOT0 *slot0 slot2',
                '  FOR_VAL *slot0 L0 slot3',
                '  OUTPUT_SAFE slot3',
                '  RETURN',
                'L0:',
                '  RETURN',
            ],
            '{% for $x in xs %}1{% else %}2{% end %}' => [
                '  LOAD_SLOT0_EXTDATA_1 *slot0 slot1 xs',
                '  FOR_VAL *slot0 L0 slot2',
                '  OUTPUT_SAFE_STRING_CONST `1`',
                '  RETURN',
                'L0:',
                '  JUMP_SLOT0_TRUTHY *slot0 L1',
                '  OUTPUT_SAFE_STRING_CONST `2`',
                'L1:',
                '  RETURN',
            ],

            // Switching output.
            '{% let $s %}{% end %}' => [
                '  START_TMP_OUTPUT',
                '  FINISH_TMP_OUTPUT slot1',
                '  RETURN',
            ],
            '{% let $s %}123{% end %}' => [
                '  START_TMP_OUTPUT',
                '  OUTPUT_SAFE_STRING_CONST `123`',
                '  FINISH_TMP_OUTPUT slot1',
                '  RETURN',
            ],
            '{% let $x = 10 %}{% let $s %}{{ $x }}{{ $x }}{% end %}' => [
                '  LOAD_INT_CONST slot1 10',
                '  START_TMP_OUTPUT',
                '  OUTPUT_SAFE slot1',
                '  OUTPUT_SAFE slot1',
                '  FINISH_TMP_OUTPUT slot2',
                '  RETURN',
            ],
            '{% let $x = 1 %}{% set $x %}2{% end %}' => [
                '  LOAD_INT_CONST slot1 1',
                '  START_TMP_OUTPUT',
                '  OUTPUT_SAFE_STRING_CONST `2`',
                '  FINISH_TMP_OUTPUT slot1',
                '  RETURN',
            ],
        ];

        $env = $this->newTestEnv();
        $env->escape_config->escape_func = null;
        foreach ($tests as $input => $want) {
            $t = self::$compiler->compile($env, 'test', (string)$input);
            $have = Disasm::getBytecode($env, $t);
            $have_pretty = [];
            foreach ($have as $s) {
                $have_pretty[] = "'$s',";
            }
            $this->assertEquals($want, $have, "input=$input\n" . implode("\n", $have_pretty));
        }
    }

    /**
     * @param LoaderInterface $loader
     * @return Env
     */
    private function newTestEnv($loader = null) {
        $env = new Env($loader);
        $env->registerFilter1('strlen', function ($s) { return strlen($s); });
        $env->registerFilter1('add1', function ($x) { return $x + 1; });
        $env->registerFilter1('sub1', function ($x) { return $x - 1; });
        $env->registerFilter2('mydefault', function ($x, $or_else) { return $x ?? $or_else; });
        $env->registerFunction0('testfunc0', function () { return 10; });
        $env->registerFunction1('testfunc1', function ($x) { return $x; });
        $env->registerFunction2('testfunc2', function ($x, $y) { return $x + $y; });
        $env->registerFunction3('testfunc3', function ($x, $y, $z) { return $x + $y + $z; });
        return $env;
    }
}
