<?php

use PHPUnit\Framework\TestCase;
use KTemplate\Compile\Lexer;
use KTemplate\Compile\ExprParser;
use KTemplate\Compile\Expr;
use KTemplate\Compile\ExprPrinter;

class ExprParserTest extends TestCase {
    public function testParseExpr() {
        $tests = [
            ['true', 'true', 1],
            ['false', 'false', 1],

            ['"abc"', '`abc`', 1],
            ["'abc'", '`abc`', 1],

            ['x + y', '(+ x y)', 3],
            ['x + y + z', '(+ (+ x y) z)', 5],
            ['x + y - z', '(- (+ x y) z)', 5],
            ['x + y * z', '(+ x (* y z))', 5],
            ['x * y + z', '(+ (* x y) z)', 5],
            ['(x + y) * z', '(* (+ x y) z)', 5],
            ['x * (y + z)', '(* x (+ y z))', 5],
            ['x1 * x2 * x3 * x4', '(* (* (* x1 x2) x3) x4)', 7],

            ['x ~ y ~ z', '(~ (~ x y) z)', 5],

            ['x.y', '(. x y)', 3],
            ['x.y.z', '(. (. x y) z)', 5],
            ['(x.y).z', '(. (. x y) z)', 5],
            ['a.b + d.c', '(+ (. a b) (. d c))', 7],

            ['x and y', '(and x y)', 3],
            ['x or y', '(or x y)', 3],
            ['x or y and z', '(or x (and y z))', 5],

            ['x == y and z1 != z2', '(and (== x y) (!= z1 z2))', 7],
            ['x < y or z1 > z2', '(or (< x y) (> z1 z2))', 7],
            ['x >= y or z1 <= z2', '(or (>= x y) (<= z1 z2))', 7],
            ['$x or $y or $x or 1', '(or $x (or $y (or $x 1)))', 7],
            ['$x and $y and $x and 1', '(and $x (and $y (and $x 1)))', 7],
            ['true or (false and f())', '(or true (and false (call f)))', 6],
            ['true or false and f()', '(or true (and false (call f)))', 6],

            ['not x', '(not x)', 2],
            ['not true', '(not true)', 2],
            ['not not x', '(not (not x))', 3],
            ['not x and not y', '(and (not x) (not y))', 5],

            ['f()', '(call f)', 2],
            ['f(1)', '(call f 1)', 5],
            ['f(1, 2)', '(call f 1 2)', 5],
            ['f(1, 2, 3)', '(call f 1 2 3)', 5],
            ['f(g(1, 2), 3, 4)', '(call f (call g 1 2) 3 4)', 9],
            ['f(1, g(2), g(3, 4, 5))', '(call f 1 (call g 2) (call g 3 4 5))', 13],
            ['f1(f2(f3()))', '(call f1 (call f2 (call f3)))', 10],

            ['"a"|strlen', '(| `a` strlen)', 3],
            ['x|y', '(| x y)', 3],
            ['x|y|z', '(| (| x y) z)', 5],
            ['a|b|c|d', '(| (| (| a b) c) d)', 7],
            ['x+1|add1', '(+ x (| 1 add1))', 5],
            ['(x+1)|add1', '(| (+ x 1) add1)', 5],
            ['x|default(1)', '(| x (call default 1))', 7],

            ['$x-1', '(- $x 1)', 3],
            ['1-1', '(- 1 1)', 3],
            ['-$x', '(neg $x)', 2],
            ['-1', '-1', 1],

            ['$x["a"]', '([] $x `a`)', 3],
            ['$x["a"][0]', '([] ([] $x `a`) 0)', 5],

            ['"\x41\x23"', '`A#`', 1],
            ['"\""', '`"`', 1],
            ["'\\''", "`'`", 1],
        ];
        $lexer = new Lexer();
        $p = new ExprParser();
        foreach ($tests as $test) {
            [$input, $want_ast, $want_allocs] = $test;
            $lexer->setExprSource('test', (string)$input);
            $e = $p->parseRootExpr($lexer);
            $x = $p->getExprMember($e, 0);
            $have_allocs = $p->getNumAllocated();
            $have_ast = ExprPrinter::formatExpr($p, $e);
            $want = ["input=$input", "allocs=$want_allocs", $want_ast];
            $have = ["input=$input", "allocs=$have_allocs", $have_ast];
            $this->assertEquals($want, $have);
        }
    }
}
