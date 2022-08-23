<?php

namespace KTemplate\Compile;

use KTemplate\Env;
use KTemplate\Template;
use KTemplate\Op;
use KTemplate\OpInfo;
use KTemplate\Internal\Assert;

class Compiler {
    /** @var Lexer */
    private $lexer;

    /** @var ExprParser */
    private $parser;

    /** @var Template */
    private $result;

    /** @var ConstFolder */
    private $const_folder;

    /** @var Frame */
    private $frame;

    /** @var Env */
    private $env;

    /** @var int[] */
    private $addr_by_label_id = [];
    /** @var int */
    private $label_seq = 0;

    /** @var int[] */
    private $string_values;
    /** @var int[] */
    private $int_values;
    /** @var int[] */
    private $float_values;

    public function __construct() {
        $this->lexer = new Lexer();
        $this->parser = new ExprParser();
        $this->const_folder = new ConstFolder($this->parser);
        $this->frame = new Frame();
    }

    /**
     * @param Env $env
     * @param string $filename
     * @param string $source
     * @return Template
     */
    public function compile($env, $filename, $source) {
        $this->reset($env, $filename, $source);
        /** @var ?\Throwable $exception */
        $exception = null;

        $this->frame->enterScope();
        try {
            while (true) {
                $tok = $this->lexer->scan();
                if ($tok->kind === Token::EOF) {
                    break;
                }
                $this->compileToken($tok);
            }
            $this->emit(Op::RETURN);
            $this->finalizeTemplate();
            return $this->result;
        } catch (\Throwable $e) {
            $exception = $e;
        }
        $this->frame->leaveScope();

        $this->finish();
        if ($exception) {
            throw $exception;
        }
        return $this->result;
    }

    private function compileToken(Token $tok) {
        switch ($tok->kind) {
        case Token::COMMENT:
            return; // Just skip the comment
        case Token::TEXT:
            $this->compileOutputStringConst($this->lexer->tokenText($tok), !$this->env->escape_config->escape_text);
            return;
        case Token::ECHO_START:
            $this->compileEcho();
            return;
        case Token::CONTROL_START:
            $this->compileControl();
            return;
        case Token::ERROR:
            $this->failToken($tok, $this->lexer->getError());
            return;
        }

        $this->failToken($tok, 'unexpected top-level token: ' . Token::prettyKindString($tok->kind));
    }

    private function compileControl() {
        $tok = $this->lexer->scan();
        switch ($tok->kind) {
        case Token::KEYWORD_IF:
            $this->compileIf();
            return;
        case Token::KEYWORD_LET:
            $this->compileLet();
            return;
        case Token::KEYWORD_SET:
            $this->compileSet();
            return;
        case Token::KEYWORD_FOR:
            $this->compileFor();
            return;
        }

        if ($tok->kind === Token::IDENT) {
            $this->failToken($tok, 'unexpected control token: ' . $this->lexer->tokenText($tok));
        }
        $this->failToken($tok, 'unexpected control token: ' . Token::prettyKindString($tok->kind));
    }

    private function compileFor() {
        $tok = $this->lexer->scan();
        if ($tok->kind !== Token::DOLLAR_IDENT) {
            $this->failToken($tok, 'for loop var names should be identifiers with leading $, found ' . Token::prettyKindString($tok->kind));
        }
        $val_var_name = '';
        $key_var_name = $this->lexer->dollarVarName($tok);
        if ($this->lexer->consume(Token::COMMA)) {
            $tok = $this->lexer->scan();
            if ($tok->kind !== Token::DOLLAR_IDENT) {
                $this->failToken($tok, 'for loop var names should be identifiers with leading $, found ' . Token::prettyKindString($tok->kind));
            }
            $val_var_name = $this->lexer->dollarVarName($tok);
        } else {
            $val_var_name = $key_var_name;
            $key_var_name = '';
        }
        $this->expectToken(Token::KEYWORD_IN);
        $seq_expr = $this->parser->parseRootExpr($this->lexer);
        $this->expectToken(Token::CONTROL_END);

        $this->frame->enterScope();
        $this->compileRootExpr(0, $seq_expr);
        $key_slot = 0;
        if ($key_var_name) {
            $key_slot = $this->frame->allocVarSlot($key_var_name);
        }
        $val_slot = $this->frame->allocVarSlot($val_var_name);
        $this->compileForBody($key_slot, $val_slot);
        $this->frame->leaveScope();
    }

    /**
     * @param int $key_slot
     * @param int $val_slot
     */
    private function compileForBody($key_slot, $val_slot) {
        $label_else = $this->newLabel();
        $label_end = $this->newLabel();
        if ($key_slot) {
            $this->emit((Op::FOR_KEY_VAL) | ($label_else << 8) | ($key_slot << 24) | ($val_slot << 32));
        } else {
            $this->emit((Op::FOR_VAL) | ($label_else << 8) | ($val_slot << 24));
        }

        $has_else = false;
        while (true) {
            $tok = $this->lexer->scan();
            if ($tok->kind === Token::CONTROL_START) {
                if ($this->lexer->consume(Token::KEYWORD_ENDFOR)) {
                    $this->expectToken(Token::CONTROL_END);
                    if (!$has_else) {
                        $this->emit(Op::RETURN);
                    }
                    $this->bindLabel($label_end);
                    $this->tryBindLabel($label_else);
                    break;
                }
                if (!$has_else && $this->lexer->consume(Token::KEYWORD_ELSE)) {
                    $has_else = true;
                    $this->expectToken(Token::CONTROL_END);
                    $this->emit(Op::RETURN);
                    $this->bindLabel($label_else);
                    $this->emitCondJump(Op::JUMP_TRUTHY, 0, $label_end);
                    continue;
                }
            }
            $this->compileToken($tok);
        }
    }

