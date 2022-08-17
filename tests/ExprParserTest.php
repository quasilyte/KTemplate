<?php

use PHPUnit\Framework\TestCase;
use KTemplate\Compile\Lexer;
use KTemplate\Compile\ExprParser;
use KTemplate\Compile\Expr;

class ExprParserTest extends TestCase {
    private static function formatBinaryExpr(ExprParser $p, Expr $e, string $op): string {
        $x = $p->getExprMember($e, 0);
        $y = $p->getExprMember($e, 1);
        return '(' . $op . ' ' . self::formatExpr($p, $x) . ' ' . self::formatExpr($p, $y) . ')';
    }

    private static function formatUnaryExpr(ExprParser $p, Expr $e, string $op): string {
        $x = $p->getExprMember($e, 0);
        return '(' . $op . ' ' . self::formatExpr($p, $x) . ')';
    }

    private static function formatExpr(ExprParser $p, Expr $e): string {
        switch ($e->kind) {
        case Expr::IDENT:
            return (string)$e->value;
        case Expr::TRUE_LIT:
            return 'true';
        case Expr::FALSE_LIT:
            return 'false';

        case Expr::STRING_LIT:
            return '`' . (string)$e->value . '`';

        case Expr::DOT_ACCESS:
            return self::formatBinaryExpr($p, $e, '.');
        case Expr::ADD:
            return self::formatBinaryExpr($p, $e, '+');
        case Expr::SUB:
            return self::formatBinaryExpr($p, $e, '-');
        case Expr::MUL:
            return self::formatBinaryExpr($p, $e, '*');
        case Expr::DIV:
            return self::formatBinaryExpr($p, $e, '/');
        case Expr::CONCAT:
            return self::formatBinaryExpr($p, $e, '~');
        case Expr::AND:
            return self::formatBinaryExpr($p, $e, 'and');
        case Expr::OR:
            return self::formatBinaryExpr($p, $e, 'or');
        case Expr::EQ:
            return self::formatBinaryExpr($p, $e, '==');
        case Expr::NOT_EQ:
            return self::formatBinaryExpr($p, $e, '!=');
        case Expr::LT:
            return self::formatBinaryExpr($p, $e, '<');
        case Expr::LT_EQ:
            return self::formatBinaryExpr($p, $e, '<=');
        case Expr::GT:
            return self::formatBinaryExpr($p, $e, '>');
        case Expr::GT_EQ:
            return self::formatBinaryExpr($p, $e, '>=');

        case Expr::NOT:
            return self::formatUnaryExpr($p, $e, 'not');

        default:
            return '?';
        }
    }

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

            ['not x', '(not x)', 2],
            ['not true', '(not true)', 2],
            ['not not x', '(not (not x))', 3],
            ['not x and not y', '(and (not x) (not y))', 5],
        ];
        $lexer = new Lexer();
        $p = new ExprParser();
        foreach ($tests as $test) {
            [$input, $want_ast, $want_allocs] = $test;
            $lexer->setExprSource('test', (string)$input);
            $e = $p->parseRootExpr($lexer);
            $x = $p->getExprMember($e, 0);
            $have_allocs = $p->getNumAllocated();
            $have_ast = self::formatExpr($p, $e);
            $have = ["input=$input", "allocs=$want_allocs", $want_ast];
            $want = ["input=$input", "allocs=$have_allocs", $have_ast];
            $this->assertEquals($have, $want);
        }
    }
}
