<?php

namespace KTemplate\Compile;

use KTemplate\Template;
use KTemplate\Op;
use KTemplate\OpInfo;

class Compiler {
    /** @var Lexer */
    private $lexer;

    /** @var ExprParser */
    private $parser;

    /** @var Template */
    private $result;

    /** @var ConstFolder */
    private $const_folder;

    /** @var int[] */
    private $addr_by_label_id = [];
    /** @var int */
    private $label_seq = 0;

    /** @var int[] */
    private $string_values;
    /** @var int[] */
    private $int_values;

    public function __construct() {
        $this->lexer = new Lexer();
        $this->parser = new ExprParser();
        $this->const_folder = new ConstFolder($this->parser);
    }

    /**
     * @param string $filename
     * @param string $source
     * @return Template
     */
    public function compile($filename, $source) {
        $this->reset($filename, $source);
        /** @var ?\Throwable $exception */
        $exception = null;

        try {
            while (true) {
                $tok = $this->lexer->scan();
                if ($tok->kind === Token::EOF) {
                    break;
                }
                $this->compileToken($tok);
            }
            $this->emit(Op::RETURN);
            $this->linkJumps();
            return $this->result;
        } catch (\Throwable $e) {
            $exception = $e;
        }

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
            $this->compileOutputStringConst($this->lexer->tokenValue($tok));
            return;
        case Token::ECHO_START:
            $this->compileEcho();
            return;
        case Token::CONTROL_START:
            $this->compileControl();
            return;
        }

        $this->failToken($tok, 'unexpected top-level token: ' . Token::kindString($tok->kind));
    }

    private function compileControl() {
        $tok = $this->lexer->scan();
        switch ($tok->kind) {
        case Token::KEYWORD_IF:
            $this->compileIf();
            return;
        }

        $this->failToken($tok, 'unexpected control token: ' . Token::kindString($tok->kind));
    }

    private function compileIf() {
        $e = $this->parser->parseRootExpr($this->lexer);
        $this->compileExpr(0, $e);
        $this->expectToken(Token::CONTROL_END);

        $label_next = $this->newLabel();
        $label_end = $this->newLabel();
        $this->emitJump(Op::JUMP_ZERO, $label_next);
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
                    $this->emitJump(Op::JUMP, $label_end);
                    $this->expectToken(Token::CONTROL_END);
                    $this->bindLabel($label_next);
                    continue;
                }
                if ($this->lexer->consume(Token::KEYWORD_ELSEIF)) {
                    // {% if 1 %}a{% elseif 2 %}b{% endif %}
                    $this->emitJump(Op::JUMP, $label_end);
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
            $this->compileExpr(0, $e);
            $this->emit(Op::OUTPUT_SLOT0);
        }
        $this->expectToken(Token::ECHO_END);
    }

    /**
     * @param string $v
     */
    private function compileOutputStringConst($v) {
        $string_id = $this->internString($v);
        $this->emit1(Op::OUTPUT_STRING_CONST, $string_id);
    }

    /**
     * @param int $v
     */
    private function compileOutputIntConst($v) {
        $int_id = $this->internInt($v);
        $this->emit1(Op::OUTPUT_INT_CONST, $int_id);
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
        case Expr::IDENT:
            $this->emit1(Op::OUTPUT_VAR_1, $this->internString((string)$e->value));
            return true;
        case Expr::DOT_ACCESS:
            [$p1, $p2, $p3] = $this->decodeDotAccess($e);
            if ($p1 === '') {
                $this->failExpr($e, 'dot access expression is too complex');
            }
            if ($p3 === '') {
                $this->emit2(Op::OUTPUT_VAR_2, $this->internString($p1), $this->internString($p2));
            } else {
                $this->emit3(Op::OUTPUT_VAR_3, $this->internString($p1), $this->internString($p2), $this->internString($p3));
            }
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
                $this->compileOutputStringConst((string)$const_value);
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
     */
    private function compileExpr($dst, $e) {
        switch ($e->kind) {
        case Expr::IDENT:
            $this->failExpr($e, 'TODO');
            return;

        case Expr::INT_LIT:
            if ($dst === 0) {
                $this->emit1(Op::LOAD_SLOT0_INT_CONST, $this->internInt((int)$e->value));
            } else {
                $this->emit2(Op::LOAD_INT_CONST, $dst, $this->internInt((int)$e->value));
            }
            return;
        }
    
        $this->failExpr($e, "compile expr: unexpected $e->kind");
    }

    /**
     * finish is executed when the compilation is finished.
     * It tries to minimize the compiler object memory footprint
     * by releasing the memory that won't be needed anymore.
     */
    private function finish() {
        $this->string_values = [];
        $this->int_values = [];
    }

    /**
     * @param string $filename
     * @param string $source
     */
    private function reset($filename, $source) {
        $this->result = new Template();
        $this->lexer->setSource($filename, $source);
        $this->string_values = [];
        $this->int_values = [];
        $this->addr_by_label_id = [];
        $this->label_seq = 0;
    }

    /**
     * @param int $op
     * @param int $label_id
     */
    private function emitJump($op, $label_id) {
        $this->emit1($op, $label_id);
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
    private function internInt(int $v) {
        if (array_key_exists($v, $this->int_values)) {
            return $this->int_values[$v];
        }
        $id = count($this->result->int_values);
        $this->result->int_values[] = $v;
        $this->int_values[$v] = $id;
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

    private function linkJumps() {
        $mask = 0xff << 8;
        foreach ($this->result->code as $pc => $opdata) {
            $op = $opdata & 0xff;
            if (!OpInfo::isJump($op)) {
                continue;
            }
            $label_id = ($opdata >> 8) & 0xff;
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

}