    private function compileSet() {
        $tok = $this->lexer->scan();
        if ($tok->kind !== Token::DOLLAR_IDENT) {
            $this->failToken($tok, 'set names should be identifiers with leading $, found ' . Token::prettyKindString($tok->kind));
        }
        $var_name = $this->lexer->dollarVarName($tok);
        $var_slot = $this->frame->lookupLocal($var_name);
        if ($var_slot === -1) {
            $this->failToken($tok, "assigning to undefined local var $var_name");
        }
        $this->expectToken(Token::ASSIGN);
        $e = $this->parser->parseRootExpr($this->lexer);
        $this->compileRootExpr($var_slot, $e);
        $this->expectToken(Token::CONTROL_END);
    }

    private function compileLet() {
        $tok = $this->lexer->scan();
        if ($tok->kind !== Token::DOLLAR_IDENT) {
            $this->failToken($tok, 'let names should be identifiers with leading $, found ' . Token::prettyKindString($tok->kind));
        }
        $var_name = $this->lexer->dollarVarName($tok);
        if ($this->frame->lookupLocalInCurrentScope($var_name) !== -1) {
            $this->failToken($tok, "variable $var_name is already declared in this scope");
        }
        $var_slot = $this->frame->allocVarSlot($var_name);
        $this->expectToken(Token::ASSIGN);
        $e = $this->parser->parseRootExpr($this->lexer);
        $this->compileRootExpr($var_slot, $e);
        $this->expectToken(Token::CONTROL_END);
    }

    private function compileIf() {
        $e = $this->parser->parseRootExpr($this->lexer);
        $this->compileRootExpr(0, $e);
        $this->expectToken(Token::CONTROL_END);

        $this->frame->enterScope();
        $this->compileIfBody();
        $this->frame->leaveScope();
    }

    private function compileIfBody() {
        $label_next = $this->newLabel();
        $label_end = $this->newLabel();
        $this->emitCondJump(Op::JUMP_FALSY, 0, $label_next);
        while (true) {
            $tok = $this->lexer->scan();
            if ($tok->kind === Token::CONTROL_START) {
                if ($this->lexer->consume(Token::KEYWORD_ENDIF)) {
                    $this->expectToken(Token::CONTROL_END);
                    $this->tryBindLabel($label_next);
                    $this->bindLabel($label_end);
                    break;
                }
                if ($this->lexer->consume(Token::KEYWORD_ELSE)) {
                    $this->emitJump($label_end);
                    $this->expectToken(Token::CONTROL_END);
                    $this->bindLabel($label_next);
                    continue;
                }
                if ($this->lexer->consume(Token::KEYWORD_ELSEIF)) {
                    $this->emitJump($label_end);
                    $this->bindLabel($label_next);
                    $this->compileIf();
                    $this->bindLabel($label_end);
                    return;
                }
            }
            $this->compileToken($tok);
        }
    }

    private function compileEcho() {
        $e = $this->parser->parseRootExpr($this->lexer);
        if (!$this->tryCompileDirectOutput($e)) {
            $type = $this->compileRootExpr(0, $e);
            $op = $this->needsEscaping($type) ? Op::OUTPUT_SLOT0 : Op::OUTPUT_SAFE_SLOT0;
            $this->emit($op);
        }
        $this->expectToken(Token::ECHO_END);
    }

    /**
     * @param string $v
     * @param bool $safe
     */
    private function compileOutputStringConst($v, $safe) {
        $string_id = $this->internString($v);
        if ($this->env->escape_func && !$safe) {
            $this->emit1(Op::OUTPUT_STRING_CONST, $string_id);
        } else {
            $this->emit1(Op::OUTPUT_SAFE_STRING_CONST, $string_id);
        }
    }

    /**
     * @param int $v
     */
    private function compileOutputIntConst($v) {
        $int_id = $this->internInt($v);
        $this->emit1(Op::OUTPUT_SAFE_INT_CONST, $int_id);
    }

