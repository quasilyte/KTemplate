<?php

use PHPUnit\Framework\TestCase;
use KTemplate\Internal\Compile\Compiler;
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
            // Inlined jump conditions.
            // Generate the right conditional jump op instead of
            // evaluating all operands and doing JUMP_FALSY.
            '{% let $x = 1 %}{% if $x != null %}1{% end %}' => [
                '  LOAD_INT_CONST slot1 1',
                '  JUMP_NOT_NULL L0 slot1',
                '  OUTPUT_SAFE_STRING_CONST `1`',
                'L0:',
                '  RETURN',
            ],

            // Cond expressions should not involve extra MOVE operations
            // for their conditions if cond is already inside a slot.
            '{% let $x = 10 %}{% if $x %}1{% end %}' => [
                '  LOAD_INT_CONST slot1 10',
                '  JUMP_FALSY L0 slot1',
                '  OUTPUT_SAFE_STRING_CONST `1`',
                'L0:',
                '  RETURN',
            ],

            // Direct output.
            '{% let $x = 10 %}{{ $x }}' => [
                '  LOAD_INT_CONST slot1 10',
                '  OUTPUT_SAFE slot1',
                '  RETURN',
            ],

            // Const folding.
            '{{ -(-1) }}' => [
                '  OUTPUT_SAFE_INT_CONST 1',
                '  RETURN',
            ],
            '{{ "a" ~ "b" }}' => [
                '  OUTPUT_SAFE_STRING_CONST `ab`',
                '  RETURN',
            ],
            '{{ "a" ~ "b" ~ x }}' => [
                '  LOAD_STRING_CONST slot2 `ab`',
                '  LOAD_EXTDATA_1 slot3 slot1 x',
                '  CONCAT_SLOT0 *slot0 slot2 slot3',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ testfunc1(1 + 2) }}' => [
                '  LOAD_INT_CONST slot1 3',
                '  CALL_SLOT0_FUNC1 *slot0 slot1 testfunc1',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ x ~ "a" ~ "b" }}' => [
                '  LOAD_EXTDATA_1 slot2 slot1 x',
                '  LOAD_STRING_CONST slot3 `ab`',
                '  CONCAT_SLOT0 *slot0 slot2 slot3',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ x + 10 + 20 }}' => [
                '  LOAD_EXTDATA_1 slot2 slot1 x',
                '  LOAD_INT_CONST slot3 30',
                '  ADD_SLOT0 *slot0 slot2 slot3',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
            '{{ x * 10 * 20 }}' => [
                '  LOAD_EXTDATA_1 slot2 slot1 x',
                '  LOAD_INT_CONST slot3 200',
                '  MUL_SLOT0 *slot0 slot2 slot3',
                '  OUTPUT_SAFE_SLOT0 *slot0',
                '  RETURN',
            ],
        ];

        $env = new Env(null);
        $env->registerFunction0('testfunc0', function () { return 10; });
        $env->registerFunction1('testfunc1', function ($x) { return $x; });
        $env->registerFunction2('testfunc2', function ($x, $y) { return $x + $y; });
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
}