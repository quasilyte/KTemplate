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

            // Controls.
            [
                '{% if 1 %}',
                ['CONTROL_START', 'IF', 'INT_LIT(1)', 'CONTROL_END'],
            ],
            [
                '{% endif %}',
                ['CONTROL_START', 'ENDIF', 'CONTROL_END'],
            ],
            [
                '{% endfor %}',
                ['CONTROL_START', 'ENDFOR', 'CONTROL_END'],
            ],
            [
                '{%endfor%}',
                ['CONTROL_START', 'ENDFOR', 'CONTROL_END'],
            ],
            [
                '{% else %}',
                ['CONTROL_START', 'ELSE', 'CONTROL_END'],
            ],
            [
                '{% elseif %}',
                ['CONTROL_START', 'ELSEIF', 'CONTROL_END'],
            ],

            // Numeric literals.
            [
                '{{ -1 }}',
                ['ECHO_START', 'MINUS', 'INT_LIT(1)', 'ECHO_END'],
            ],

            // String literals.
            [
                '{{ "" }}',
                ['ECHO_START', 'STRING_LIT_Q2("")', 'ECHO_END'],
            ],
            [
                '{{ "abc" }}',
                ['ECHO_START', 'STRING_LIT_Q2("abc")', 'ECHO_END'],
            ],
            [
                '{{ "ab\"c" }}',
                ['ECHO_START', 'STRING_LIT_Q2("ab\"c")', 'ECHO_END'],
            ],
            [
                "{{ '' }}",
                ['ECHO_START', "STRING_LIT_Q1('')", 'ECHO_END'],
            ],
            [
                "{{ 'abc' }}",
                ['ECHO_START', "STRING_LIT_Q1('abc')", 'ECHO_END'],
            ],
            [
                "{{ 'ab\'c' }}",
                ['ECHO_START', "STRING_LIT_Q1('ab\'c')", 'ECHO_END'],
            ],

            // Expressions.
            [
                '{{ $x }}',
                ['ECHO_START', 'DOLLAR_IDENT($x)', 'ECHO_END'],
            ],
            [
                '{{ x.y.z }}',
                ['ECHO_START', 'IDENT(x)', 'DOT', 'IDENT(y)', 'DOT', 'IDENT(z)', 'ECHO_END'],
            ],
            [
                '{{ (x) }}',
                ['ECHO_START', 'LPAREN', 'IDENT(x)', 'RPAREN', 'ECHO_END'],
            ],
            [
                '{{ ( null ) }}',
                ['ECHO_START', 'LPAREN', 'NULL', 'RPAREN', 'ECHO_END'],
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
                '{{ x / y % z }}',
                ['ECHO_START', 'IDENT(x)', 'SLASH', 'IDENT(y)', 'PERCENT', 'IDENT(z)', 'ECHO_END'],
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
                '{{ for set and use let }}',
                ['ECHO_START', 'FOR', 'SET', 'AND', 'USE', 'LET', 'ECHO_END'],
            ],
            [
                '{{ < = }}',
                ['ECHO_START', 'LT', 'ASSIGN', 'ECHO_END'],
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
            [
                '{{ x|y }}',
                ['ECHO_START', 'IDENT(x)', 'PIPE', 'IDENT(y)', 'ECHO_END'],
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
                    $have[] = "$kind_string(" . $lexer->tokenText($tok) . ')';
                } else {
                    $have[] = $kind_string;
                }
            }
            $this->assertEquals($have, $want);
        }
    }
}