    /**
     * @param Expr $e
     * @return bool
     */
    private function tryCompileDirectOutput($e) {
        if ($this->tryCompileConstexprEcho($e)) {
            return true;
        }
        switch ($e->kind) {
        case Expr::DOLLAR_IDENT:
            $var_slot = $this->lookupLocalVar($e);
            $op = $this->needsEscaping(Types::UNKNOWN) ? Op::OUTPUT : Op::OUTPUT_SAFE;
            $this->emit1($op, $var_slot);
            return true;
        case Expr::IDENT:
            $cache_slot_info = $this->frame->getCacheSlotInfo((string)$e->value, '', '');
            $cache_slot = $cache_slot_info & 0xff;
            $key_offset = ($cache_slot_info >> 8) & 0xff;
            $escape_bit = $this->needsEscaping(Types::UNKNOWN) ? 1 : 0;
            $this->emit3(Op::OUTPUT_EXTDATA_1, $cache_slot, $key_offset, $escape_bit);
            return true;
        case Expr::DOT_ACCESS:
            [$p1, $p2, $p3] = $this->decodeDotAccess($e);
            if ($p1 === '') {
                $this->failExpr($e, 'dot access expression is too complex');
            }
            $cache_slot_info = $this->frame->getCacheSlotInfo($p1, $p2, $p3);
            $cache_slot = $cache_slot_info & 0xff;
            $key_offset = ($cache_slot_info >> 8) & 0xff;
            $escape_bit = $this->needsEscaping(Types::UNKNOWN) ? 1 : 0;
            $op = $p3 === '' ? Op::OUTPUT_EXTDATA_2 : Op::OUTPUT_EXTDATA_3;
            $this->emit3($op, $cache_slot, $key_offset, $escape_bit);
            return true;
        }
        return false;
    }

    /**
     * @param Expr $e
     * @return tuple(string,string,string)
     */
    private function decodeDotAccess($e) {
        $lhs = $this->parser->getExprMember($e, 0);
        $rhs = $this->parser->getExprMember($e, 1);
        if ($rhs->kind !== Expr::IDENT) {
            return tuple('', '', '');
        }
        if ($lhs->kind === Expr::IDENT) {
            return tuple((string)$lhs->value, (string)$rhs->value, '');
        }
        if ($lhs->kind !== Expr::DOT_ACCESS) {
            return tuple('', '', '');
        }
        $lhs0 = $this->parser->getExprMember($lhs, 0);
        $lhs1 = $this->parser->getExprMember($lhs, 1);
        if ($lhs0->kind !== Expr::IDENT || $lhs1->kind !== Expr::IDENT) {
            return tuple('', '', '');
        }
        return tuple((string)$lhs0->value, (string)$lhs1->value, (string)$rhs->value);
    }

    /**
     * @param Expr $e
     * @return bool
     */
    private function tryCompileConstexprEcho($e) {
        $const_value = $this->const_folder->fold($e);
        if ($const_value !== null) {
            if (is_int($const_value)) {
                $this->compileOutputIntConst((int)$const_value);
            } else if (is_string($const_value)) {
                $this->compileOutputStringConst((string)$const_value, !$this->env->escape_config->escape_const_expr);
            } else {
                $this->failExpr($e, 'unexpected value type: ' . gettype($const_value));
            }
            return true;
        }
        return false;
    }

    /**
     * @param int $dst
     * @param Expr $e
     * @return int
     */
    private function compileRootExpr($dst, $e) {
        $this->frame->enterTempBlock();
        $result_type = $this->compileExpr($dst, $e);
        $this->frame->leaveTempBlock();
        return $result_type;
    }

    /**
     * @param Expr $e
     * @return int
     */
    private function compileTempExpr($e) {
        if ($e->kind === Expr::DOLLAR_IDENT) {
            return $this->lookupLocalVar($e);
        }
        $temp = $this->frame->allocTempSlot();
        $this->compileExpr($temp, $e);
        return $temp;
    }

    /**
     * @param int $dst
     * @param string $value
     */
    private function compileStringConst($dst, $value) {
        $this->emit2dst(Op::LOAD_STRING_CONST, $dst, $this->internString($value));
    }

    /**
     * @param int $dst
     * @param int $value
     */
    private function compileIntConst($dst, $value) {
        $this->emit2dst(Op::LOAD_INT_CONST, $dst, $this->internInt($value));
    }

    /**
     * @param int $dst
     * @param float $value
     */
    private function compileFloatConst($dst, $value) {
        $this->emit2dst(Op::LOAD_FLOAT_CONST, $dst, $this->internFloat($value));
    }

    /**
     * @param int $type
     * @param bool $is_const
     * @return bool
     */
    private function needsEscaping($type, $is_const = false) {
        if ($this->env->escape_func === null) {
            return false;
        }
        if ($is_const) {
            return $this->env->escape_config->escape_const_expr;
        }
        switch ($type) {
        case Types::BOOL:
        case Types::NULL;
        case Types::SAFE_STRING:
        case Types::INT:
        case Types::FLOAT:
        case Types::NUMERIC:
            return false;
        default:
            return $this->env->escape_config->escape_expr;
        }
    }

    /**
     * @param int $dst
     * @param int $type
     */
    private function maybeEscape($dst, $type) {
        
    }

    /**
     * @param Expr $e
     * @return bool
     */
    private function isAdditiveBinaryExpr($e) {
        switch ($e->kind) {
        case Expr::CONCAT:
        case Expr::ADD:
        case Expr::MUL:
            return true;
        default:
            return false;
        }
    }

