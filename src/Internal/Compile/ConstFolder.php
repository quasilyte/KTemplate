<?php

namespace KTemplate\Internal\Compile;

class ConstFolder {
    /** @var ExprParser */
    private $parser;

    /**
     * @param ExprParser $parser
     */
    public function __construct($parser) {
        $this->parser = $parser;
    }

    /**
     * @param Expr $x
     * @param Expr $y
     * @return mixed
     */
    public function foldBinaryExpr($kind, $x, $y) {
        switch ($kind) {
        case ExprKind::CONCAT:
            $lhs = $this->fold($x);
            if (!is_string($lhs)) {
                return null;
            }
            $rhs = $this->fold($y);
            if (!is_string($rhs)) {
                return null;
            }
            return $lhs . $rhs;
        case ExprKind::MUL:
            $lhs = $this->fold($x);
            if (!is_numeric($lhs)) {
                return null;
            }
            $rhs = $this->fold($y);
            if (!is_numeric($rhs)) {
                return null;
            }
            return $lhs * $rhs;
        case ExprKind::QUO:
            $lhs = $this->fold($x);
            if (!is_numeric($lhs)) {
                return null;
            }
            $rhs = $this->fold($y);
            if (!is_numeric($rhs)) {
                return null;
            }
            return $lhs / $rhs;
        case ExprKind::ADD:
            $lhs = $this->fold($x);
            if (!is_numeric($lhs)) {
                return null;
            }
            $rhs = $this->fold($y);
            if (!is_numeric($rhs)) {
                return null;
            }
            return $lhs + $rhs;
        case ExprKind::SUB:
            $lhs = $this->fold($x);
            if (!is_numeric($lhs)) {
                return null;
            }
            $rhs = $this->fold($y);
            if (!is_numeric($rhs)) {
                return null;
            }
            return $lhs - $rhs;

        default:
            return null;
        }
    }

    /**
     * @param Expr $e
     * @return mixed
     */
    public function fold($e) {
        switch ($e->kind) {
        case ExprKind::INT_LIT:
        case ExprKind::STRING_LIT:
            return $e->value;
        
        case ExprKind::NEG:
            $arg = $this->fold($this->getExprMember($e, 0));
            if (!is_numeric($arg)) {
                return null;
            }
            return -$arg;

        case ExprKind::CONCAT:
        case ExprKind::ADD:
        case ExprKind::SUB:
        case ExprKind::MUL:
        case ExprKind::QUO:
            return $this->foldBinaryExprNode($e);

        default:
            return null;
        }
    }

    /**
     * @param Expr $e
     * @return mixed
     */
    private function foldBinaryExprNode($e) {
        $lhs = $this->getExprMember($e, 0);
        $rhs = $this->getExprMember($e, 1);
        return $this->foldBinaryExpr($e->kind, $lhs, $rhs);
    }

    /**
     * @param Expr $e
     * @param int $i
     * @return Expr
     */
    private function getExprMember($e, $i) {
        return $this->parser->getExprMember($e, $i);
    }
}
