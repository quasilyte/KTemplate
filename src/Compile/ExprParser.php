<?php

namespace KTemplate\Compile;

use KTemplate\Internal\Strings;

class ExprParser {
    /** @var Expr[] */
    private $expr_pool = [];
    private $num_allocated = 0;
    /** @var Lexer */
    private $lexer;
    /** @var Expr */
    private $tmp;

    public function __construct() {
        $this->growPool(16);
        $this->tmp = new Expr();
    }

    public function parseRootExpr(Lexer $lexer): Expr {
        $this->num_allocated = 0;
        $this->lexer = $lexer;
        $e = $this->newExpr(0);
        $this->parseExpr($e, 0);
        return $e;
    }

    public function getNumAllocated() {
        return $this->num_allocated;
    }

    public function getExprMember(Expr $e, int $i): Expr {
        return $this->expr_pool[$e->members_offset + $i];
    }

    private function newExprCopy(Expr $e) {
        $cloned = $this->newExpr(0);
        $cloned->assign($e);
    }

    private function newExpr(int $kind, $value = null): Expr {
        if ($this->num_allocated == count($this->expr_pool)) {
            $this->growPool(10);
        }
        $e = $this->expr_pool[$this->num_allocated];
        $e->kind = $kind;
        $e->value = $value;
        $this->num_allocated++;
        return $e;
    }

    private function allocateExprMembers(Expr $e, int $num_members) {
        if ($this->num_allocated + $num_members >= count($this->expr_pool)) {
            $this->growPool($num_members + 10);
        }
        $e->members_offset = $this->num_allocated;
        $this->num_allocated += $num_members;
    }

    private function growPool(int $extra) {
        while ($extra > 0) {
            $this->expr_pool[] = new Expr();
            $extra--;
        }
    }

    private function setError(Expr $e, string $msg) {
        $pos = $this->lexer->getPos();
        $line = $this->lexer->getLineByPos($pos);
        $e->kind = Expr::BAD;
        $e->value = ['line' => $line, 'msg' => $msg];
    }

    private function parseExpr(Expr $dst, int $precedence) {
        $left = $dst;
        $lexer = $this->lexer;
        $tok = $lexer->scan();
        switch ($tok->kind) {
        case Token::KEYWORD_TRUE:
            $left->kind = Expr::BOOL_LIT;
            $left->value = true;
            break;
        case Token::KEYWORD_FALSE:
            $left->kind = Expr::BOOL_LIT;
            $left->value = false;
            break;
        case Token::DOLLAR_IDENT:
            $left->kind = Expr::DOLLAR_IDENT;
            $left->value = $lexer->dollarVarName($tok);
            break;
        case Token::IDENT:
            $left->kind = Expr::IDENT;
            $left->value = $lexer->tokenText($tok);
            if ($lexer->consume(Token::LPAREN)) {
                $this->parseCallExpr($left);
            }
            break;
        case Token::INT_LIT:
            $left->kind = Expr::INT_LIT;
            $left->value = (int)$lexer->tokenText($tok);
            break;
        case Token::STRING_LIT_Q1:
        case Token::STRING_LIT_Q2:
            // We may enable string interpolation inside DQ strings later.
            // For now, they're behaving identically.
            $left->kind = Expr::STRING_LIT;
            $left->value = $this->interpretString($left, $lexer->stringText($tok));
            break;
        case Token::KEYWORD_NULL:
            $left->kind = Expr::NULL_LIT;
            break;
        case Token::LPAREN:
            $this->parseExpr($left, 0);
            if (!$lexer->consume(Token::RPAREN)) {
                $this->setError($left, 'missing )');
            }
            break;
        case Token::KEYWORD_NOT:
            $this->parseUnaryExpr($left, Expr::NOT, $this->unaryPrecedence(Token::KEYWORD_NOT));
            break;
        case Token::MINUS:
            if ($lexer->peek()->kind === Token::INT_LIT) {
                $tok = $lexer->scan();
                $left->kind = Expr::INT_LIT;
                $left->value = -(int)$lexer->tokenText($tok);
                break;
            }
            $this->parseUnaryExpr($left, Expr::NEG, $this->unaryPrecedence(Token::MINUS));
            break;
        case Token::ERROR:
            $this->setError($left, $this->lexer->getError());
            break;
        default:
            $this->setError($left, 'unexpected token ' . Token::prettyKindString($tok->kind));
        }

        while (true) {
            $right_prec = $this->infixPrecedence($lexer->peek());
            if ($precedence >= $right_prec) {
                break;
            }
            $tok = $lexer->scan();
            switch ($tok->kind) {
            case Token::LBRACKET:
                $this->parseIndexExpr($left);
                break;
            case Token::PIPE:
                $this->parseBinaryExpr($left, Expr::FILTER, $right_prec);
                break;
            case Token::DOT:
                $this->parseBinaryExpr($left, Expr::DOT_ACCESS, $right_prec);
                break;
            case Token::PLUS:
                $this->parseBinaryExpr($left, Expr::ADD, $right_prec);
                break;
            case Token::MINUS:
                $this->parseBinaryExpr($left, Expr::SUB, $right_prec);
                break;
            case Token::STAR:
                $this->parseBinaryExpr($left, Expr::MUL, $right_prec);
                break;
            case Token::SLASH:
                $this->parseBinaryExpr($left, Expr::QUO, $right_prec);
                break;
            case Token::PERCENT:
                $this->parseBinaryExpr($left, Expr::MOD, $right_prec);
                break;
            case Token::TILDE:
                $this->parseBinaryExpr($left, Expr::CONCAT, $right_prec);
                break;
            case Token::KEYWORD_AND:
                // Parse AND as right-associative to simplify the compilation.
                $this->parseBinaryExpr($left, Expr::AND, $right_prec - 1);
                break;
            case Token::KEYWORD_OR:
                // Parse OR as right-associative to simplify the compilation.
                $this->parseBinaryExpr($left, Expr::OR, $right_prec - 1);
                break;
            case Token::EQ:
                $this->parseBinaryExpr($left, Expr::EQ, $right_prec);
                break;
            case Token::NOT_EQ:
                $this->parseBinaryExpr($left, Expr::NOT_EQ, $right_prec);
                break;
            case Token::LT:
                $this->parseBinaryExpr($left, Expr::LT, $right_prec);
                break;
            case Token::LT_EQ:
                $this->parseBinaryExpr($left, Expr::LT_EQ, $right_prec);
                break;
            case Token::GT:
                $this->parseBinaryExpr($left, Expr::GT, $right_prec);
                break;
            case Token::GT_EQ:
                $this->parseBinaryExpr($left, Expr::GT_EQ, $right_prec);
                break;
            }
        }

        return $left;
    }