    /**
     * @param int $dst
     * @param Expr $e
     * @param int $type
     * @return int
     */
    private function compileExpr($dst, $e, $type = Types::UNKNOWN) {
        // First try to constant-fold the expression.
        $const_value = $this->const_folder->fold($e);
        if (is_int($const_value)) {
            $this->compileIntConst($dst, (int)$const_value);
            return Types::INT;
        }
        if (is_string($const_value)) {
            $this->compileStringConst($dst, (string)$const_value);
            return Types::STRING;
        }
        // Binary expressions are parsed like this:
        // `x + 1 + 2` => `(+ (+ x 1) 2)`
        // The const folder won't be able to fold this expression.
        // To fix that, we try to see whether we can do a partial
        // const folding.
        // If we fold `(+ 1 2)` separately, we'll get `(+ x 3)`.
        // This approach is not perfect as it won't fold the expression entirely.
        // We may want to do something more generic in the future.
        // Also, checking for the same $kind is usually excessive as it
        // limits the folding to the same operation.
        if ($this->isAdditiveBinaryExpr($e)) {
            $lhs = $this->parser->getExprMember($e, 0);
            if ($lhs->kind === $e->kind) {
                $lhs_lhs = $this->parser->getExprMember($lhs, 0);
                $lhs_rhs = $this->parser->getExprMember($lhs, 1);
                $rhs = $this->parser->getExprMember($e, 1);
                $const_value = $this->const_folder->foldBinaryExpr($e->kind, $lhs_rhs, $rhs);
                if ($const_value !== null) {
                    $lhs_lhs_slot = $this->compileTempExpr($lhs_lhs);
                    $rhs_slot = $this->frame->allocTempSlot();
                    $op = $this->opByBinaryExprKind($e->kind);
                    if (is_string($const_value)) {
                        $this->compileStringConst($rhs_slot, (string)$const_value);
                    } else if (is_int($const_value)) {
                        $this->compileIntConst($rhs_slot, (int)$const_value);
                    } else {
                        Assert::unreachable('unexpected const-folded value type');
                    }
                    $this->compileBinaryExpr($dst, $op, $lhs_lhs_slot, $rhs_slot);
                    return Types::UNKNOWN;
                }
            }
        }

        switch ($e->kind) {
        case Expr::BAD:
            $this->fail((int)$e->value['line'], (string)$e->value['msg']);
            return Types::UNKNOWN;

        case Expr::DOLLAR_IDENT:
            $this->compileTypedMoveNode($dst, $e, $type);
            return $type;

        case Expr::IDENT:
            $cache_slot_info = $this->frame->getCacheSlotInfo((string)$e->value, '', '');
            $cache_slot = $cache_slot_info & 0xff;
            $key_offset = ($cache_slot_info >> 8) & 0xff;
            $this->emit3dst(Op::LOAD_EXTDATA_1, $dst, $cache_slot, $key_offset);
            return Types::UNKNOWN;

        case Expr::DOT_ACCESS:
            [$p1, $p2, $p3] = $this->decodeDotAccess($e);
            if ($p1 === '') {
                $this->failExpr($e, 'dot access expression is too complex');
            }
            $slot_info = $this->frame->getCacheSlotInfo($p1, $p2, $p3);
            $cache_slot = $slot_info & 0xff;
            $key_offset = ($slot_info >> 8) & 0xff;
            $op = $p3 === '' ? Op::LOAD_EXTDATA_2 : Op::LOAD_EXTDATA_3;
            $this->emit3dst($op, $dst, $cache_slot, $key_offset);
            return Types::UNKNOWN;

        case Expr::INDEX:
            $this->compileIndex($dst, $e);
            return Types::UNKNOWN;

        case Expr::OR:
            $this->compileOr($dst, $e);
            return Types::BOOL;
        case Expr::AND:
            $this->compileAnd($dst, $e);
            return Types::BOOL;

        case Expr::NOT:
            $this->compileUnaryExpr($dst, Op::NOT, $e);
            return Types::BOOL;
        case Expr::NEG:
            $this->compileUnaryExpr($dst, Op::NEG, $e);
            return Types::UNKNOWN;

        case Expr::GT:
            $this->compileReversedBinaryExprNode($dst, Op::LT, $e);
            return Types::BOOL;
        case Expr::GT_EQ:
            $this->compileReversedBinaryExprNode($dst, Op::LT_EQ, $e);
            return Types::BOOL;

        case Expr::CONCAT:
        case Expr::EQ:
        case Expr::LT:
        case Expr::LT_EQ:
        case Expr::NOT_EQ:
        case Expr::ADD:
        case Expr::SUB:
        case Expr::MUL:
        case Expr::QUO:
        case Expr::MOD:
            return $this->compileBinaryExprNode($dst, $e);

        case Expr::NULL_LIT:
            $this->emit1dst(Op::LOAD_NULL, $dst);
            return Types::NULL;

        case Expr::STRING_LIT:
            $this->compileStringConst($dst, (string)$e->value);
            return Types::STRING;

        case Expr::BOOL_LIT:
            $this->emit2dst(Op::LOAD_BOOL, $dst, (int)$e->value);
            return Types::BOOL;

        case Expr::INT_LIT:
            $this->compileIntConst($dst, (int)$e->value);
            return Types::INT;

        case Expr::FLOAT_LIT:
            $this->compileFloatConst($dst, (float)$e->value);
            return Types::FLOAT;

        case Expr::CALL:
            $this->compileCall($dst, $e);
            return Types::UNKNOWN;

        case Expr::FILTER:
            if ($this->parser->getExprMember($e, 1)->kind === Expr::IDENT) {
                return $this->compileFilter1($dst, $e);
            }
            if ($this->parser->getExprMember($e, 1)->kind === Expr::CALL) {
                return $this->compileFilter2($dst, $e);
            }
            $this->failExpr($e, 'compile expr: invalid filter, expected a call or ident');
        }
    
        $this->failExpr($e, "compile expr: unexpected $e->kind");
        return Types::UNKNOWN;
    }

