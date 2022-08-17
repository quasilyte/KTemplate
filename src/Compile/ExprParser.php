<?php

namespace KTemplate\Compile;

class ExprParser {
    /** @var Expr[] */
    private $expr_pool = [];
    private $num_allocated = 0;
    /** @var Lexer */
    private $lexer;
    /** @var Expr */
    private $tmp;

    public function __construct() {
        $this->growPool(10);
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
        $filename = $this->lexer->getFilename();
        $e->kind = Expr::BAD;
        $e->value = "$filename:$line: $msg";
    }

    private function parseExpr(Expr $dst, int $precedence) {
        $left = $dst;
        $lexer = $this->lexer;
        $tok = $lexer->scan();
        switch ($tok->kind) {
        case Token::KEYWORD_TRUE:
            $left->kind = Expr::TRUE_LIT;
            break;
        case Token::KEYWORD_FALSE:
            $left->kind = Expr::FALSE_LIT;
            break;
        case Token::DOLLAR_IDENT:
            $left->kind = Expr::DOLLAR_IDENT;
            $left->value = $lexer->dollarVarName($tok);
            break;
        case Token::IDENT:
            $left->kind = Expr::IDENT;
            $left->value = $lexer->tokenText($tok);
            break;
        case Token::INT_LIT:
            $left->kind = Expr::INT_LIT;
            $left->value = (int)$lexer->tokenText($tok);
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
                $this->parseBinaryExpr($left, Expr::DIV, $right_prec);
                break;
            case Token::TILDE:
                $this->parseBinaryExpr($left, Expr::CONCAT, $right_prec);
                break;
            case Token::KEYWORD_AND:
                $this->parseBinaryExpr($left, Expr::AND, $right_prec);
                break;
            case Token::KEYWORD_OR:
                $this->parseBinaryExpr($left, Expr::OR, $right_prec);
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

    private function unaryPrecedence(int $kind): int {
        switch ($kind) {
        case Token::KEYWORD_NOT:
            return 5;
        case Token::PLUS:
        case Token::MINUS:
            return 10;
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
            return 2;
        case Token::EQ:
        case Token::NOT_EQ:
        case Token::LT:
        case Token::LT_EQ:
        case Token::GT:
        case Token::GT_EQ:
            return 3;
        case Token::PLUS:
        case Token::MINUS:
        case Token::TILDE:
            return 4;
        case Token::STAR:
        case Token::SLASH:
            return 6;
        case Token::DOT:
            return 7;
        default:
            return -1;
        }
    }
}
