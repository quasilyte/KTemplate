<?php

use PHPUnit\Framework\TestCase;
use KTemplate\Compile\Compiler;
use KTemplate\Env;
use KTemplate\Disasm;

class CompilerTest extends TestCase {
    /** @var Compiler */
    private static $compiler;

    public static function setUpBeforeClass(): void {
        self::$compiler = new Compiler();
    }

    public function testCompile() {
        $tests = [
            // Numeric constants.
            '{{ -1 }}' => [
                '  OUTPUT_INT_CONST -1',
                '  RETURN',
            ],

            // Local variables.
            '{% let $s = "abc" %}{{ $s }}' => [
                '  LOAD_STRING_CONST slot1 `abc`',
                '  OUTPUT slot1',
                '  RETURN',
            ],

            // Extdata.
            '{{ x }}{% let $y = 2 %}{{ x }}{{ $y }}' => [
                '  OUTPUT_EXTDATA_1 slot1 x',
                '  LOAD_INT_CONST slot2 2',
                '  OUTPUT_EXTDATA_1 slot1 x',
                '  OUTPUT slot2',
                '  RETURN',
            ],
            '{{ x }}{% let $s = "a" %}{{ $s == "a" }}' => [
                '  OUTPUT_EXTDATA_1 slot1 x',
                '  LOAD_STRING_CONST slot2 `a`',
                '  LOAD_STRING_CONST slot3 `a`',
                '  EQ_SLOT0 *slot0 slot2 slot3',
                '  OUTPUT_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ x + x }}' => [
                '  LOAD_EXTDATA_1 slot2 slot1 x',
                '  LOAD_EXTDATA_1 slot3 slot1 x',
                '  ADD_SLOT0 *slot0 slot2 slot3',
                '  OUTPUT_SLOT0 *slot0',
                '  RETURN',
            ],
            '{% let $i=1 %}{{ x.y }}{{$i}}' => [
                '  LOAD_INT_CONST slot2 1',
                '  OUTPUT_EXTDATA_2 slot1 x.y',
                '  OUTPUT slot2',
                '  RETURN',
            ],
            '{% let $i=1 %}{{ x.y.z }}{{$i}}' => [
                '  LOAD_INT_CONST slot2 1',
                '  OUTPUT_EXTDATA_3 slot1 x.y.z',
                '  OUTPUT slot2',
                '  RETURN',
            ],
            '{{ x }}{{ x.y }}{{ x.y.z }}{{ x }}{{ x.y }}' => [
                '  OUTPUT_EXTDATA_1 slot1 x',
                '  OUTPUT_EXTDATA_2 slot2 x.y',
                '  OUTPUT_EXTDATA_3 slot3 x.y.z',
                '  OUTPUT_EXTDATA_1 slot1 x',
                '  OUTPUT_EXTDATA_2 slot2 x.y',
                '  RETURN',
            ],
            '{{ x.y + x.y.z }}' => [
                '  LOAD_EXTDATA_2 slot3 slot1 x.y',
                '  LOAD_EXTDATA_3 slot4 slot2 x.y.z',
                '  ADD_SLOT0 *slot0 slot3 slot4',
                '  OUTPUT_SLOT0 *slot0',
                '  RETURN',
            ],

            // Operators.
            '{% let $x = 1 %}{{ $x + $x }}' => [
                '  LOAD_INT_CONST slot1 1',
                '  ADD_SLOT0 *slot0 slot1 slot1',
                '  OUTPUT_SLOT0 *slot0',
                '  RETURN',
            ],
            '{% let $x = 1 %}{{ $x == 1 }}' => [
                '  LOAD_INT_CONST slot1 1',
                '  LOAD_INT_CONST slot2 1',
                '  EQ_SLOT0 *slot0 slot1 slot2',
                '  OUTPUT_SLOT0 *slot0',
                '  RETURN',
            ],
            '{% let $s = "a" %}{{ $s ~ $s ~ $s }}' => [
                '  LOAD_STRING_CONST slot1 `a`',
                '  CONCAT slot2 slot1 slot1',
                '  CONCAT_SLOT0 *slot0 slot2 slot1',
                '  OUTPUT_SLOT0 *slot0',
                '  RETURN',
            ],
            '{% let $b = true %}{{ not $b }}' => [
                '  LOAD_BOOL slot1 $1',
                '  NOT_SLOT0 *slot0 slot1',
                '  OUTPUT_SLOT0 *slot0',
                '  RETURN',
            ],
            '{% let $b = true %}{{ not not $b }}' => [
                '  LOAD_BOOL slot1 $1',
                '  NOT slot2 slot1',
                '  NOT_SLOT0 *slot0 slot2',
                '  OUTPUT_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ -x }}' => [
                '  LOAD_EXTDATA_1 slot2 slot1 x',
                '  NEG_SLOT0 *slot0 slot2',
                '  OUTPUT_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ -x - 10 }}' => [
                '  LOAD_EXTDATA_1 slot3 slot1 x',
                '  NEG slot2 slot3',
                '  LOAD_INT_CONST slot4 10',
                '  SUB_SLOT0 *slot0 slot2 slot4',
                '  OUTPUT_SLOT0 *slot0',
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
                '  OUTPUT_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ testfunc1(testfunc0()) }}' => [
                '  CALL_FUNC0 slot1 testfunc0',
                '  CALL_SLOT0_FUNC1 *slot0 slot1 testfunc1',
                '  OUTPUT_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ testfunc3(1, 2, 3) }}' => [
                '  LOAD_INT_CONST slot1 1',
                '  LOAD_INT_CONST slot2 2',
                '  LOAD_INT_CONST slot3 3',
                '  CALL_SLOT0_FUNC3 *slot0 slot1 slot2 slot3 testfunc3',
                '  OUTPUT_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ testfunc1(testfunc3(1, 2, 3)) }}' => [
                '  LOAD_INT_CONST slot2 1',
                '  LOAD_INT_CONST slot3 2',
                '  LOAD_INT_CONST slot4 3',
                '  CALL_FUNC3 slot1 slot2 slot3 slot4 testfunc3',
                '  CALL_SLOT0_FUNC1 *slot0 slot1 testfunc1',
                '  OUTPUT_SLOT0 *slot0',
                '  RETURN',
            ],

            // Filters.
            '{{ s|strlen }}' => [
                '  LOAD_EXTDATA_1 slot2 slot1 s',
                '  CALL_SLOT0_FILTER1 *slot0 slot2 strlen',
                '  OUTPUT_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ s|strlen + 1 }}{{ s|strlen }}' => [
                '  LOAD_EXTDATA_1 slot3 slot1 s',
                '  CALL_FILTER1 slot2 slot3 strlen',
                '  LOAD_INT_CONST slot4 1',
                '  ADD_SLOT0 *slot0 slot2 slot4',
                '  OUTPUT_SLOT0 *slot0',
                '  LOAD_EXTDATA_1 slot2 slot1 s',
                '  CALL_SLOT0_FILTER1 *slot0 slot2 strlen',
                '  OUTPUT_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ 10|add1|add1 }}' => [
                '  LOAD_INT_CONST slot2 10',
                '  CALL_FILTER1 slot1 slot2 add1',
                '  CALL_SLOT0_FILTER1 *slot0 slot1 add1',
                '  OUTPUT_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ 10|add1|sub1|add1 }}' => [
                '  LOAD_INT_CONST slot3 10',
                '  CALL_FILTER1 slot2 slot3 add1',
                '  CALL_FILTER1 slot1 slot2 sub1',
                '  CALL_SLOT0_FILTER1 *slot0 slot1 add1',
                '  OUTPUT_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ s|length }}' => [
                '  LOAD_EXTDATA_1 slot2 slot1 s',
                '  LENGTH_SLOT0_FILTER slot2 slot1',
                '  OUTPUT_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ (s|length) + 1 }}' => [
                '  LOAD_EXTDATA_1 slot3 slot1 s',
                '  LENGTH_FILTER slot2 slot3',
                '  LOAD_INT_CONST slot4 1',
                '  ADD_SLOT0 *slot0 slot2 slot4',
                '  OUTPUT_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ x|default(0) }}' => [
                '  LOAD_EXTDATA_1 slot2 slot1 x',
                '  LOAD_INT_CONST slot3 0',
                '  CALL_SLOT0_FILTER2 *slot0 slot2 slot3 default',
                '  OUTPUT_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ x|default(0)|add1|default(10) }}' => [
                '  LOAD_EXTDATA_1 slot4 slot1 x',
                '  LOAD_INT_CONST slot5 0',
                '  CALL_FILTER2 slot3 slot4 slot5 default',
                '  CALL_FILTER1 slot2 slot3 add1',
                '  LOAD_INT_CONST slot6 10',
                '  CALL_SLOT0_FILTER2 *slot0 slot2 slot6 default',
                '  OUTPUT_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ x|default(y) }}' => [
                '  LOAD_EXTDATA_1 slot3 slot1 x',
                '  LOAD_EXTDATA_1 slot4 slot2 y',
                '  CALL_SLOT0_FILTER2 *slot0 slot3 slot4 default',
                '  OUTPUT_SLOT0 *slot0',
                '  RETURN',
            ],

            // If blocks.
            '{% if 1 %}a{% endif %}' => [
                '  LOAD_SLOT0_INT_CONST *slot0 1',
                '  JUMP_ZERO *slot0 L0',
                '  OUTPUT_STRING_CONST `a`',
                'L0:',
                '  RETURN',
            ],
            '{% if 1 %}a{% else %}b{% endif %}' => [
                '  LOAD_SLOT0_INT_CONST *slot0 1',
                '  JUMP_ZERO *slot0 L0',
                '  OUTPUT_STRING_CONST `a`',
                '  JUMP L1',
                'L0:',
                '  OUTPUT_STRING_CONST `b`',
                'L1:',
                '  RETURN',
            ],
            '{% if 1 %}a{% elseif 2 %}b{% endif %}' => [
                '  LOAD_SLOT0_INT_CONST *slot0 1',
                '  JUMP_ZERO *slot0 L0',
                '  OUTPUT_STRING_CONST `a`',
                '  JUMP L1',
                'L0:',
                '  LOAD_SLOT0_INT_CONST *slot0 2',
                '  JUMP_ZERO *slot0 L1',
                '  OUTPUT_STRING_CONST `b`',
                'L1:',
                '  RETURN',
            ],
            '{% if 1 %}a{% elseif 2 %}b{% elseif 3 %}c{% endif %}' => [
                '  LOAD_SLOT0_INT_CONST *slot0 1',
                '  JUMP_ZERO *slot0 L0',
                '  OUTPUT_STRING_CONST `a`',
                '  JUMP L1',
                'L0:',
                '  LOAD_SLOT0_INT_CONST *slot0 2',
                '  JUMP_ZERO *slot0 L2',
                '  OUTPUT_STRING_CONST `b`',
                '  JUMP L1',
                'L2:',
                '  LOAD_SLOT0_INT_CONST *slot0 3',
                '  JUMP_ZERO *slot0 L1',
                '  OUTPUT_STRING_CONST `c`',
                'L1:',
                '  RETURN',
            ],
            '{% if 1 %}a{% elseif 2 %}b{% else %}c{% endif %}' => [
                '  LOAD_SLOT0_INT_CONST *slot0 1',
                '  JUMP_ZERO *slot0 L0',
                '  OUTPUT_STRING_CONST `a`',
                '  JUMP L1',
                'L0:',
                '  LOAD_SLOT0_INT_CONST *slot0 2',
                '  JUMP_ZERO *slot0 L2',
                '  OUTPUT_STRING_CONST `b`',
                '  JUMP L1',
                'L2:',
                '  OUTPUT_STRING_CONST `c`',
                'L1:',
                '  RETURN',
            ],

            '{{ null }}' => [
                '  LOAD_SLOT0_NULL',
                '  OUTPUT_SLOT0 *slot0',
                '  RETURN',
            ],

            // Const folding.
            '{{ -(-1) }}' => [
                '  OUTPUT_INT_CONST 1',
                '  RETURN',
            ],
            '{{ "a" ~ "b" }}' => [
                '  OUTPUT_STRING_CONST `ab`',
                '  RETURN',
            ],
            '{{ "a" ~ "b" ~ x }}' => [
                '  LOAD_STRING_CONST slot2 `ab`',
                '  LOAD_EXTDATA_1 slot3 slot1 x',
                '  CONCAT_SLOT0 *slot0 slot2 slot3',
                '  OUTPUT_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ testfunc1(1 + 2) }}' => [
                '  LOAD_INT_CONST slot1 3',
                '  CALL_SLOT0_FUNC1 *slot0 slot1 testfunc1',
                '  OUTPUT_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ x ~ "a" ~ "b" }}' => [
                '  LOAD_EXTDATA_1 slot2 slot1 x',
                '  LOAD_STRING_CONST slot3 `ab`',
                '  CONCAT_SLOT0 *slot0 slot2 slot3',
                '  OUTPUT_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ x + 10 + 20 }}' => [
                '  LOAD_EXTDATA_1 slot2 slot1 x',
                '  LOAD_INT_CONST slot3 30',
                '  ADD_SLOT0 *slot0 slot2 slot3',
                '  OUTPUT_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ x * 10 * 20 }}' => [
                '  LOAD_EXTDATA_1 slot2 slot1 x',
                '  LOAD_INT_CONST slot3 200',
                '  MUL_SLOT0 *slot0 slot2 slot3',
                '  OUTPUT_SLOT0 *slot0',
                '  RETURN',
            ],
        ];

        $env = new Env();
        $env->registerFilter1('strlen', function ($s) { return strlen($s); });
        $env->registerFilter1('add1', function ($x) { return $x + 1; });
        $env->registerFilter1('sub1', function ($x) { return $x - 1; });
        $env->registerFilter2('default', function ($x, $or_else) { return $x ?? $or_else; });
        $env->registerFunction0('testfunc0', function () { return 10; });
        $env->registerFunction1('testfunc1', function ($x) { return $x; });
        $env->registerFunction2('testfunc2', function ($x, $y) { return $x + $y; });
        $env->registerFunction3('testfunc3', function ($x, $y, $z) { return $x + $y + $z; });
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
}