    /**
     * @param int $dst
     * @param Expr $e
     */
    private function compileIndex($dst, $e) {
        $seq_expr = $this->parser->getExprMember($e, 0);
        $seq_slot = $this->compileTempExpr($seq_expr);
        $key_expr = $this->parser->getExprMember($e, 1);
        if ($key_expr->kind === Expr::STRING_LIT) {
            $key_id = $this->internString((string)$key_expr->value);
            $this->emit3dst(Op::INDEX_STRING_KEY, $dst, $seq_slot, $key_id);
            return;
        }
        if ($key_expr->kind === Expr::INT_LIT) {
            $key_id = $this->internInt((int)$key_expr->value);
            $this->emit3dst(Op::INDEX_INT_KEY, $dst, $seq_slot, $key_id);
            return;
        }
        $key_slot = $this->compileTempExpr($key_expr);
        $this->emit3dst(Op::INDEX, $dst, $seq_slot, $key_slot);
    }

    /**
     * @param int $dst
     * @param int $src
     * @param int $type
     */
    private function compileTypedMove($dst, $src, $type) {
        $op = Op::MOVE;
        switch ($type) {
        case Types::BOOL:
            $op = Op::MOVE_BOOL;
            break;
        }
        if ($op === Op::MOVE && $dst === $src) {
            return; // Do nothing
        }
        $this->emit2dst($op, $dst, $src);
    }

    /**
     * @param int $dst
     * @param Expr $e
     * @param int $type
     */
    private function compileTypedMoveNode($dst, $e, $type) {
        $src_slot = $this->compileTempExpr($e);
        $this->compileTypedMove($dst, $src_slot, $type);
        
    }

    private function compileAndOr($jump_op, $dst, $e) {
        $lhs = $this->parser->getExprMember($e, 0);
        $rhs = $this->parser->getExprMember($e, 1);

        if ($lhs->kind === Expr::DOLLAR_IDENT && $rhs->kind === Expr::DOLLAR_IDENT) {
            $this->compileBinaryExprNode($dst, $e);
            return;
        }

        $label_end = $this->newLabel();
        $lhs_type = $this->compileExpr($dst, $lhs, Types::BOOL);
        $this->emitCondJump($jump_op, $dst, $label_end);
        $rhs_type = $this->compileExpr($dst, $rhs, Types::BOOL);
        if ($lhs_type !== Types::BOOL) {
            $this->bindLabel($label_end);
        }
        if ($lhs_type !== Types::BOOL || $rhs_type !== Types::BOOL) {
            $this->emit1dst(Op::CONV_BOOL, $dst);
        }
        if ($lhs_type === Types::BOOL) {
            $this->bindLabel($label_end);
        }
    }

    /**
     * @param int $dst
     * @param Expr $e
     */
    private function compileOr($dst, $e) {
        $this->compileAndOr(Op::JUMP_TRUTHY, $dst, $e);
    }

    /**
     * @param int $dst
     * @param Expr $e
     */
    private function compileAnd($dst, $e) {
        $this->compileAndOr(Op::JUMP_FALSY, $dst, $e);
    }

    /**
     * @param int $dst
     * @param Expr $e
     */
    private function compileCall($dst, $e) {
        // Parser should take care of that already.
        // We have opcodes for CALL0, CALL1, CALL2, CALL3 and that's it.
        Assert::true($e->value >= 0 && $e->value <= 3, 'unexpected call args count');

        $fn_ident = $this->parser->getExprMember($e, 0);
        $func_id = $this->env->getFunctionID((string)$fn_ident->value, (int)$e->value);
        if ($func_id === -1) {
            $this->failExpr($fn_ident, "$fn_ident->value function is not defined");
        }

        switch ($e->value) {
        case 0:
            if ($dst === 0) {
                $this->emitCall0(Op::CALL_SLOT0_FUNC0, $func_id);
            } else {
                $this->emitCall1(Op::CALL_FUNC0, $dst, $func_id);
            }
            break;
        case 1:
            $arg1_slot = $this->compileTempExpr($this->parser->getExprMember($e, 1));
            if ($dst === 0) {
                $this->emitCall1(Op::CALL_SLOT0_FUNC1, $arg1_slot, $func_id);
            } else {
                $this->emitCall2(Op::CALL_FUNC1, $dst, $arg1_slot, $func_id);
            }
            break;
        case 2:
            $arg1_slot = $this->compileTempExpr($this->parser->getExprMember($e, 1));
            $arg2_slot = $this->compileTempExpr($this->parser->getExprMember($e, 2));
            if ($dst === 0) {
                $this->emitCall2(Op::CALL_SLOT0_FUNC2, $arg1_slot, $arg2_slot, $func_id);
            } else {
                $this->emitCall3(Op::CALL_FUNC2, $dst, $arg1_slot, $arg2_slot, $func_id);
            }
            break;
        case 3:
            $arg1_slot = $this->compileTempExpr($this->parser->getExprMember($e, 1));
            $arg2_slot = $this->compileTempExpr($this->parser->getExprMember($e, 2));
            $arg3_slot = $this->compileTempExpr($this->parser->getExprMember($e, 3));
            if ($dst === 0) {
                $this->emitCall3(Op::CALL_SLOT0_FUNC3, $arg1_slot, $arg2_slot, $arg3_slot, $func_id);
            } else {
                $this->emitCall4(Op::CALL_FUNC3, $dst, $arg1_slot, $arg2_slot, $arg3_slot, $func_id);
            }
            break;
        }
    }

