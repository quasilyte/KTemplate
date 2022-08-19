<?php

namespace KTemplate\Compile;

class ConstFolder {
    /** @var ExprParser */
    private $parser;

    /**
     * @param ExprParser $parser
     */
    function __construct($parser) {
        $this->parser = $parser;
    }

    /**
     * @param Expr $e
     * @return mixed
     */
    public function fold($e) {
        switch ($e->kind) {
        case Expr::INT_LIT:
            return $e->value;
        case Expr::STRING_LIT:
            return $e->value;
        
        case Expr::NEG:
            $arg = self::fold($this->getExprMember($e, 0));
            if (!is_numeric($arg)) {
                return null;
            }
            return -$arg;

        case Expr::CONCAT:
            $lhs = self::fold($this->getExprMember($e, 0));
            if (!is_string($lhs)) {
                return null;
            }
            $rhs = self::fold($this->getExprMember($e, 1));
            if (!is_string($rhs)) {
                return null;
            }
            return $lhs . $rhs;
        case Expr::ADD:
            $lhs = self::fold($this->getExprMember($e, 0));
            if (!is_numeric($lhs)) {
                return null;
            }
            $rhs = self::fold($this->getExprMember($e, 1));
            if (!is_numeric($rhs)) {
                return null;
            }
            return $lhs + $rhs;
        case Expr::SUB:
            $lhs = self::fold($this->getExprMember($e, 0));
            if (!is_numeric($lhs)) {
                return null;
            }
            $rhs = self::fold($this->getExprMember($e, 1));
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
     * @param int $i
     * @return Expr
     */
    private function getExprMember($e, $i) {
        return $this->parser->getExprMember($e, $i);
    }
}
