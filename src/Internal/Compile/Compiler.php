<?php

namespace KTemplate\Internal\Compile;

use KTemplate\Template;
use KTemplate\CompilationException;
use KTemplate\Internal\Env;
use KTemplate\Internal\Op;
use KTemplate\Internal\OpInfo;
use KTemplate\Internal\Assert;
use KTemplate\Internal\Strings;
use KTemplate\Internal\Arrays;

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
    private $string_value_map;
    /** @var string[] */
    private $string_value_list;
    /** @var int[] */
    private $int_values;

    /** @var bool */
    private $parsing_header = true;

    /** @var bool */
    private $trim_left = false;

    /**
     * [$pc] => tuple($template_load_path, $arg_name)
     * @var tuple(string, string)[]
     */
    private $template_arg_deps = [];
    /** @var string */
    private $current_template_path = '';
    /** @var string */
    private $current_template_arg = '';

    /** @var int */
    private $output_merge_seq = 0;
    /** @var int */
    private $prev_output_pc = -1;
    /** @var string */
    private $prev_output_string = '';

    /** @var string */
    private $tmp_output_tag = '';

    /** @var Expr[] */
    private $tmp_expr_array = [null, null, null, null, null, null, null, null, null, null];
    private $tmp_expr_array_size = 0;

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

        /** @var Template $result */
        $result = null;

        try {
            $this->frame->enterScope();
            while (true) {
                $tok = $this->lexer->scan();
                if ($tok->kind === TokenKind::EOF) {
                    break;
                }
                $this->compileToken($tok);
            }
            $this->emit(Op::RETURN);
            $this->frame->leaveScope();
            $result = $this->finalizeTemplate();
        } catch (\Throwable $e) {
            $exception = $e;
        }

        $this->finish();
        if ($exception) {
            throw $exception;
        }
        return $result;
    }

    /**
     * @param Token $tok
     */
    private function compileToken($tok) {
        switch ($tok->kind) {
        case TokenKind::COMMENT:
            return; // Just skip the comment
        case TokenKind::TEXT:
            $this->compileText($tok);
            return;
        case TokenKind::ECHO_START:
        case TokenKind::ECHO_START_TRIM:
            $this->compileEcho();
            return;
        case TokenKind::CONTROL_START:
        case TokenKind::CONTROL_START_TRIM:
            $this->compileControl();
            return;
        case TokenKind::ERROR:
            $this->failToken($tok, $this->lexer->getError());
            return;
        }

        $this->failToken($tok, 'unexpected top-level token: ' . $tok->prettyKindName());
    }

    /**
     * @param Token $tok
     */
    private function compileText($tok) {
        $text = $this->lexer->tokenText($tok);

        $next_tok = $this->lexer->peek();
        $trim_right = false;
        switch ($next_tok->kind) {
        case TokenKind::CONTROL_START_TRIM:
        case TokenKind::ECHO_START_TRIM:
            $trim_right = true;
        }

        if ($this->trim_left && $trim_right) {
            $text = trim($text);
        } else if ($this->trim_left) {
            $text = ltrim($text);
        } else if ($trim_right) {
            $text = rtrim($text);
        }

        $this->compileOutputStringConst($text, !$this->env->ctx->auto_escape_text);

        $this->trim_left = false; // Not strictly needed, but anyway
    }

    private function compileControl() {
        $tok = $this->lexer->scan();
        $is_header_token = false;
        // Preserve for the error handling
        $tok_kind = $tok->kind;
        $tok_pos = $tok->pos_from;
        switch ($tok->kind) {
        case TokenKind::KEYWORD_PARAM:
            $is_header_token = true;
            $this->compileParam();
            break;
        case TokenKind::KEYWORD_IF:
            $this->compileIf();
            break;
        case TokenKind::KEYWORD_LET:
            $this->compileLet();
            break;
        case TokenKind::KEYWORD_SET:
            $this->compileSet();
            break;
        case TokenKind::KEYWORD_FOR:
            $this->compileFor();
            break;
        case TokenKind::KEYWORD_INCLUDE:
            $this->compileInclude();
            break;
        default:
            if ($tok->kind === TokenKind::IDENT) {
                $this->failToken($tok, 'unexpected control token: ' . $this->lexer->tokenText($tok));
            }
            $this->failToken($tok, 'unexpected control token: ' . $tok->prettyKindName());
        }

        if (!$this->parsing_header && $is_header_token) {
            $message = TokenKind::prettyName($tok_kind) . ' can only be used in the beginning of template';
            $this->fail($this->lexer->getLineByPos($tok_pos), $message);
        }
        $this->parsing_header = $is_header_token;
    }

    private function compileInclude() {
        $e = $this->parser->parseRootExpr($this->lexer);
        $path_value = $this->const_folder->fold($e);
        if (!is_string($path_value)) {
            $this->failExpr($e, 'include expects a const expr string argument');
        }
        if ($this->current_template_path) {
            $this->failExpr($e, "attempted to include $path_value while including $this->current_template_path");
        }
        $this->current_template_path = (string)$path_value;
        $this->frame->enterTemplateCall();
        $this->expectEndToken(TokenKind::CONTROL_END);
        $this->emit1(Op::PREPARE_TEMPLATE, $this->internString($this->current_template_path));
        while (true) {
            $tok = $this->lexer->scan();
            if ($tok->kind === TokenKind::TEXT) {
                if (!Strings::isWhitespaceOnly($this->lexer->tokenText($tok))) {
                    $this->failToken($tok, 'include block can only contain args and whitespace, found text');
                }
                continue;
            }
            if (self::isControlStartTok($tok->kind)) {
                if ($this->lexer->consume(TokenKind::KEYWORD_ARG)) {
                    $this->compileTemplateArg();
                    continue;
                }
                if ($this->lexer->consume(TokenKind::KEYWORD_END)) {
                    $this->expectEndToken(TokenKind::CONTROL_END);
                    $this->emit(Op::INCLUDE_TEMPLATE);
                    break;
                }
            }
            $this->failToken($tok, 'include block can only contain args and whitespace');
        }
        $this->current_template_path = '';
        $this->frame->leaveTemplateCall();
    }

    private function compileFor() {
        $tok = $this->lexer->scan();
        if ($tok->kind !== TokenKind::DOLLAR_IDENT) {
            $this->failToken($tok, 'for loop var names should be identifiers with leading $, found ' . $tok->prettyKindName());
        }
        $val_var_name = '';
        $key_var_name = $this->lexer->dollarVarName($tok);
        if ($this->lexer->consume(TokenKind::COMMA)) {
            $tok = $this->lexer->scan();
            if ($tok->kind !== TokenKind::DOLLAR_IDENT) {
                $this->failToken($tok, 'for loop var names should be identifiers with leading $, found ' . $tok->prettyKindName());
            }
            $val_var_name = $this->lexer->dollarVarName($tok);
        } else {
            $val_var_name = $key_var_name;
            $key_var_name = '';
        }
        $this->expectToken(TokenKind::KEYWORD_IN);
        $seq_expr = $this->parser->parseRootExpr($this->lexer);
        $this->expectEndToken(TokenKind::CONTROL_END);

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
            if (self::isControlStartTok($tok->kind)) {
                if ($this->lexer->consume(TokenKind::KEYWORD_END)) {
                    $this->expectEndToken(TokenKind::CONTROL_END);
                    if (!$has_else) {
                        $this->emit(Op::RETURN);
                    }
                    $this->bindLabel($label_end);
                    $this->tryBindLabel($label_else);
                    break;
                }
                if (!$has_else && $this->lexer->consume(TokenKind::KEYWORD_ELSE)) {
                    $has_else = true;
                    $this->expectEndToken(TokenKind::CONTROL_END);
                    $this->emit(Op::RETURN);
                    $this->bindLabel($label_else);
                    $this->emitCondJump(Op::JUMP_TRUTHY, 0, $label_end);
                    continue;
                }
            }
            $this->compileToken($tok);
        }
    }

    /**
     * @param int $dst
     * @param string $tag
     */
    private function compileBlockAssign($dst, $tag) {
        $tok = $this->lexer->scan();
        if ($tok->kind === TokenKind::CONTROL_END_TRIM) {
            $this->trim_left = true;
        } else if ($tok->kind === TokenKind::CONTROL_END) {
            $this->trim_left = false;
        } else {
            $this->failToken($tok, "expected = or %} or -%}, found " . $tok->prettyKindName());
        }
        $this->frame->enterScope();
        if ($this->tmp_output_tag) {
            $this->failToken($tok, "unsupported block-assign $tag inside $this->tmp_output_tag");
        }
        $this->tmp_output_tag = $tag;
        $this->emit(Op::START_TMP_OUTPUT);
        while (true) {
            $tok = $this->lexer->scan();
            if (self::isControlStartTok($tok->kind) && $this->lexer->consume(TokenKind::KEYWORD_END)) {
                $this->expectEndToken(TokenKind::CONTROL_END);
                break;
            }
            $this->compileToken($tok);
        }
        $this->emit1dst(Op::FINISH_TMP_OUTPUT, $dst);
        $this->tmp_output_tag = '';
        $this->frame->leaveScope();
    }

    private function compileSet() {
        $tok = $this->lexer->scan();
        if ($tok->kind !== TokenKind::DOLLAR_IDENT) {
            $this->failToken($tok, 'set names should be identifiers with leading $, found ' . $tok->prettyKindName());
        }
        $var_name = $this->lexer->dollarVarName($tok);
        $var_slot = $this->frame->lookupLocal($var_name);
        if ($var_slot === -1) {
            $this->failToken($tok, "assigning to undefined local var $var_name");
        }
        if ($this->lexer->consume(TokenKind::ASSIGN)) {
            $e = $this->parser->parseRootExpr($this->lexer);
            $this->compileRootExpr($var_slot, $e);
            $this->expectEndToken(TokenKind::CONTROL_END);
            return;
        }
        $this->compileBlockAssign($var_slot, 'set');
    }

    private function compileLet() {
        $tok = $this->lexer->scan();
        if ($tok->kind !== TokenKind::DOLLAR_IDENT) {
            $this->failToken($tok, 'let names should be identifiers with leading $, found ' . $tok->prettyKindName());
        }
        $var_name = $this->lexer->dollarVarName($tok);
        if ($this->frame->lookupLocalInCurrentScope($var_name) !== -1) {
            $this->failToken($tok, "variable $var_name is already declared in this scope");
        }
        $var_slot = $this->frame->allocVarSlot($var_name);
        if ($this->lexer->consume(TokenKind::ASSIGN)) {
            $e = $this->parser->parseRootExpr($this->lexer);
            $this->compileRootExpr($var_slot, $e);
            $this->expectEndToken(TokenKind::CONTROL_END);
            return;
        }
        $this->compileBlockAssign($var_slot, 'let');
    }

    private function compileParam() {
        $tok = $this->lexer->scan();
        if ($tok->kind !== TokenKind::DOLLAR_IDENT) {
            $this->failToken($tok, 'param names should be identifiers with leading $, found ' . $tok->prettyKindName());
        }
        $var_name = $this->lexer->dollarVarName($tok);
        if ($this->frame->lookupLocalInCurrentScope($var_name) !== -1) {
            $this->failToken($tok, "can't declare $var_name param: name is already in use");
        }
        $var_slot = $this->frame->allocVarSlot($var_name);
        if ($this->lexer->consume(TokenKind::ASSIGN)) {
            $e = $this->parser->parseRootExpr($this->lexer);
            if ($e->kind === ExprKind::NULL_LIT) {
                $this->failExpr($e, "$var_name param default initializer can't have null value");
            }
            $const_value = $this->const_folder->fold($e);
            if ($const_value !== null) {
                $this->result->params[$var_name] = $const_value;
            } else {
                $this->result->params[$var_name] = null;
                $label_end = $this->newLabel();
                $this->emitCondJump(Op::JUMP_NOT_NULL, $var_slot, $label_end);
                $this->compileRootExpr($var_slot, $e);
                $this->bindLabel($label_end);
            }
            $this->expectEndToken(TokenKind::CONTROL_END);
            return;
        }
        $this->result->params[$var_name] = null;
        $label_end = $this->newLabel();
        $this->emitCondJump(Op::JUMP_NOT_NULL, $var_slot, $label_end);
        $this->compileBlockAssign($var_slot, 'param');
        $this->bindLabel($label_end);
    }

    private function compileTemplateArg() {
        $tok = $this->lexer->scan();
        if ($tok->kind !== TokenKind::DOLLAR_IDENT) {
            $this->failToken($tok, 'arg names should be identifiers with leading $, found ' . $tok->prettyKindName());
        }
        $arg_name = $this->lexer->dollarVarName($tok);
        if ($this->current_template_arg) {
            $this->failToken($tok, "attempted to define $arg_name argument while defining $this->current_template_arg");
        }
        if (!$this->frame->addTemplateArg($arg_name)) {
            $this->failToken($tok, "duplicated $arg_name argument");
        }
        $this->current_template_arg = $arg_name;
        if ($this->lexer->consume(TokenKind::ASSIGN)) {
            $e = $this->parser->parseRootExpr($this->lexer);
            if ($e->kind === ExprKind::NULL_LIT) {
                $this->failExpr($e, "passing null will cause the param to be default-initialized");
            }
            $this->compileRootExpr(Frame::ARG_SLOT_PLACEHOLDER, $e);
            $this->expectEndToken(TokenKind::CONTROL_END);
        } else {
            $this->compileBlockAssign(Frame::ARG_SLOT_PLACEHOLDER, 'arg');
        }
        $this->current_template_arg = '';
    }

    private function compileIf() {
        $e = $this->parser->parseRootExpr($this->lexer);
        $jump_op = Op::JUMP_FALSY;
        $cond_slot = 0;
        switch ($e->kind) {
        case ExprKind::NOT_EQ:
            $lhs = $this->parser->getExprMember($e, 0);
            $rhs = $this->parser->getExprMember($e, 1);
            if ($lhs->kind === ExprKind::DOLLAR_IDENT && $rhs->kind === ExprKind::NULL_LIT) {
                $jump_op = Op::JUMP_NOT_NULL;
                $cond_slot = $this->lookupLocalVar($lhs);
            }
            break;
        }

        if ($cond_slot === 0) {
            [$cond_slot, $_] = $this->compileRootTempExpr(0, $e);
        }
        $this->expectEndToken(TokenKind::CONTROL_END);

        $this->frame->enterScope();
        $this->compileIfBody($jump_op, $cond_slot);
        $this->frame->leaveScope();
    }

    /**
     * @param int $jump_op
     * @param int $cond_slot
     */
    private function compileIfBody($jump_op, $cond_slot) {
        $label_next = $this->newLabel();
        $label_end = $this->newLabel();
        $this->emitCondJump($jump_op, $cond_slot, $label_next);
        while (true) {
            $tok = $this->lexer->scan();
            if (self::isControlStartTok($tok->kind)) {
                if ($this->lexer->consume(TokenKind::KEYWORD_END)) {
                    $this->expectEndToken(TokenKind::CONTROL_END);
                    $this->tryBindLabel($label_next);
                    $this->bindLabel($label_end);
                    break;
                }
                if ($this->lexer->consume(TokenKind::KEYWORD_ELSE)) {
                    $this->emitJump($label_end);
                    $this->expectEndToken(TokenKind::CONTROL_END);
                    $this->bindLabel($label_next);
                    continue;
                }
                if ($this->lexer->consume(TokenKind::KEYWORD_ELSEIF)) {
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
        $this->expectEndToken(TokenKind::ECHO_END);
    }

    /**
     * @param int $kind
     */
    private function expectEndToken($kind) {
        $tok = $this->lexer->scan();
        $trim_kind = $kind + 1;
        if ($tok->kind === $trim_kind) {
            $this->trim_left = true;
        } else if ($tok->kind === $kind) {
            $this->trim_left = false;
        } else {
            $this->failToken($tok, "expected " . TokenKind::prettyName($kind) . ' or ' . TokenKind::prettyName($trim_kind) . ', found ' . $tok->prettyKindName());
        }
    }

    /**
     * @param int $tok
     */
    private static function isControlStartTok($tok) {
        return $tok === TokenKind::CONTROL_START || $tok === TokenKind::CONTROL_START_TRIM;
    }

    /**
     * @param string $v
     * @param bool $safe
     */
    private function compileOutputStringConst($v, $safe) {
        if ($v === '') {
            return;
        }
        $op = $this->env->ctx->escape_func && !$safe ? Op::OUTPUT_STRING_CONST : Op::OUTPUT_SAFE_STRING_CONST;
        if ($this->prev_output_pc !== -1 && $this->output_merge_seq < 10) {
            $opdata = $this->result->code[$this->prev_output_pc];
            if (($opdata & 0xff) === $op) {
                $this->output_merge_seq++;
                $this->prev_output_string .= $v;
                $new_string_id = $this->internString($this->prev_output_string);
                $arg_offset = OpInfo::getStringConstOffset($opdata);
                $this->result->code[$this->prev_output_pc] = self::patchOpdata2($opdata, $arg_offset, $new_string_id);
                return;
            }
        }
        $this->output_merge_seq = 0;
        $this->prev_output_pc = $this->getPC();
        $this->prev_output_string = $v;
        $string_id = $this->internString($v);
        $this->emit1($op, $string_id);
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
        case ExprKind::CONCAT:
            if (!$this->needsEscaping(Types::MIXED)) {
                // Compiling the concat may require tmp slots.
                $this->frame->enterTempBlock();
                $this->compileConcat(0, $e, true);
                $this->frame->leaveTempBlock();
                return true;
            }
            return false;
        case ExprKind::DOLLAR_IDENT:
            $var_slot = $this->lookupLocalVar($e);
            $op = $this->needsEscaping(Types::MIXED) ? Op::OUTPUT : Op::OUTPUT_SAFE;
            $this->emit1($op, $var_slot);
            return true;
        case ExprKind::IDENT:
            $cache_slot_info = $this->frame->getCacheSlotInfo((string)$e->value, '', '');
            $cache_slot = $cache_slot_info & 0xff;
            $key_offset = ($cache_slot_info >> 8) & 0xff;
            $this->validateCacheSlot($e, $cache_slot);
            $escape_bit = $this->needsEscaping(Types::MIXED) ? 1 : 0;
            $this->emit3(Op::OUTPUT_EXTDATA_1, $cache_slot, $key_offset, $escape_bit);
            return true;
        case ExprKind::DOT_ACCESS:
            [$p1, $p2, $p3] = $this->decodeDotAccess($e);
            if ($p1 === '') {
                $this->failExpr($e, 'dot access expression is too complex');
            }
            $cache_slot_info = $this->frame->getCacheSlotInfo($p1, $p2, $p3);
            $cache_slot = $cache_slot_info & 0xff;
            $key_offset = ($cache_slot_info >> 8) & 0xff;
            $this->validateCacheSlot($e, $cache_slot);
            $escape_bit = $this->needsEscaping(Types::MIXED) ? 1 : 0;
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
        if ($rhs->kind !== ExprKind::IDENT) {
            return tuple('', '', '');
        }
        if ($lhs->kind === ExprKind::IDENT) {
            return tuple((string)$lhs->value, (string)$rhs->value, '');
        }
        if ($lhs->kind !== ExprKind::DOT_ACCESS) {
            return tuple('', '', '');
        }
        $lhs0 = $this->parser->getExprMember($lhs, 0);
        $lhs1 = $this->parser->getExprMember($lhs, 1);
        if ($lhs0->kind !== ExprKind::IDENT || $lhs1->kind !== ExprKind::IDENT) {
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
                $this->compileOutputStringConst((string)$const_value, !$this->env->ctx->auto_escape_const_expr);
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
     * @return int -- result type
     */
    private function compileRootExpr($dst, $e) {
        $this->frame->enterTempBlock();
        $result_type = $this->compileExpr($dst, $e);
        $this->frame->leaveTempBlock();
        return $result_type;
    }

    /**
     * @param int $dst
     * @param Expr $e
     * @return tuple(int, int) -- a slot and result type
     */
    private function compileRootTempExpr($dst, $e) {
        if ($e->kind === ExprKind::DOLLAR_IDENT) {
            return tuple($this->lookupLocalVar($e), Types::MIXED);
        }
        return tuple($dst, $this->compileRootExpr($dst, $e));
    }

    /**
     * @param Expr $e
     * @return int -- a slot that holds result
     */
    private function compileTempExpr($e) {
        if ($e->kind === ExprKind::DOLLAR_IDENT) {
            return $this->lookupLocalVar($e);
        }
        if ($e->kind === ExprKind::IDENT) {
            $cache_slot = $this->frame->lookupExtdataSlot((string)$e->value, '', '');
            if ($cache_slot !== -1) {
                return (255 - $cache_slot) + 1;
            }
        }
        if ($e->kind === ExprKind::DOT_ACCESS) {
            [$p1, $p2, $p3] = $this->decodeDotAccess($e);
            $cache_slot = $this->frame->lookupExtdataSlot($p1, $p2, $p3);
            if ($cache_slot !== -1) {
                return (255 - $cache_slot) + 1;
            }
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
        if ($this->env->ctx->escape_func === null) {
            return false;
        }
        if ($is_const) {
            return $this->env->ctx->auto_escape_const_expr;
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
            return $this->env->ctx->auto_escape_expr;
        }
    }

    /**
     * @param Expr $e
     * @return bool
     */
    private function isAdditiveBinaryExpr($e) {
        switch ($e->kind) {
        case ExprKind::ADD:
        case ExprKind::MUL:
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
    private function compileExpr($dst, $e, $type = Types::MIXED) {
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
                    return Types::MIXED;
                }
            }
        }

        switch ($e->kind) {
        case ExprKind::BAD:
            $this->fail((int)$e->value['line'], (string)$e->value['msg']);
            return Types::UNKNOWN;

        case ExprKind::DOLLAR_IDENT:
            $this->compileTypedMoveNode($dst, $e, $type);
            return $type;

        case ExprKind::IDENT:
            $cache_slot_info = $this->frame->getCacheSlotInfo((string)$e->value, '', '');
            $cache_slot = $cache_slot_info & 0xff;
            $key_offset = ($cache_slot_info >> 8) & 0xff;
            $this->validateCacheSlot($e, $cache_slot);
            $this->emit3dst(Op::LOAD_EXTDATA_1, $dst, $cache_slot, $key_offset);
            $this->frame->saveExtdataSlot($cache_slot, (string)$e->value, '', '');
            return Types::MIXED;

        case ExprKind::DOT_ACCESS:
            [$p1, $p2, $p3] = $this->decodeDotAccess($e);
            if ($p1 === '') {
                $this->failExpr($e, 'dot access expression is too complex');
            }
            $slot_info = $this->frame->getCacheSlotInfo($p1, $p2, $p3);
            $cache_slot = $slot_info & 0xff;
            $key_offset = ($slot_info >> 8) & 0xff;
            $this->validateCacheSlot($e, $cache_slot);
            $op = $p3 === '' ? Op::LOAD_EXTDATA_2 : Op::LOAD_EXTDATA_3;
            $this->emit3dst($op, $dst, $cache_slot, $key_offset);
            $this->frame->saveExtdataSlot($cache_slot, $p1, $p2, $p3);
            return Types::MIXED;

        case ExprKind::INDEX:
            $this->compileIndex($dst, $e);
            return Types::MIXED;

        case ExprKind::OR:
            $this->compileOr($dst, $e);
            return Types::BOOL;
        case ExprKind::AND:
            $this->compileAnd($dst, $e);
            return Types::BOOL;

        case ExprKind::NOT:
            $this->compileUnaryExpr($dst, Op::NOT, $e);
            return Types::BOOL;
        case ExprKind::NEG:
            $this->compileUnaryExpr($dst, Op::NEG, $e);
            return Types::NUMERIC;

        case ExprKind::GT:
            $this->compileReversedBinaryExprNode($dst, Op::LT, $e);
            return Types::BOOL;
        case ExprKind::GT_EQ:
            $this->compileReversedBinaryExprNode($dst, Op::LT_EQ, $e);
            return Types::BOOL;
        
        case ExprKind::MATCHES:
            return $this->compileMatches($dst, $e);

        case ExprKind::CONCAT:
            return $this->compileConcat($dst, $e);

        case ExprKind::EQ:
        case ExprKind::LT:
        case ExprKind::LT_EQ:
        case ExprKind::NOT_EQ:
        case ExprKind::ADD:
        case ExprKind::SUB:
        case ExprKind::MUL:
        case ExprKind::QUO:
        case ExprKind::MOD:
            return $this->compileBinaryExprNode($dst, $e);

        case ExprKind::NULL_LIT:
            $this->emit1dst(Op::LOAD_NULL, $dst);
            return Types::NULL;

        case ExprKind::STRING_LIT:
            $this->compileStringConst($dst, (string)$e->value);
            return Types::STRING;

        case ExprKind::BOOL_LIT:
            $this->emit2dst(Op::LOAD_BOOL, $dst, (int)$e->value);
            return Types::BOOL;

        case ExprKind::INT_LIT:
            $this->compileIntConst($dst, (int)$e->value);
            return Types::INT;

        case ExprKind::FLOAT_LIT:
            $this->compileFloatConst($dst, (float)$e->value);
            return Types::FLOAT;

        case ExprKind::CALL:
            $this->compileCall($dst, $e);
            return Types::MIXED;

        case ExprKind::FILTER:
            if ($this->parser->getExprMember($e, 1)->kind === ExprKind::IDENT) {
                return $this->compileFilter1($dst, $e);
            }
            if ($this->parser->getExprMember($e, 1)->kind === ExprKind::CALL) {
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
     * @return int
     */
    private function compileMatches($dst, $e) {
        $lhs = $this->parser->getExprMember($e, 0);
        $pattern = $this->parser->getExprMember($e, 1);
        $pattern_value = $this->const_folder->fold($pattern);
        if (!is_string($pattern_value)) {
            $this->failExpr($pattern, 'matches operator rhs pattern should be a const expr string');
        }
        $pattern_string = (string)$pattern_value;
        if ($this->env->ctx->validate_regexp) {
            if ((@preg_match($pattern_string, '')) === false) {
                $this->failExpr($pattern, 'matches operator rhs contains invalid pattern');
            }
        }
        $lhs_slot = $this->compileTempExpr($lhs);
        $this->emit3dst(Op::MATCHES, $dst, $lhs_slot, $this->internString($pattern_string));
        return Types::BOOL;
    }

    /**
     * @param int $dst
     * @param Expr $e
     */
    private function compileIndex($dst, $e) {
        $seq_expr = $this->parser->getExprMember($e, 0);
        $seq_slot = $this->compileTempExpr($seq_expr);
        $key_expr = $this->parser->getExprMember($e, 1);
        if ($key_expr->kind === ExprKind::STRING_LIT) {
            $key_id = $this->internString((string)$key_expr->value);
            $this->emit3dst(Op::INDEX_STRING_KEY, $dst, $seq_slot, $key_id);
            return;
        }
        if ($key_expr->kind === ExprKind::INT_LIT) {
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

        if ($lhs->kind === ExprKind::DOLLAR_IDENT && $rhs->kind === ExprKind::DOLLAR_IDENT) {
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
            $this->emit2dst(Op::CALL_FUNC0, $dst, $func_id);
            break;
        case 1:
            $arg1_slot = $this->compileTempExpr($this->parser->getExprMember($e, 1));
            $this->emit3dst(Op::CALL_FUNC1, $dst, $arg1_slot, $func_id);
            break;
        case 2:
            $arg1_slot = $this->compileTempExpr($this->parser->getExprMember($e, 1));
            $arg2_slot = $this->compileTempExpr($this->parser->getExprMember($e, 2));
            $this->emit4dst(Op::CALL_FUNC2, $dst, $arg1_slot, $arg2_slot, $func_id);
            break;
        case 3:
            $arg1_slot = $this->compileTempExpr($this->parser->getExprMember($e, 1));
            $arg2_slot = $this->compileTempExpr($this->parser->getExprMember($e, 2));
            $arg3_slot = $this->compileTempExpr($this->parser->getExprMember($e, 3));
            $this->emit5dst(Op::CALL_FUNC3, $dst, $arg1_slot, $arg2_slot, $arg3_slot, $func_id);
            break;
        }
    }

    /**
     * @param int $dst
     * @param Expr $e
     */
    private function compileFilter2($dst, $e) {
        $rhs = $this->parser->getExprMember($e, 1);
        if ($rhs->value === 0) {
            $this->failExpr($e, 'omit the () for 0-arguments filter call');
        }
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
            if ($this->env->ctx->escape_func === null) {
                $this->failExpr($e, $filter_name . ' is used, but $ctx->escape_func is null');
            }
            $strategy = $this->internString((string)$arg2_const_value);
            $this->emit3dst(Op::ESCAPE_FILTER2, $dst, $arg1_slot, $strategy);
            return Types::SAFE_STRING;
        }
        $arg2_slot = $this->compileTempExpr($arg2_expr);
        if ($filter_id === -1) {
            if ($filter_name === 'default') {
                $this->emit3dst(Op::DEFAULT_FILTER, $dst, $arg1_slot, $arg2_slot);
                return Types::MIXED;
            }
            $this->failExpr($this->parser->getExprMember($rhs, 0), "$filter_name filter is not defined");
        }
        $this->emit4dst(Op::CALL_FILTER2, $dst, $arg1_slot, $arg2_slot, $filter_id);
        return Types::MIXED;
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
                if ($this->env->ctx->escape_func === null) {
                    $this->failExpr($e, $rhs->value . ' is used, but $ctx->escape_func is null');
                }
                $this->emit2dst(Op::ESCAPE_FILTER1, $dst, $arg1_slot);
                return Types::SAFE_STRING;
            }
            $this->failExpr($rhs, "$rhs->value filter is not defined");
        }
        $this->emit3dst(Op::CALL_FILTER1, $dst, $arg1_slot, $filter_id);
        return Types::MIXED;
    }

    /**
     * @param int $dst
     * @param int $op
     * @param int $lhs_slot
     * @param int $rhs_slot
     */
    private function compileBinaryExpr($dst, $op, $lhs_slot, $rhs_slot) {
        $this->emit3dst($op, $dst, $lhs_slot, $rhs_slot);
    }

    /**
     * @param int $kind
     * @return int
     */
    private function opByBinaryExprKind($kind) {
        switch ($kind) {
        case ExprKind::OR:
            return Op::OR;
        case ExprKind::AND:
            return Op::AND;
        case ExprKind::CONCAT:
            return Op::CONCAT;
        case ExprKind::EQ:
            return Op::EQ;
        case ExprKind::LT:
            return Op::LT;
        case ExprKind::LT_EQ:
            return Op::LT_EQ;
        case ExprKind::NOT_EQ:
            return Op::NOT_EQ;
        case ExprKind::ADD:
            return Op::ADD;
        case ExprKind::SUB:
            return Op::SUB;
        case ExprKind::MUL:
            return Op::MUL;
        case ExprKind::QUO:
            return Op::QUO;
        case ExprKind::MOD:
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
     * @param Expr $e
     * @param bool $direct_output
     * @return int
     */
    private function compileConcat($dst, $e, $direct_output = false) {
        $this->tmp_expr_array_size = 0;
        Expr::walk($this->parser, $e, function ($x) {
            if ($x->kind === ExprKind::CONCAT) {
                return true;
            }
            if ($this->tmp_expr_array_size >= count($this->tmp_expr_array)) {
                $this->failExpr($x, "too many concat operands ($this->tmp_expr_array_size)");
            }
            $this->tmp_expr_array[$this->tmp_expr_array_size] = $x;
            $this->tmp_expr_array_size++;
            return false;
        });

        $num_args = 1;
        for ($i = 1; $i < $this->tmp_expr_array_size; $i++) {
            $prev_arg = $this->tmp_expr_array[$num_args-1];
            $current_arg = $this->tmp_expr_array[$i];
            $const_val = $this->const_folder->foldBinaryExpr(ExprKind::CONCAT, $prev_arg, $current_arg);
            if ($const_val !== null) {
                $prev_arg->kind = ExprKind::STRING_LIT;
                $prev_arg->value = (string)$const_val;
            } else {
                $this->tmp_expr_array[$num_args] = $current_arg;
                $num_args++;
            }
        }

        $i = 0;
        if ($direct_output) {
            // for the direct output without escaping,
            // {{ x }}{{ y }} is identical to {{ x ~ y }} but
            // doesn't produce a temporary string to be appended to the output;
            // we also have OUTPUT2 instruction just for the cases like this.
            while ($num_args > 0) {
                $concat_arg = $this->tmp_expr_array[$i];
                if ($concat_arg->kind === ExprKind::STRING_LIT) {
                    $this->compileOutputStringConst((string)$concat_arg->value, !$this->env->ctx->auto_escape_const_expr);
                    $i++;
                    $num_args--;
                    continue;
                }
                if ($concat_arg->kind === ExprKind::INT_LIT) {
                    $this->compileOutputIntConst((int)$concat_arg->value);
                    $i++;
                    $num_args--;
                    continue;
                }
                if ($num_args >= 2) {
                    $arg1 = $this->compileTempExpr($this->tmp_expr_array[$i]);
                    $arg2 = $this->compileTempExpr($this->tmp_expr_array[$i+1]);
                    $this->emit2(Op::OUTPUT2_SAFE, $arg1, $arg2);
                    $i += 2;
                    $num_args -= 2;
                } else {
                    $arg = $this->compileTempExpr($this->tmp_expr_array[$i]);
                    $this->emit1(Op::OUTPUT_SAFE, $arg);
                    $i++;
                    $num_args--;
                }
            }
            return Types::UNKNOWN;
        }

        if ($num_args >= 3) {
            $i = 3;
            $arg1 = $this->compileTempExpr($this->tmp_expr_array[0]);
            $arg2 = $this->compileTempExpr($this->tmp_expr_array[1]);
            $arg3 = $this->compileTempExpr($this->tmp_expr_array[2]);
            $this->emit4dst(Op::CONCAT3, $dst, $arg1, $arg2, $arg3);
        } else {
            $i = 2;
            $arg1 = $this->compileTempExpr($this->tmp_expr_array[0]);
            $arg2 = $this->compileTempExpr($this->tmp_expr_array[1]);
            $this->emit3dst(Op::CONCAT, $dst, $arg1, $arg2);
        }
        $num_args -= $i;
        while ($num_args > 0) {
            $arg_slot = $this->compileTempExpr($this->tmp_expr_array[$i]);
            $this->emit2dst(Op::APPEND, $dst, $arg_slot);
            $i++;
            $num_args--;
        }
        return Types::STRING;
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
        $this->string_value_map = [];
        $this->string_value_list = [];
        $this->int_values = [];
        $this->prev_output_string = '';
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
        $this->string_value_map = [];
        $this->string_value_list = [];
        $this->int_values = [];
        $this->addr_by_label_id = [];
        $this->label_seq = 0;
        $this->parsing_header = true;
        $this->trim_left = false;

        $this->template_arg_deps = [];
        $this->current_template_path = '';
        $this->current_template_arg = '';

        $this->output_merge_seq = 0;
        $this->prev_output_pc = -1;
        $this->prev_output_string = '';

        $this->tmp_output_tag = '';
    }

    /**
     * @param int $label_id - 16bit
     */
    private function emitJump($label_id) {
        $this->emit(Op::JUMP| ($label_id << 8));
    }

    /**
     * @param int $op
     * @param int $cond_slot
     * @param int $label_id - 16bit
     */
    private function emitCondJump($op, $cond_slot, $label_id) {
        if ($cond_slot === 0) {
            $this->emit(($op+1) | ($label_id << 8));
            return;
        }
        $this->emit(($op) | ($label_id << 8) | ($cond_slot << 24));
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
        if ($dst === Frame::ARG_SLOT_PLACEHOLDER) {
            $this->template_arg_deps[$this->getPC()] = tuple($this->current_template_path, $this->current_template_arg);
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
        if ($dst === Frame::ARG_SLOT_PLACEHOLDER) {
            $this->template_arg_deps[$this->getPC()] = tuple($this->current_template_path, $this->current_template_arg);
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
        if ($dst === Frame::ARG_SLOT_PLACEHOLDER) {
            $this->template_arg_deps[$this->getPC()] = tuple($this->current_template_path, $this->current_template_arg);
        }
        $this->emit3($op, $dst, $arg1, $arg2);
    }

    /**
     * @param int $op
     * @param int $dst
     * @param int $arg1
     * @param int $arg2
     * @param int $arg3
     */
    private function emit4dst($op, $dst, $arg1, $arg2, $arg3) {
        if ($dst === 0) {
            $this->emit3($op+1, $arg1, $arg2, $arg3);
            return;
        }
        if ($dst === Frame::ARG_SLOT_PLACEHOLDER) {
            $this->template_arg_deps[$this->getPC()] = tuple($this->current_template_path, $this->current_template_arg);
        }
        $this->emit4($op, $dst, $arg1, $arg2, $arg3);
    }

    /**
     * @param int $op
     * @param int $dst
     * @param int $arg1
     * @param int $arg2
     * @param int $arg3
     * @param int $arg4
     */
    private function emit5dst($op, $dst, $arg1, $arg2, $arg3, $arg4) {
        if ($dst === 0) {
            $this->emit4($op+1, $arg1, $arg2, $arg3, $arg4);
            return;
        }
        if ($dst === Frame::ARG_SLOT_PLACEHOLDER) {
            $this->template_arg_deps[$this->getPC()] = tuple($this->current_template_path, $this->current_template_arg);
        }
        $this->emit5($op, $dst, $arg1, $arg2, $arg3, $arg4);
    }

    /**
     * @param int $opdata
     */
    private function emit($opdata) {
        if ($this->prev_output_pc !== -1) {
            // Decide whether we need to close the output merging region.
            $op = $opdata & 0xff;
            switch (Op::opcodeKind($op)) {
            case OpInfo::KIND_SIMPLE_ASSIGN:
                break; // OK, simple assignments can't affect the const output.
            case OpInfo::KIND_OUTPUT:
                if ($op === Op::OUTPUT_STRING_CONST || $op === Op::OUTPUT_SAFE_STRING_CONST) {
                    // OK, const outputs can be merged.
                } else {
                    $this->prev_output_pc = -1;
                }
                break;
            default:
                $this->prev_output_pc = -1;
            }
        }
        $this->result->code[] = $opdata;
    }

    /**
     * @param int $op
     * @param int $arg1
     */
    private function emit1($op, $arg1) {
        $this->emit($op | ($arg1 << 8));
    }

    /**
     * @param int $op
     * @param int $arg1
     * @param int $arg2
     */
    private function emit2($op, $arg1, $arg2) {
        $this->emit($op | ($arg1 << 8) | ($arg2 << 16));
    }

    /**
     * @param int $op
     * @param int $arg1
     * @param int $arg2
     * @param int $arg3
     */
    private function emit3($op, $arg1, $arg2, $arg3) {
        $this->emit($op | ($arg1 << 8) | ($arg2 << 16) | ($arg3 << 24));
    }

    /**
     * @param int $op
     * @param int $arg1
     * @param int $arg2
     * @param int $arg3
     * @param int $arg4
     */
    private function emit4($op, $arg1, $arg2, $arg3, $arg4) {
        $this->emit($op | ($arg1 << 8) | ($arg2 << 16) | ($arg3 << 24) | ($arg4 << 32));
    }

    /**
     * @param int $op
     * @param int $arg1
     * @param int $arg2
     * @param int $arg3
     * @param int $arg4
     * @param int $arg5
     */
    private function emit5($op, $arg1, $arg2, $arg3, $arg4, $arg5) {
        $this->emit($op | ($arg1 << 8) | ($arg2 << 16) | ($arg3 << 24) | ($arg4 << 32) | ($arg5 << 40));
    }

    /**
     * @param string $v
     * @return int
     */
    private function internString($v) {
        if (array_key_exists($v, $this->string_value_map)) {
            return $this->string_value_map[$v];
        }
        $id = count($this->string_value_list);
        if ($id > 0xffff) {
            $this->fail(-1, "can't compile: too many string const values");
        }
        $this->string_value_map[$v] = $id;
        $this->string_value_list[] = $v;
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
        if ($id > 0xffff) {
            $this->fail(-1, "can't compile: too many int const values");
        }
        $this->result->int_values[] = $v;
        $this->int_values[$v] = $id;
        return $id;
    }

    /**
     * @param float $v
     * @return int
     */
    private function internFloat($v) {
        // PHP arrays can't have float keys, so they will be converted to ints.
        // This is not what we want, so we use a linear search to find the index.
        // Note that float pools are limited to 255 entries, so this shouldn't
        // be as much of a big deal.
        $search_result = array_search($v, $this->result->float_values, true);
        if (is_int($search_result)) {
            return (int)$search_result;
        }
        $id = count($this->result->float_values);
        if ($id > 0xff) {
            $this->fail(-1, "can't compile: too many float const values");
        }
        $this->result->float_values[] = $v;
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
        $this->failToken($tok, 'expected ' . TokenKind::prettyName($kind) . ', found ' . $tok->prettyKindName());
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
     * @param string $filename
     */
    private function fail($line, $message, $filename = '') {
        $e = new CompilationException($message);
        $e->source_line = $line;
        $e->source_filename = $filename ?: $this->lexer->getFilename();
        throw $e;
    }

    /**
     * @param Expr $e
     * @param int $cache_slot
     */
    private function validateCacheSlot($e, $cache_slot) {
        if ($cache_slot <= 64) {
            return;
        }
        // We're storing a cache bitset inside 64-bit integer.
        // Since every slot require exactly 1 bit, we can't
        // have more than 64 slots reserved for cache.
        //
        // TODO: have a second bitset to lift this restriction to 128?
        // Or maybe have a separate, non-caching opcode for ids>64?
        // Anyway, we need to fail the compilation here for now.
        $this->failExpr($e, "too many external variable references");
    }

    /**
     * @return Template
     */
    private function finalizeTemplate() {
        $this->linkJumps();
        $this->reallocateSlots();
        $this->bindStringValues();

        $num_cache_slots = count($this->frame->cache_slots);
        $frame_size = $num_cache_slots + $this->frame->num_locals;
        $frame_args_size = $this->frame->max_num_args;
        $this->result->setExtraInfo($frame_size, $frame_args_size, $num_cache_slots);

        if ($frame_size > 255) {
            $this->fail(-1, "template frame size is too big (too many local variables?)");
        }

        return $this->resolveTemplateDeps();
    }

    private function bindStringValues() {
        if (count($this->string_value_list) === 0) {
            // Nothing to do: there were no string constants.
            // It's a very rare case though.
            return;
        }

        // Since string_value_map (and list) can contain the unused (dead) values,
        // we fill the result list with only actually used constants.
        $live_strings_map = [];
        foreach ($this->result->code as $pc => $opdata) {
            $op = $opdata & 0xff;
            $op_flags = Op::opcodeFlags($op);
            if (($op_flags & OpInfo::FLAG_HAS_STRING_ARG) === 0) {
                // Skip non-interesting opcodes to make compilation faster.
                continue;
            }
            $arg_shift = OpInfo::getStringConstOffset($opdata);
            $old_value_id = ($opdata >> $arg_shift) & 0xffff;
            $s = $this->string_value_list[$old_value_id];
            $new_value_id = 0;
            if (array_key_exists($s, $live_strings_map)) {
                $new_value_id = $live_strings_map[$s];
            } else {
                $new_value_id = count($this->result->string_values);
                $live_strings_map[$s] = $new_value_id;
                $this->result->string_values[] = $s;
            }
            // In case there were no dead values, all IDs will
            // be the same; don't bother to path the bytecode.
            if ($old_value_id === $new_value_id) {
                continue;
            }
            $this->result->code[$pc] = self::patchOpdata2($opdata, $arg_shift, $new_value_id);
        }
    }

    /**
     * @param int $opdata
     * @param int $shift
     * @param int $value
     * @return int
     */
    private static function patchOpdata1($opdata, $shift, $value) {
        $mask = (0xff << $shift);
        return ($opdata & (~$mask)) | ($value << $shift);
    }

    /**
     * @param int $opdata
     * @param int $shift
     * @param int $value
     * @return int
     */
    private static function patchOpdata2($opdata, $shift, $value) {
        $mask = (0xffff << $shift);
        return ($opdata & (~$mask)) | ($value << $shift);
    }

    private function resolveTemplateDeps() {
        // This is a hard part.
        // When compiling a different template, the same compiler instance
        // can be used; and we want to allow that.
        //
        // Save all the state we'll need to continue and do not depend
        // on the $this state after the dependency is compiled.

        $result = $this->result;
        $env = $this->env;
        $filename = $this->lexer->getFilename();
        $template_arg_deps = $this->template_arg_deps;

        // Accessing $this beyond this point could be a bad idea.
        // Always re-check what you're using and whether it's safe in this context.

        /** @var Template[] $template_cache */
        $template_cache = [];
        $frame_slot_offset = 1; // Skip slot0 index
        foreach ($template_arg_deps as $pc => $tup) {
            [$load_path, $arg_name] = $tup;
            /** @var Template $t */
            $t = null;
            // Although getTemplate() usually caches the results,
            // it's faster to use a local cache map here.
            if (isset($template_cache[$load_path])) {
                $t = $template_cache[$load_path];
            } else {
                $t = $env->getTemplate($load_path);
            }
            $arg_index = Arrays::stringKeyOffset($t->params, $arg_name);
            if ($arg_index === -1) {
                // TODO: -1 is not a good error location.
                $this->fail(-1, "template $load_path doesn't have $arg_name param", $filename);
            }
            $arg_slot = $result->frameSize() + $t->numCacheSlots() + $arg_index + $frame_slot_offset;
            // We have a guarantee that all expression-like operations
            // have dst at fixed offset.
            $opdata = $result->code[$pc];
            Assert::true((($opdata >> 8) & 0xff) === Frame::ARG_SLOT_PLACEHOLDER, 'bad argument slot placeholder');
            $result->code[$pc] = self::patchOpdata1($opdata, 8, $arg_slot);
        }

        return $result;
    }

    private function reallocateSlots() {
        $num_cache_slots = count($this->frame->cache_slots);
        if ($num_cache_slots === 0) {
            // Nothing to do: all slots are OK as they are.
            return;
        }
        $max_slot = 255 - $num_cache_slots;
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
                    if ($orig_slot > $max_slot) {
                        // An inlined cache slot.
                        $new_slot = (255 - $orig_slot) + 1;
                    }
                    $new_opdata = self::patchOpdata1($new_opdata, $arg_shift, $new_slot);
                }
                $arg_shift += OpInfo::argSize($a) * 8;
            }
            $this->result->code[$pc] = $new_opdata;
        }
    }

    private function linkJumps() {
        foreach ($this->result->code as $mixed_pc => $opdata) {
            $pc = (int)$mixed_pc;
            $op = $opdata & 0xff;
            if (!OpInfo::isJump($op)) {
                continue;
            }
            $label_id = ($opdata >> 8) & 0xffff;
            $jump_target = $this->addr_by_label_id[$label_id];
            $jump_offset = ($jump_target - $pc) - 1;
            if ($jump_offset < -32768 || $jump_offset > 32767) {
                $this->fail(-1, "can't compile: jump offset $jump_offset doesn't fit into int16");
            }
            $this->result->code[$pc] = self::patchOpdata2($opdata, 8, $jump_offset);
        }
    }

    /**
     * @return int
     */
    private function newLabel() {
        $id = $this->label_seq;
        if ($id > 0xffff) {
            $this->fail(-1, "can't compile: too many jump targets");
        }
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

        // Labels start a new basic block.
        // We can't optimize between the blocks.
        $this->prev_output_pc = -1;

        $this->addr_by_label_id[$label_id] = $this->getPC();
    }

    /**
     * @return int
     */
    private function getPC() {
        return count($this->result->code);
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