    /**
     * @param int $dst
     * @param Expr $e
     */
    private function compileFilter2($dst, $e) {
        $rhs = $this->parser->getExprMember($e, 1);
        if ($rhs->value > 1) {
            $this->failExpr($e, 'too many arguments for a filter');
        }
        $arg1_slot = $this->compileTempExpr($this->parser->getExprMember($e, 0));
        $filter_name = (string)$this->parser->getExprMember($rhs, 0)->value;
        $filter_id = $this->env->getFilterID($filter_name, 2);
        $arg2_expr = $this->parser->getExprMember($rhs, 1);
        if ($filter_id === -1 && ($filter_name === 'escape' || $filter_name === 'e')) {
            $arg2_const_value = $this->const_folder->fold($arg2_expr);
            if (!is_string($arg2_const_value)) {
                $this->failExpr($arg2_expr, 'escape filter expects a const expr string argument');
            }
            $strategy = $this->internString((string)$arg2_const_value);
            $this->emit3dst(Op::ESCAPE_FILTER2, $dst, $arg1_slot, $strategy);
            return Types::SAFE_STRING;
        }
        $arg2_slot = $this->compileTempExpr($arg2_expr);
        if ($filter_id === -1) {
            if ($filter_name === 'default') {
                $this->emit3dst(Op::DEFAULT_FILTER, $dst, $arg1_slot, $arg2_slot);
                return Types::UNKNOWN;
            }
            $this->failExpr($this->parser->getExprMember($rhs, 0), "$filter_name filter is not defined");
        }
        if ($dst === 0) {
            $this->emitCall2(Op::CALL_SLOT0_FILTER2, $arg1_slot, $arg2_slot, $filter_id);
        } else {
            $this->emitCall3(Op::CALL_FILTER2, $dst, $arg1_slot, $arg2_slot, $filter_id);
        }
        return Types::UNKNOWN;
    }

    /**
     * @param int $dst
     * @param Expr $e
     */
    private function compileFilter1($dst, $e) {
        $rhs = $this->parser->getExprMember($e, 1);
        $filter_id = $this->env->getFilterID((string)$rhs->value, 1);
        if ($filter_id === -1 && $rhs->value === 'raw') {
            $this->compileExpr($dst, $this->parser->getExprMember($e, 0));
            return Types::SAFE_STRING;
        }
        $arg1_slot = $this->compileTempExpr($this->parser->getExprMember($e, 0));
        if ($filter_id === -1) {
            if ($rhs->value === 'length') {
                $this->emit2dst(Op::LENGTH_FILTER, $dst, $arg1_slot);
                return Types::INT;
            }
            if ($rhs->value === 'escape' || $rhs->value === 'e') {
                $this->emit2dst(Op::ESCAPE_FILTER1, $dst, $arg1_slot);
                return Types::SAFE_STRING;
            }
            $this->failExpr($rhs, "$rhs->value filter is not defined");
        }
        if ($dst === 0) {
            $this->emitCall1(Op::CALL_SLOT0_FILTER1, $arg1_slot, $filter_id);
        } else {
            $this->emitCall2(Op::CALL_FILTER1, $dst, $arg1_slot, $filter_id);
        }
        return Types::UNKNOWN;
    }

    /**
     * @param int $dst
     * @param int $op
     * @param int $lhs_slot
     * @param int $rhs_slot
     */
    private function compileBinaryExpr($dst, $op, $lhs_slot, $rhs_slot) {
        if ($dst === 0) {
            $this->emit2($op + 1, $lhs_slot, $rhs_slot);
            return;
        }
        $this->emit3($op, $dst, $lhs_slot, $rhs_slot);
    }

    /**
     * @param int $kind
     * @return int
     */
    private function opByBinaryExprKind($kind) {
        switch ($kind) {
        case Expr::OR:
            return Op::OR;
        case Expr::AND:
            return Op::AND;
        case Expr::CONCAT:
            return Op::CONCAT;
        case Expr::EQ:
            return Op::EQ;
        case Expr::LT:
            return Op::LT;
        case Expr::LT_EQ:
            return Op::LT_EQ;
        case Expr::NOT_EQ:
            return Op::NOT_EQ;
        case Expr::ADD:
            return Op::ADD;
        case Expr::SUB:
            return Op::SUB;
        case Expr::MUL:
            return Op::MUL;
        case Expr::QUO:
            return Op::QUO;
        case Expr::MOD:
            return Op::MOD;

        default:
            Assert::unreachable("can't map expr kind to bytecode op");
            return 0;
        }
    }

