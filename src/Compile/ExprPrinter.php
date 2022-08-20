<?php

namespace KTemplate\Compile;

class ExprPrinter {
    public static function formatExpr(ExprParser $p, Expr $e): string {
        switch ($e->kind) {
        case Expr::IDENT:
            return (string)$e->value;
        case Expr::DOLLAR_IDENT:
            return '$' . (string)$e->value;

        case Expr::BOOL_LIT:
            return $e->value ? 'true' : 'false';

        case Expr::STRING_LIT:
            return '`' . (string)$e->value . '`';
        case Expr::INT_LIT:
            return (string)$e->value;

        case Expr::BAD:
            return '(bad `' . $e->value . '`)';

        case Expr::CALL:
            $num_args = (int)$e->value;
            $fn = self::formatExpr($p, $p->getExprMember($e, 0));
            if ($num_args === 0) {
                return "(call $fn)";
            }
            $args = [];
            for ($i = 0; $i < $num_args; $i++) {
                $args[] = self::formatExpr($p, $p->getExprMember($e, $i + 1));
            }
            return '(call ' . $fn . ' ' . implode(' ', $args) . ')';

        case Expr::FILTER:
            return self::formatBinaryExpr($p, $e, '|');
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

    private static function formatBinaryExpr(ExprParser $p, Expr $e, string $op): string {
        $x = $p->getExprMember($e, 0);
        $y = $p->getExprMember($e, 1);
        return '(' . $op . ' ' . self::formatExpr($p, $x) . ' ' . self::formatExpr($p, $y) . ')';
    }

    private static function formatUnaryExpr(ExprParser $p, Expr $e, string $op): string {
        $x = $p->getExprMember($e, 0);
        return '(' . $op . ' ' . self::formatExpr($p, $x) . ')';
    }
}