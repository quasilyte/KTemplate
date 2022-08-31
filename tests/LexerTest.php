<?php

use PHPUnit\Framework\TestCase;
use KTemplate\Internal\Compile\TokenKind;
use KTemplate\Internal\Compile\Token;
use KTemplate\Internal\Compile\Lexer;

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

            // Trim tags.
            [
                '{{- $x -}}',
                ['ECHO_START_TRIM', 'DOLLAR_IDENT($x)', 'ECHO_END_TRIM'],
            ],
            [
                '{%- $x -%}',
                ['CONTROL_START_TRIM', 'DOLLAR_IDENT($x)', 'CONTROL_END_TRIM'],
            ],
            [
                '{{-$x}}',
                ['ECHO_START_TRIM', 'DOLLAR_IDENT($x)', 'ECHO_END'],
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
                '{% end %}',
                ['CONTROL_START', 'END', 'CONTROL_END'],
            ],
            [
                '{% else %}',
                ['CONTROL_START', 'ELSE', 'CONTROL_END'],
            ],
            [
                '{% elseif %}',
                ['CONTROL_START', 'ELSEIF', 'CONTROL_END'],
            ],
            [
                '{% arg param include %}',
                ['CONTROL_START', 'ARG', 'PARAM', 'INCLUDE', 'CONTROL_END'],
            ],

            // Numeric literals.
            [
                '{{ -1 }}',
                ['ECHO_START', 'MINUS', 'INT_LIT(1)', 'ECHO_END'],
            ],
            [
                '{{ 0.0 }}',
                ['ECHO_START', 'FLOAT_LIT(0.0)', 'ECHO_END'],
            ],
            [
                '{{ 14.512 }}',
                ['ECHO_START', 'FLOAT_LIT(14.512)', 'ECHO_END'],
            ],

            // String literals.
            [
                '{{ `` }}',
                ['ECHO_START', 'STRING_LIT_RAW(``)', 'ECHO_END'],
            ],
            [
                '{{ `\d` }}',
                ['ECHO_START', 'STRING_LIT_RAW(`\d`)', 'ECHO_END'],
            ],
            [
                '{{ `\` }}',
                ['ECHO_START', 'STRING_LIT_RAW(`\`)', 'ECHO_END'],
            ],
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
                '{{ in }}',
                ['ECHO_START', 'IN', 'ECHO_END'],
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
            [
                '{{ $x matches "/abc/" }}',
                ['ECHO_START', 'DOLLAR_IDENT($x)', 'MATCHES', 'STRING_LIT_Q2("/abc/")', 'ECHO_END'],
            ],

            // Mixing everything together.
            [
                'a{{x_2}}b',
                ['TEXT(a)', 'ECHO_START', 'IDENT(x_2)', 'ECHO_END', 'TEXT(b)'],
            ],
            [
                '{%- if $v > 100000 -%}',
                ['CONTROL_START_TRIM', 'IF', 'DOLLAR_IDENT($v)', 'GT', 'INT_LIT(100000)', 'CONTROL_END_TRIM'],
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
                if ($tok->kind === TokenKind::ERROR) {
                    $this->fail('unexpected error: ' . $lexer->getError());
                    break;
                }
                if ($tok->kind === TokenKind::EOF) {
                    break;
                }
                $kind_string = $tok->kindName();
                if ($tok->hasValue()) {
                    $have[] = "$kind_string(" . $lexer->tokenText($tok) . ')';
                } else {
                    $have[] = $kind_string;
                }
            }
            $this->assertEquals($want, $have);
        }
    }
}