    /**
     * @param int $dst
     * @param Expr $e
     * @return int
     */
    private function compileBinaryExprNode($dst, $e) {
        $lhs_slot = $this->compileTempExpr($this->parser->getExprMember($e, 0));
        $rhs_slot = $this->compileTempExpr($this->parser->getExprMember($e, 1));
        $op = $this->opByBinaryExprKind($e->kind);
        $this->compileBinaryExpr($dst, $op, $lhs_slot, $rhs_slot);
        return Op::opcodeResultType($op);
    }

    /**
     * @param int $dst
     * @param int $op
     * @param Expr $e
     */
    private function compileReversedBinaryExprNode($dst, $op, $e) {
        // Evaluate the arguments normally, but emit opcode slot
        // args in the reversed order.
        $lhs_slot = $this->compileTempExpr($this->parser->getExprMember($e, 0));
        $rhs_slot = $this->compileTempExpr($this->parser->getExprMember($e, 1));
        $this->compileBinaryExpr($dst, $op, $rhs_slot, $lhs_slot);
    }

    /**
     * @param int $dst
     * @param int $op
     * @param Expr $e
     */
    private function compileUnaryExpr($dst, $op, $e) {
        $arg = $this->compileTempExpr($this->parser->getExprMember($e, 0));
        $this->emit2dst($op, $dst, $arg);
    }

    /**
     * finish is executed when the compilation is finished.
     * It tries to minimize the compiler object memory footprint
     * by releasing the memory that won't be needed anymore.
     */
    private function finish() {
        $this->env = null;
        $this->string_values = [];
        $this->int_values = [];
        $this->float_values = [];
    }

    /**
     * @param Env $env
     * @param string $filename
     * @param string $source
     */
    private function reset($env, $filename, $source) {
        $this->env = $env;
        $this->result = new Template();
        $this->lexer->setSource($filename, $source);
        $this->frame->reset($this->result);
        $this->string_values = [];
        $this->int_values = [];
        $this->float_values = [];
        $this->addr_by_label_id = [];
        $this->label_seq = 0;
    }

    /**
     * @param int $label_id - 16bit
     */
    private function emitJump($label_id) {
        $this->result->code[] = Op::JUMP| ($label_id << 8);
    }

    /**
     * @param int $op
     * @param int $cond_slot
     * @param int $label_id - 16bit
     */
    private function emitCondJump($op, $cond_slot, $label_id) {
        if ($cond_slot === 0) {
            $this->result->code[] = ($op+1) | ($label_id << 8);
            return;
        }
        $this->result->code[] = ($op) | ($label_id << 8) | ($cond_slot << 24);
    }

    /**
     * @param int $op
     * @param int $dst
     */
    private function emit1dst($op, $dst) {
        if ($dst === 0) {
            $this->emit($op+1);
            return;
        }
        $this->emit1($op, $dst);
    }

    /**
     * @param int $op
     * @param int $dst
     * @param int $arg1
     */
    private function emit2dst($op, $dst, $arg1) {
        if ($dst === 0) {
            $this->emit1($op+1, $arg1);
            return;
        }
        $this->emit2($op, $dst, $arg1);
    }

    /**
     * @param int $op
     * @param int $dst
     * @param int $arg1
     * @param int $arg2
     */
    private function emit3dst($op, $dst, $arg1, $arg2) {
        if ($dst === 0) {
            $this->emit2($op+1, $arg1, $arg2);
            return;
        }
        $this->emit3($op, $dst, $arg1, $arg2);
    }

    /**
     * @param int $op
     * @param int $func_id - 16bit
     */
    private function emitCall0($op, $func_id) {
        $this->result->code[] = $op | ($func_id << 8);
    }

    /**
     * @param int $op
     * @param int $arg1
     * @param int $func_id - 16bit
     */
    private function emitCall1($op, $arg1, $func_id) {
        $this->result->code[] = $op | ($arg1 << 8) | ($func_id << 16);
    }

    /**
     * @param int $op
     * @param int $arg1
     * @param int $arg2
     * @param int $func_id - 16bit
     */
    private function emitCall2($op, $arg1, $arg2, $func_id) {
        $this->result->code[] = $op | ($arg1 << 8) | ($arg2 << 16) | ($func_id << 24);
    }

    /**
     * @param int $op
     * @param int $arg1
     * @param int $arg2
     * @param int $arg3
     * @param int $func_id - 16bit
     */
    private function emitCall3($op, $arg1, $arg2, $arg3, $func_id) {
        $this->result->code[] = $op | ($arg1 << 8) | ($arg2 << 16) | ($arg3 << 24) | ($func_id << 32);
    }

    /**
     * @param int $op
     * @param int $arg1
     * @param int $arg2
     * @param int $arg3
     * @param int $arg4
     * @param int $func_id - 16bit
     */
    private function emitCall4($op, $arg1, $arg2, $arg3, $arg4, $func_id) {
        $this->result->code[] = $op | ($arg1 << 8) | ($arg2 << 16) | ($arg3 << 24) | ($arg4 << 32) | ($func_id << 40);
    }

    /**
     * @param int $opdata
     */
    private function emit($opdata) {
        $this->result->code[] = $opdata;
    }

    /**
     * @param int $op
     * @param int $arg1
     */
    private function emit1($op, $arg1) {
        $this->result->code[] = $op | ($arg1 << 8);
    }

