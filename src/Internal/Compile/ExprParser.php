<?php

namespace KTemplate\Internal\Compile;

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
        $e->kind = ExprKind::BAD;
        $e->value = ['line' => $line, 'msg' => $msg];
    }

    private function parseExpr(Expr $dst, int $precedence) {
        $left = $dst;
        $lexer = $this->lexer;
        $tok = $lexer->scan();
        switch ($tok->kind) {
        case TokenKind::KEYWORD_TRUE:
            $left->kind = ExprKind::BOOL_LIT;
            $left->value = true;
            break;
        case TokenKind::KEYWORD_FALSE:
            $left->kind = ExprKind::BOOL_LIT;
            $left->value = false;
            break;
        case TokenKind::DOLLAR_IDENT:
            $left->kind = ExprKind::DOLLAR_IDENT;
            $left->value = $lexer->dollarVarName($tok);
            break;
        case TokenKind::IDENT:
            $left->kind = ExprKind::IDENT;
            $left->value = $lexer->tokenText($tok);
            if ($lexer->consume(TokenKind::LPAREN)) {
                $this->parseCallExpr($left);
            }
            break;
        case TokenKind::INT_LIT:
            $left->kind = ExprKind::INT_LIT;
            $left->value = (int)$lexer->tokenText($tok);
            break;
        case TokenKind::FLOAT_LIT:
            $left->kind = ExprKind::FLOAT_LIT;
            $left->value = (float)$lexer->tokenText($tok);
            break;
        case TokenKind::STRING_LIT_RAW:
            $left->kind = ExprKind::STRING_LIT;
            $left->value = $lexer->stringText($tok);
            break;
        case TokenKind::STRING_LIT_Q1:
        case TokenKind::STRING_LIT_Q2:
            // We may enable string interpolation inside DQ strings later.
            // For now, they're behaving identically.
            $left->kind = ExprKind::STRING_LIT;
            $left->value = $this->interpretString($left, $lexer->stringText($tok));
            break;
        case TokenKind::KEYWORD_NULL:
            $left->kind = ExprKind::NULL_LIT;
            break;
        case TokenKind::LPAREN:
            $this->parseExpr($left, 0);
            if (!$lexer->consume(TokenKind::RPAREN)) {
                $this->setError($left, 'missing )');
            }
            break;
        case TokenKind::KEYWORD_NOT:
            $this->parseUnaryExpr($left, ExprKind::NOT, $this->unaryPrecedence(TokenKind::KEYWORD_NOT));
            break;
        case TokenKind::MINUS:
            if ($lexer->peek()->kind === TokenKind::INT_LIT) {
                $tok = $lexer->scan();
                $left->kind = ExprKind::INT_LIT;
                $left->value = -(int)$lexer->tokenText($tok);
                break;
            }
            $this->parseUnaryExpr($left, ExprKind::NEG, $this->unaryPrecedence(TokenKind::MINUS));
            break;
        case TokenKind::ERROR:
            $this->setError($left, $this->lexer->getError());
            break;
        default:
            $this->setError($left, 'unexpected token ' . $tok->prettyKindName());
        }

        while (true) {
            $right_prec = $this->infixPrecedence($lexer->peek());
            if ($precedence >= $right_prec) {
                break;
            }
            $tok = $lexer->scan();
            switch ($tok->kind) {
            case TokenKind::LBRACKET:
                $this->parseIndexExpr($left);
                break;
            case TokenKind::KEYWORD_MATCHES:
                $this->parseBinaryExpr($left, ExprKind::MATCHES, $right_prec);
                break;
            case TokenKind::PIPE:
                $this->parseBinaryExpr($left, ExprKind::FILTER, $right_prec);
                break;
            case TokenKind::DOT:
                $this->parseBinaryExpr($left, ExprKind::DOT_ACCESS, $right_prec);
                break;
            case TokenKind::PLUS:
                $this->parseBinaryExpr($left, ExprKind::ADD, $right_prec);
                break;
            case TokenKind::MINUS:
                $this->parseBinaryExpr($left, ExprKind::SUB, $right_prec);
                break;
            case TokenKind::STAR:
                $this->parseBinaryExpr($left, ExprKind::MUL, $right_prec);
                break;
            case TokenKind::SLASH:
                $this->parseBinaryExpr($left, ExprKind::QUO, $right_prec);
                break;
            case TokenKind::PERCENT:
                $this->parseBinaryExpr($left, ExprKind::MOD, $right_prec);
                break;
            case TokenKind::TILDE:
                $this->parseBinaryExpr($left, ExprKind::CONCAT, $right_prec);
                break;
            case TokenKind::KEYWORD_AND:
                // Parse AND as right-associative to simplify the compilation.
                $this->parseBinaryExpr($left, ExprKind::AND, $right_prec - 1);
                break;
            case TokenKind::KEYWORD_OR:
                // Parse OR as right-associative to simplify the compilation.
                $this->parseBinaryExpr($left, ExprKind::OR, $right_prec - 1);
                break;
            case TokenKind::EQ:
                $this->parseBinaryExpr($left, ExprKind::EQ, $right_prec);
                break;
            case TokenKind::NOT_EQ:
                $this->parseBinaryExpr($left, ExprKind::NOT_EQ, $right_prec);
                break;
            case TokenKind::LT:
                $this->parseBinaryExpr($left, ExprKind::LT, $right_prec);
                break;
            case TokenKind::LT_EQ:
                $this->parseBinaryExpr($left, ExprKind::LT_EQ, $right_prec);
                break;
            case TokenKind::GT:
                $this->parseBinaryExpr($left, ExprKind::GT, $right_prec);
                break;
            case TokenKind::GT_EQ:
                $this->parseBinaryExpr($left, ExprKind::GT_EQ, $right_prec);
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
        $left->kind = ExprKind::CALL;
        $left->value = 0; // Number of arguments
        if ($this->lexer->consume(TokenKind::RPAREN)) {
            // A small optimization: we can allocate exactly 1 member
            // for empty argument list.
            $this->allocateExprMembers($left, 1);
            $this->getExprMember($left, 0)->assign($this->tmp);
            return;
        }
        $max_call_args = 3;
        $this->allocateExprMembers($left, 1 + $max_call_args);
        $this->getExprMember($left, 0)->assign($this->tmp);
        $this->parseExpr($this->getExprMember($left, 1), 0);
        $left->value++;
        while (true) {
            if (!$this->lexer->consume(TokenKind::COMMA)) {
                break;
            }
            if ($left->value >= $max_call_args) {
                $this->setError($left, "call expr is limited to $max_call_args arguments");
                return;
            }
            $this->parseExpr($this->getExprMember($left, (int)$left->value + 1), 0);
            $left->value++;
        }
        if (!$this->lexer->consume(TokenKind::RPAREN)) {
            $tok = $this->lexer->peek();
            $this->setError($left, 'expected ) to close a call expr argument list, found ' . $tok->prettyKindName());
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
        $left->kind = ExprKind::INDEX;
        $this->allocateExprMembers($left, 2);
        $this->getExprMember($left, 0)->assign($this->tmp);
        $this->parseExpr($this->getExprMember($left, 1), 0);
        if (!$this->lexer->consume(TokenKind::RBRACKET)) {
            $tok = $this->lexer->peek();
            $this->setError($left, 'expected ] to close indexing expr, found ' . $tok->prettyKindName());
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
        case TokenKind::KEYWORD_NOT:
            return 6;
        case TokenKind::PLUS:
        case TokenKind::MINUS:
            return 11;
        default:
            return -1;
        }
    }

    private function infixPrecedence(Token $tok): int {
        // For the reference:
        // https://github.com/twigphp/Twig/blob/760341fa8c41c764a5a819a31deb3c5ad66befb1/src/Extension/CoreExtension.php#L261
        switch ($tok->kind) {
        case TokenKind::KEYWORD_OR:
            return 1;
        case TokenKind::KEYWORD_AND:
            return 3;
        case TokenKind::EQ:
        case TokenKind::NOT_EQ:
        case TokenKind::LT:
        case TokenKind::LT_EQ:
        case TokenKind::GT:
        case TokenKind::GT_EQ:
        case TokenKind::KEYWORD_MATCHES:
            return 4;
        case TokenKind::PLUS:
        case TokenKind::MINUS:
        case TokenKind::TILDE:
            return 5;
        case TokenKind::STAR:
        case TokenKind::SLASH:
        case TokenKind::PERCENT:
            return 7;
        case TokenKind::LBRACKET:
            return 9;
        case TokenKind::PIPE:
            return 13;
        case TokenKind::DOT:
            return 14;
        default:
            return -1;
        }
    }
}
