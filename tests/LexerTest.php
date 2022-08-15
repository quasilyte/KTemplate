<?php

use PHPUnit\Framework\TestCase;
use KTemplate\Compile\Token;
use KTemplate\Compile\Lexer;

class LexerTest extends TestCase {
    public function testScan() {
        $tests = [
            // Simple text.
            [
                'x',
                ['TEXT(x)'],
            ],
            [
                'hello, world',
                ['TEXT(hello, world)'],
            ],

            // Comments.
            [
                '{#comment#}',
                ['COMMENT(comment)'],
            ],
            [
                'left{#comment#}',
                ['TEXT(left)', 'COMMENT(comment)'],
            ],
            [
                '{#comment#}right',
                ['COMMENT(comment)', 'TEXT(right)'],
            ],
            [
                'left{#comment#}right',
                ['TEXT(left)', 'COMMENT(comment)', 'TEXT(right)'],
            ],
            [
                ' {#comment#}  ',
                ['TEXT( )', 'COMMENT(comment)', 'TEXT(  )'],
            ],
            [
                '{#c1#}{##}{#c3#} ',
                ['COMMENT(c1)', 'COMMENT()', 'COMMENT(c3)', 'TEXT( )'],
            ],

            // Expressions.
            [
                '{{ x }}',
                ['ECHO_START', 'IDENT(x)', 'ECHO_END'],
            ],
            [
                '{{ (x) }}',
                ['ECHO_START', 'LPAREN', 'IDENT(x)', 'RPAREN', 'ECHO_END'],
            ],
            [
                '{{ ( x ) }}',
                ['ECHO_START', 'LPAREN', 'IDENT(x)', 'RPAREN', 'ECHO_END'],
            ],
            [
                '{{ x + y }}',
                ['ECHO_START', 'IDENT(x)', 'PLUS', 'IDENT(y)', 'ECHO_END'],
            ],
            [
                '{{x+y}}',
                ['ECHO_START', 'IDENT(x)', 'PLUS', 'IDENT(y)', 'ECHO_END'],
            ],
            [
                '{{ x - y }}',
                ['ECHO_START', 'IDENT(x)', 'MINUS', 'IDENT(y)', 'ECHO_END'],
            ],
            [
                '{{ -x }}',
                ['ECHO_START', 'MINUS', 'IDENT(x)', 'ECHO_END'],
            ],
            [
                '{{x*y}}',
                ['ECHO_START', 'IDENT(x)', 'STAR', 'IDENT(y)', 'ECHO_END'],
            ],
            [
                '{{ x / y }}',
                ['ECHO_START', 'IDENT(x)', 'SLASH', 'IDENT(y)', 'ECHO_END'],
            ],
            [
                '{{ x + 1 }}',
                ['ECHO_START', 'IDENT(x)', 'PLUS', 'INT_LIT(1)', 'ECHO_END'],
            ],
            [
                '{{ x + 123 }}',
                ['ECHO_START', 'IDENT(x)', 'PLUS', 'INT_LIT(123)', 'ECHO_END'],
            ],
            [
                '{{ or }}',
                ['ECHO_START', 'OR', 'ECHO_END'],
            ],
            [
                '{{ if do }}',
                ['ECHO_START', 'IF', 'DO', 'ECHO_END'],
            ],
            [
                '{{ for set and use }}',
                ['ECHO_START', 'FOR', 'SET', 'AND', 'USE', 'ECHO_END'],
            ],
            [
                '{{ < }}',
                ['ECHO_START', 'LT', 'ECHO_END'],
            ],
            [
                '{{ <= }}',
                ['ECHO_START', 'LT_EQ', 'ECHO_END'],
            ],
            [
                '{{ > }}',
                ['ECHO_START', 'GT', 'ECHO_END'],
            ],
            [
                '{{ >= }}',
                ['ECHO_START', 'GT_EQ', 'ECHO_END'],
            ],
            [
                '{{ == }}',
                ['ECHO_START', 'EQ', 'ECHO_END'],
            ],
            [
                '{{ != }}',
                ['ECHO_START', 'NOT_EQ', 'ECHO_END'],
            ],
            [
                '{{ not }}',
                ['ECHO_START', 'NOT', 'ECHO_END'],
            ],

            // Mixing everything together.
            [
                'a{{x_2}}b',
                ['TEXT(a)', 'ECHO_START', 'IDENT(x_2)', 'ECHO_END', 'TEXT(b)'],
            ],
        ];
        $lexer = new Lexer();
        foreach ($tests as $test) {
            [$input, $want_toks] = $test;
            $lexer->setSource('test', (string)$input);
            $have = ["input=$input"];
            $want = ["input=$input"];
            $want = array_merge($want, $want_toks);
            while (true) {
                $tok = $lexer->scan();
                if ($tok->kind === Token::ERROR) {
                    $this->fail('unexpected error: ' . $lexer->getError());
                    break;
                }
                if ($tok->kind === Token::EOF) {
                    break;
                }
                $kind_string = Token::kindString($tok->kind);
                if (Token::hasValue($tok->kind)) {
                    $have[] = "$kind_string(" . $lexer->tokenValue($tok) . ')';
                } else {
                    $have[] = $kind_string;
                }
            }
            $this->assertEquals($have, $want);
        }
    }
}