    /**
     * @param Expr $left
     */
    private function parseCallExpr($left) {
        $this->tmp->assign($left);
        $left->kind = Expr::CALL;
        $left->value = 0; // Number of arguments
        if ($this->lexer->consume(Token::RPAREN)) {
            // A small optimization: we can allocate exactly 1 member
            // for empty argument list.
            $this->allocateExprMembers($left, 1);
            $this->getExprMember($left, 0);
            $this->getExprMember($left, 0)->assign($this->tmp);
            return;
        }
        $max_call_args = 3;
        $this->allocateExprMembers($left, 1 + $max_call_args);
        $this->getExprMember($left, 0)->assign($this->tmp);
        $this->parseExpr($this->getExprMember($left, 1), 0);
        $left->value++;
        while (true) {
            if (!$this->lexer->consume(Token::COMMA)) {
                break;
            }
            if ($left->value >= $max_call_args) {
                $this->setError($left, "call expr is limited to $max_call_args arguments");
                return;
            }
            $this->parseExpr($this->getExprMember($left, (int)$left->value + 1), 0);
            $left->value++;
        }
        if (!$this->lexer->consume(Token::RPAREN)) {
            $tok = $this->lexer->peek();
            $this->setError($left, 'expected ) to close a call expr argument list, found ' . Token::prettyKindString($tok->kind));
        }
    }

    private function parseUnaryExpr(Expr $left, int $kind, int $prec) {
        $left->kind = $kind;
        $this->allocateExprMembers($left, 1);
        $x = $this->getExprMember($left, 0);
        $this->parseExpr($x, $prec);
    }

    private function parseBinaryExpr(Expr $left, int $kind, int $prec) {
        $this->tmp->assign($left);
        $left->kind = $kind;
        $this->allocateExprMembers($left, 2);
        $x = $this->getExprMember($left, 0);
        $x->assign($this->tmp);
        $y = $this->getExprMember($left, 1);
        $this->parseExpr($y, $prec);
    }

    /**
     * @param Expr $left
     */
    private function parseIndexExpr($left) {
        $this->tmp->assign($left);
        $left->kind = Expr::INDEX;
        $this->allocateExprMembers($left, 2);
        $this->getExprMember($left, 0)->assign($this->tmp);
        $this->parseExpr($this->getExprMember($left, 1), 0);
        if (!$this->lexer->consume(Token::RBRACKET)) {
            $tok = $this->lexer->peek();
            $this->setError($left, 'expected ] to close indexing expr, found ' . Token::prettyKindString($tok->kind));
        }
    }

    /**
     * @param Expr $e
     * @param string $raw_string
     * @return string
     */
    private function interpretString($e, $raw_string) {
        if (!Strings::contains($raw_string, '\\')) {
            // Fast path: nothing to replace.
            return $raw_string;
        }
        return stripcslashes($raw_string);
    }

    private function unaryPrecedence(int $kind): int {
        switch ($kind) {
        case Token::KEYWORD_NOT:
            return 6;
        case Token::PLUS:
        case Token::MINUS:
            return 11;
        default:
            return -1;
        }
    }

    private function infixPrecedence(Token $tok): int {
        // For the reference:
        // https://github.com/twigphp/Twig/blob/760341fa8c41c764a5a819a31deb3c5ad66befb1/src/Extension/CoreExtension.php#L261
        switch ($tok->kind) {
        case Token::KEYWORD_OR:
            return 1;
        case Token::KEYWORD_AND:
            return 3;
        case Token::EQ:
        case Token::NOT_EQ:
        case Token::LT:
        case Token::LT_EQ:
        case Token::GT:
        case Token::GT_EQ:
            return 4;
        case Token::PLUS:
        case Token::MINUS:
        case Token::TILDE:
            return 5;
        case Token::STAR:
        case Token::SLASH:
        case Token::PERCENT:
            return 7;
        case Token::LBRACKET:
            return 9;
        case Token::PIPE:
            return 13;
        case Token::DOT:
            return 14;
        default:
            return -1;
        }
    }
}
