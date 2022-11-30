<?php

namespace KTemplate\Internal\Compile;

class ExprPrinter {
    /**
     * @param ExprParser $p
     * @param Expr $e
     * @return string
     */
    public static function formatExpr($p, $e) {
        switch ($e->kind) {
        case ExprKind::IDENT:
        case ExprKind::INT_LIT:
            return (string)$e->value;
        case ExprKind::DOLLAR_IDENT:
            return '$' . (string)$e->value;

        case ExprKind::BOOL_LIT:
            return $e->value ? 'true' : 'false';

        case ExprKind::STRING_LIT:
            return '`' . (string)$e->value . '`';

        case ExprKind::BAD:
            return '(bad `' . $e->value['msg'] . '`)';

        case ExprKind::CALL:
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

        case ExprKind::FILTER:
            return self::formatBinaryExpr($p, $e, '|');
        case ExprKind::DOT_ACCESS:
            return self::formatBinaryExpr($p, $e, '.');
        case ExprKind::ADD:
            return self::formatBinaryExpr($p, $e, '+');
        case ExprKind::SUB:
            return self::formatBinaryExpr($p, $e, '-');
        case ExprKind::MUL:
            return self::formatBinaryExpr($p, $e, '*');
        case ExprKind::QUO:
            return self::formatBinaryExpr($p, $e, '/');
        case ExprKind::MOD:
            return self::formatBinaryExpr($p, $e, '%');
        case ExprKind::CONCAT:
            return self::formatBinaryExpr($p, $e, '~');
        case ExprKind::AND:
            return self::formatBinaryExpr($p, $e, 'and');
        case ExprKind::OR:
            return self::formatBinaryExpr($p, $e, 'or');
        case ExprKind::EQ:
            return self::formatBinaryExpr($p, $e, '==');
        case ExprKind::NOT_EQ:
            return self::formatBinaryExpr($p, $e, '!=');
        case ExprKind::LT:
            return self::formatBinaryExpr($p, $e, '<');
        case ExprKind::LT_EQ:
            return self::formatBinaryExpr($p, $e, '<=');
        case ExprKind::GT:
            return self::formatBinaryExpr($p, $e, '>');
        case ExprKind::GT_EQ:
            return self::formatBinaryExpr($p, $e, '>=');
        case ExprKind::MATCHES:
            return self::formatBinaryExpr($p, $e, 'matches');

        case ExprKind::NOT:
            return self::formatUnaryExpr($p, $e, 'not');
        case ExprKind::NEG:
            return self::formatUnaryExpr($p, $e, 'neg');

        case ExprKind::INDEX:
            return self::formatBinaryExpr($p, $e, '[]');

        default:
            return (string)$e->kind;
        }
    }

    /**
     * @param ExprParser $p
     * @param Expr $e
     * @param string $op
     * @return string
     */
    private static function formatBinaryExpr($p, $e, $op) {
        $x = $p->getExprMember($e, 0);
        $y = $p->getExprMember($e, 1);
        return '(' . $op . ' ' . self::formatExpr($p, $x) . ' ' . self::formatExpr($p, $y) . ')';
    }

    /**
     * @param ExprParser $p
     * @param Expr $e
     * @param string $op
     * @return string
     */
    private static function formatUnaryExpr($p, $e, $op) {
        $x = $p->getExprMember($e, 0);
        return '(' . $op . ' ' . self::formatExpr($p, $x) . ')';
    }
}