    /**
     * @param int $op
     * @param int $arg1
     * @param int $arg2
     */
    private function emit2($op, $arg1, $arg2) {
        $this->result->code[] = $op | ($arg1 << 8) | ($arg2 << 16);
    }

    /**
     * @param int $op
     * @param int $arg1
     * @param int $arg2
     * @param int $arg3
     */
    private function emit3($op, $arg1, $arg2, $arg3) {
        $this->result->code[] = $op | ($arg1 << 8) | ($arg2 << 16) | ($arg3 << 24);
    }

    /**
     * @param string $v
     * @return int
     */
    private function internString($v) {
        if (array_key_exists($v, $this->string_values)) {
            return $this->string_values[$v];
        }
        $id = count($this->result->string_values);
        $this->result->string_values[] = $v;
        $this->string_values[$v] = $id;
        return $id;
    }

    /**
     * @param int $v
     * @return int
     */
    private function internInt($v) {
        if (array_key_exists($v, $this->int_values)) {
            return $this->int_values[$v];
        }
        $id = count($this->result->int_values);
        $this->result->int_values[] = $v;
        $this->int_values[$v] = $id;
        return $id;
    }

    /**
     * @param float $v
     * @return int
     */
    private function internFloat($v) {
        if (array_key_exists($v, $this->float_values)) {
            return $this->float_values[$v];
        }
        $id = count($this->result->float_values);
        $this->result->float_values[] = $v;
        $this->float_values[$v] = $id;
        return $id;
    }

    /**
     * @param int $kind
     */
    private function expectToken($kind) {
        $tok = $this->lexer->scan();
        if ($tok->kind === $kind) {
            return;
        }
        $this->failToken($tok, 'expected ' . Token::prettyKindString($kind) . ', found ' . Token::prettyKindString($tok->kind));
    }

    /**
     * @param Expr $e
     * @param string $message
     */
    private function failExpr($e, $message) {
        $this->fail($this->lexer->getLineByPos($this->lexer->getPos()), $message);
    }

    /**
     * @param Token $tok
     * @param string $message
     */
    private function failToken($tok, $message) {
        $this->fail($this->lexer->getLineByPos($tok->pos_from), $message);
    }

    /**
     * @param int $line
     * @param string $message
     */
    private function fail($line, $message) {
        $e = new CompilationException($message);
        $e->source_line = $line;
        $e->source_filename = $this->lexer->getFilename();
        throw $e;
    }

    private function finalizeTemplate() {
        $this->linkJumps();
        $this->reallocateSlots();
        $this->result->frame_size = 1 + count($this->frame->cache_slots) + $this->frame->num_locals;
    }

    private function reallocateSlots() {
        $num_cache_slots = count($this->frame->cache_slots);
        if ($num_cache_slots === 0) {
            // Nothing to do: all slots are OK as they are.
            return;
        }
        foreach ($this->result->code as $pc => $opdata) {
            $op = $opdata & 0xff;
            $op_flags = Op::opcodeFlags($op);
            if (($op_flags & OpInfo::FLAG_HAS_SLOT_ARG) === 0) {
                // Skip non-interesting opcodes to make compilation faster.
                continue;
            }
            $args = Op::$args[$op];
            $arg_shift = 8;
            $new_opdata = $opdata;
            foreach ($args as $a) {
                if ($a == OpInfo::ARG_SLOT) {
                    $orig_slot = ($opdata >> $arg_shift) & 0xff;
                    $new_slot = $orig_slot + $num_cache_slots;
                    $arg_mask = 0xff << $arg_shift;
                    $new_opdata = ($new_opdata & (~$arg_mask)) | ($new_slot << $arg_shift);
                }
                $arg_shift += OpInfo::argSize($a) * 8;
            }
            $this->result->code[$pc] = $new_opdata;
        }
    }

    private function linkJumps() {
        $mask = 0xffff << 8;
        foreach ($this->result->code as $pc => $opdata) {
            $op = $opdata & 0xff;
            if (!OpInfo::isJump($op)) {
                continue;
            }
            $label_id = ($opdata >> 8) & 0xffff;
            $jump_target = $this->addr_by_label_id[$label_id];
            $jump_offset = ($jump_target - $pc) - 1;
            $this->result->code[$pc] = ($opdata & (~$mask)) | ($jump_offset << 8);
        }
    }

    /**
     * @return int
     */
    private function newLabel() {
        $id = $this->label_seq;
        $this->label_seq++;
        return $id;
    }

    /**
     * @param int $label_id
     */
    private function bindLabel($label_id) {
        if (array_key_exists($label_id, $this->addr_by_label_id)) {
            $this->fail(-1, "internal error: binding label with id=$label_id twice");
        }
        $pc = count($this->result->code);
        $this->addr_by_label_id[$label_id] = $pc;
    }

    /**
     * @param int $label_id
     */
    private function tryBindLabel($label_id) {
        if (array_key_exists($label_id, $this->addr_by_label_id)) {
            return;
        }
        $this->bindLabel($label_id);
    }

    /**
     * @param Expr $e
     * @return int
     */
    private function lookupLocalVar($e) {
        $slot = $this->frame->lookupLocal((string)$e->value);
        if ($slot === -1) {
            $this->failExpr($e, "referenced undefined local var $e->value");
        }
        return $slot;
    }
}
