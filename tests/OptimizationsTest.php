<?php

use PHPUnit\Framework\TestCase;
use KTemplate\Internal\Compile\Compiler;
use KTemplate\Context;
use KTemplate\Internal\Env;
use KTemplate\Internal\Disasm;

class OptimizationsTest extends TestCase {
    /** @var Compiler */
    private static $compiler;

    public static function setUpBeforeClass(): void {
        self::$compiler = new Compiler();
    }

    public function testOptimize() {
        $tests = [
            // Reordered instructions: merged output ops.
            // Merged constants should not be present in the frame.
            ' {% let $x = "a" %}  {{ $x }}' => [
                'slots={cache:0 local:2} constants={s:2 i:0 f:0}',
                '  OUTPUT_SAFE_STRING_CONST `   `',
                '  LOAD_STRING_CONST slot1 `a`',
                '  OUTPUT_SAFE slot1',
                '  RETURN',
            ],

            // Comments should not interfere with the constant output merging.
            'a{# hello #}b' => [
                'slots={cache:0 local:1} constants={s:1 i:0 f:0}',
                '  OUTPUT_SAFE_STRING_CONST `ab`',
                '  RETURN',
            ],

            // Comments handling + output merging.
            'a{% let $x = "a" %}b{# ok #}c{{ $x }}' => [
                'slots={cache:0 local:2} constants={s:2 i:0 f:0}',
                '  OUTPUT_SAFE_STRING_CONST `abc`',
                '  LOAD_STRING_CONST slot1 `a`',
                '  OUTPUT_SAFE slot1',
                '  RETURN',
            ],

            // Const-folded output should be merged too.
            'a{{ "b" ~ "c" }}' => [
                'slots={cache:0 local:1} constants={s:1 i:0 f:0}',
                '  OUTPUT_SAFE_STRING_CONST `abc`',
                '  RETURN',
            ],

            // Inlined jump conditions.
            // Generate the right conditional jump op instead of
            // evaluating all operands and doing JUMP_FALSY.
            '{% let $x = 1 %}{% if $x != null %}1{% end %}' => [
                'slots={cache:0 local:2} constants={s:1 i:1 f:0}',
                '  LOAD_INT_CONST slot1 1',
                '  JUMP_NOT_NULL L0 slot1',
                '  OUTPUT_SAFE_STRING_CONST `1`',
                'L0:',
                '  RETURN',
            ],

            // Cond expressions should not involve extra MOVE operations
            // for their conditions if cond is already inside a slot.
            '{% let $x = 10 %}{% if $x %}1{% end %}' => [
                'slots={cache:0 local:2} constants={s:1 i:1 f:0}',
                '  LOAD_INT_CONST slot1 10',
                '  JUMP_FALSY L0 slot1',
                '  OUTPUT_SAFE_STRING_CONST `1`',
                'L0:',
                '  RETURN',
            ],

            // Direct output.
            '{% let $x = 10 %}{{ $x }}' => [
                'slots={cache:0 local:2} constants={s:0 i:1 f:0}',
                '  LOAD_INT_CONST slot1 10',
                '  OUTPUT_SAFE slot1',
                '  RETURN',
            ],

            // Const folding.
            '{{ -(-1) }}' => [
                'slots={cache:0 local:1} constants={s:0 i:1 f:0}',
                '  OUTPUT_SAFE_INT_CONST 1',
                '  RETURN',
            ],
            '{{ "a" ~ "b" }}' => [
                'slots={cache:0 local:1} constants={s:1 i:0 f:0}',
                '  OUTPUT_SAFE_STRING_CONST `ab`',
                '  RETURN',
            ],
            '{{ "a" ~ "b" ~ x }}' => [
                'slots={cache:1 local:2} constants={s:1 i:0 f:0}',
                '  OUTPUT_SAFE_STRING_CONST `ab`',
                '  LOAD_EXTDATA_1 slot2 [slot1] x',
                '  OUTPUT_SAFE slot2',
                '  RETURN',
            ],
            '{{ testfunc1(1 + 2) }}' => [
                'slots={cache:0 local:2} constants={s:0 i:1 f:0}',
                '  LOAD_INT_CONST slot1 3',
                '  CALL_SLOT0_FUNC1 *slot0 slot1 testfunc1',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            // do not reload extdata inside one expression; re-use slot2 here
            '{{ x ~ "a" ~ "b" ~ x ~ "c" ~ "d" }}' => [
                'slots={cache:1 local:4} constants={s:2 i:0 f:0}',
                '  LOAD_EXTDATA_1 slot2 [slot1] x',
                '  LOAD_STRING_CONST slot3 `ab`',
                '  OUTPUT2_SAFE slot2 slot3',
                '  LOAD_STRING_CONST slot4 `cd`',
                '  OUTPUT2_SAFE [slot1] slot4',
                '  RETURN',
            ],
            '{{ x.y ~ x.y }}' => [
                'slots={cache:1 local:2} constants={s:0 i:0 f:0}',
                '  LOAD_EXTDATA_2 slot2 [slot1] x.y',
                '  OUTPUT2_SAFE slot2 [slot1]',
                '  RETURN',
            ],
            '{{ x.y ~ x ~ x.y ~ x }}{{ x }}{{ x ~ "x" }}' => [
                'slots={cache:2 local:3} constants={s:1 i:0 f:0}',
                '  LOAD_EXTDATA_2 slot3 [slot1] x.y',
                '  LOAD_EXTDATA_1 slot4 [slot2] x',
                '  OUTPUT2_SAFE slot3 slot4',
                '  OUTPUT2_SAFE [slot1] [slot2]',
                '  OUTPUT_EXTDATA_1 [slot2] x $0',
                '  LOAD_EXTDATA_1 slot3 [slot2] x',
                '  LOAD_STRING_CONST slot4 `x`',
                '  OUTPUT2_SAFE slot3 slot4',
                '  RETURN',
            ],
            '{{ x + 10 + 20 }}' => [
                'slots={cache:1 local:3} constants={s:0 i:1 f:0}',
                '  LOAD_EXTDATA_1 slot2 [slot1] x',
                '  LOAD_INT_CONST slot3 30',
                '  ADD_SLOT0 *slot0 slot2 slot3',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ x * 10 * 20 }}' => [
                'slots={cache:1 local:3} constants={s:0 i:1 f:0}',
                '  LOAD_EXTDATA_1 slot2 [slot1] x',
                '  LOAD_INT_CONST slot3 200',
                '  MUL_SLOT0 *slot0 slot2 slot3',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
        ];

        $ctx = new Context();
        $ctx->escape_func = null;
        $env = new Env($ctx, null);
        $env->registerFunction0('testfunc0', function () { return 10; });
        $env->registerFunction1('testfunc1', function ($x) { return $x; });
        $env->registerFunction2('testfunc2', function ($x, $y) { return $x + $y; });
        $env->registerFunction3('testfunc3', function ($x, $y, $z) { return $x + $y + $z; });
        foreach ($tests as $input => $want) {
            $t = self::$compiler->compile($env, 'test', (string)$input);
            $have = [Disasm::getFrameHeader($env, $t), ...Disasm::getBytecode($env, $t)];
            $have_pretty = [];
            foreach ($have as $s) {
                $have_pretty[] = "'$s',";
            }
            $this->assertEquals($want, $have, "input=$input\n" . implode("\n", $have_pretty));
        }
    }
}
