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
            $t = self::$compiler->compile('test', $input);
            $have = Disasm::getBytecode($t);
            $have_pretty = [];
            foreach ($have as $s) {
                $have_pretty[] = "'$s',";
            }
            $this->assertEquals($have, $want, "input=$input\n" . implode("\n", $have_pretty));
        }
    }
}
