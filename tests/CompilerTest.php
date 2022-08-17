<?php

use PHPUnit\Framework\TestCase;
use KTemplate\Compile\Compiler;
use KTemplate\Disasm;

class CompilerTest extends TestCase {
    /** @var Compiler */
    private static $compiler;

    public static function setUpBeforeClass(): void {
        self::$compiler = new Compiler();
    }

    public function testCompile() {
        $tests = [
            // Local variables.
            '{% let $s = "abc" %}{{ $s }}' => [
                '  LOAD_STRING_CONST slot1 `abc`',
                '  OUTPUT slot1',
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

            // Bool constants.
            '{% let $x = true %}' => [
                '  LOAD_BOOL slot1 $1',
                '  RETURN',
            ],
            '{% let $x = false %}' => [
                '  LOAD_BOOL slot1 $0',
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
        ];

        foreach ($tests as $input => $want) {
            $t = self::$compiler->compile('test', (string)$input);
            $have = Disasm::getBytecode($t);
            $have_pretty = [];
            foreach ($have as $s) {
                $have_pretty[] = "'$s',";
            }
            $this->assertEquals($have, $want, "input=$input\n" . implode("\n", $have_pretty));
        }
    }
}
