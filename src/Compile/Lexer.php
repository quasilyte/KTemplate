<?php

namespace KTemplate\Compile;

class Lexer {
    private $filename = '';
    private $pos = 0;
    private $src = '';
    private $src_len = 0;
    private $has_tok2 = false;
    private $inside_expr = false;

    private $err = '';
    private $last_err_line = 1;
    private $err_cursor = 0;

    /** @var Token */
    private $tok1;
    /** @var Token */
    private $tok2;

    public function __construct() {
        $this->tok1 = new Token();
        $this->tok2 = new Token();
    }

    public function setSource(string $filename, string $src) {
        $this->filename = $filename;
        $this->pos = 0;
        $this->src = $src;
        $this->src_len = strlen($src);
        $this->has_tok2 = false;
        $this->inside_expr = false;
        $this->err = '';
        $this->err_cursor = 0;
        $this->last_err_line = 1;
        $this->tok1->reset();
        $this->tok2->reset();
    }

    public function setExprSource(string $filename, string $src) {
        $this->setSource($filename, $src);
        $this->inside_expr = true;
    }

    public function scan(): Token {
        if ($this->has_tok2) {
            $this->has_tok2 = false;
            $this->tok1->assign($this->tok2);
            return $this->tok1;
        }
        $this->scanInto($this->tok1);
        return $this->tok1;
    }

    public function consume(int $kind) {
        if ($this->peek()->kind == $kind) {
            $this->has_tok2 = false;
            return true;
        }
        return false;
    }

    public function getFilename(): string { return $this->filename; }

    public function getPos(): int { return $this->pos; }

    public function peek(): Token {
        if ($this->has_tok2) {
            return $this->tok2;
        }
        $this->scanInto($this->tok2);
        $this->has_tok2 = true;
        return $this->tok2;
    }

    /**
     * @param Token $tok
     * @return string
     */
    public function tokenText($tok) {
        return (string)substr($this->src, $tok->pos_from, $tok->pos_to - $tok->pos_from);
    }

    /**
     * @param Token $tok
     * @return string
     */
    public function dollarVarName($tok) {
        $from = $tok->pos_from + 1; // Skip the '$' sign
        return (string)substr($this->src, $from, $tok->pos_to - $from);
    }

    /**
     * @param Token $tok
     * @return string
     */
    public function stringText($tok) {
        $from = $tok->pos_from + 1; // Skip the opening quote
        $to = $tok->pos_to - $from - 1; // Skip the closing quote
        return (string)substr($this->src, $from, $to);
    }

    public function getError(): string {
        return $this->err;
    }

    public function getLineByPos(int $pos): int {
        if ($this->err_cursor > $pos) {
            return -1;
        }
        $len = $pos - $this->err_cursor;
        $num_lines = substr_count($this->src, "\n", $this->err_cursor, $len);
        $this->err_cursor = $pos;
        $this->last_err_line += $num_lines;
        return $this->last_err_line;
    }

    private static function isLetter(int $ch) {
        return ($ch >= ord('a') && $ch <= ord('z')) ||
            ($ch >= ord('A') && $ch <= ord('Z'));
    }

    private static function isDigitChar(int $ch) {
        return $ch >= ord('0') && $ch <= ord('9');
    }

    private static function isFirstIdentChar(int $ch) {
        return $ch === ord('$') || self::isLetter($ch) || $ch === ord('_');
    }

    private static function isIdentChar(int $ch) {
        return self::isLetter($ch) || $ch === ord('_') || self::isDigitChar($ch);
    }

    private function peekChar(int $offset) {
        $pos = $this->pos + $offset;
        if ($pos >= $this->src_len) {
            return 0;
        }
        return ord($this->src[$pos]);
    }

    private function scanInto(Token $dst) {
        if ($this->inside_expr) {
            $this->skipWhitespace();
            if ($this->pos >= $this->src_len) {
                $dst->kind = Token::EOF;
                return;
            }
            $ch = ord($this->src[$this->pos]);
            if (self::isFirstIdentChar($ch)) {
                $this->scanIdentInto($dst);
                return;
            }
            if (self::isDigitChar($ch)) {
                $this->scanNumberInto($dst);
                return;
            }
            switch ($ch) {
            case ord('\''):
                $this->scanStringInto($dst, ord('\''));
                return;
            case ord('"'):
                $this->scanStringInto($dst, ord('"'));
                return;
            case ord('+'):
                $this->acceptSimpleToken($dst, Token::PLUS, 1);
                return;
            case ord('-'):
                if (self::isDigitChar($this->peekChar(1))) {
                    $this->scanNumberInto($dst, true);
                    return;
                }
                $this->acceptSimpleToken($dst, Token::MINUS, 1);
                return;
            case ord('*'):
                $this->acceptSimpleToken($dst, Token::STAR, 1);
                return;
            case ord('/'):
                $this->acceptSimpleToken($dst, Token::SLASH, 1);
                return;
            case ord('~'):
                $this->acceptSimpleToken($dst, Token::TILDE, 1);
                return;
            case ord('.'):
                $this->acceptSimpleToken($dst, Token::DOT, 1);
                return;
            case ord('('):
                $this->acceptSimpleToken($dst, Token::LPAREN, 1);
                return;
            case ord(')'):
                $this->acceptSimpleToken($dst, Token::RPAREN, 1);
                return;
            case ord('|'):
                $this->acceptSimpleToken($dst, Token::PIPE, 1);
                return;
            case ord(','):
                $this->acceptSimpleToken($dst, Token::COMMA, 1);
                return;
            case ord('<'):
                switch ($this->peekChar(1)) {
                case ord('='):
                    $this->acceptSimpleToken($dst, Token::LT_EQ, 2);
                    return;
                default:
                    $this->acceptSimpleToken($dst, Token::LT, 1);
                    return;
                }
            case ord('>'):
                switch ($this->peekChar(1)) {
                case ord('='):
                    $this->acceptSimpleToken($dst, Token::GT_EQ, 2);
                    return;
                default:
                    $this->acceptSimpleToken($dst, Token::GT, 1);
                    return;
                }
            case ord('='):
                switch ($this->peekChar(1)) {
                case ord('='):
                    $this->acceptSimpleToken($dst, Token::EQ, 2);
                    return;
                default:
                    $this->acceptSimpleToken($dst, Token::ASSIGN, 1);
                    return;
                }
            case ord('!'):
                switch ($this->peekChar(1)) {
                case ord('='):
                    $this->acceptSimpleToken($dst, Token::NOT_EQ, 2);
                    return;
                }
            }
        }

        if ($this->pos >= $this->src_len) {
            $dst->kind = Token::EOF;
            return;
        }
        switch ($this->src[$this->pos]) {
        case '{':
            if ($this->pos < $this->src_len - 1) {
                switch ($this->src[$this->pos + 1]) {
                case '{':
                    $dst->kind = Token::ECHO_START;
                    $this->pos += 2;
                    $this->inside_expr = true;
                    return;
                case '%':
                    $dst->kind = Token::CONTROL_START;
                    $this->pos += 2;
                    $this->inside_expr = true;
                    return;
                case '#':
                    $this->scanCommentInto($dst);
                    return;
                }
            }
        case '%': {
            if ($this->pos < $this->src_len - 1) {
                switch ($this->src[$this->pos + 1]) {
                case '}':
                    $dst->kind = Token::CONTROL_END;
                    $this->pos += 2;
                    $this->inside_expr = false;
                    return;
                }
            }
        }
        case '}':
            if ($this->pos < $this->src_len - 1) {
                switch ($this->src[$this->pos + 1]) {
                case '}':
                    $dst->kind = Token::ECHO_END;
                    $this->pos += 2;
                    $this->inside_expr = false;
                    return;
                }
            }
        }

        $this->scanTextInto($dst);
    }

    private function skipWhitespace() {
        while ($this->pos < $this->src_len && $this->src[$this->pos] === ' ') {
            $this->pos++;
        }
    }

    /**
     * @param Token $dst
     * @param int $quote
     */
    private function scanStringInto($dst, $quote) {
        $dst->kind = $quote === ord('"') ? Token::STRING_LIT_Q2 : Token::STRING_LIT_Q1;
        $dst->pos_from = $this->pos;
        $this->pos++;
        while ($this->pos < $this->src_len) {
            $ch = ord($this->src[$this->pos]);
            if ($ch === $quote && ord($this->src[$this->pos-1]) !== ord('\\')) {
                break;
            }
            $this->pos++;
        }
        if (ord($this->src[$this->pos]) !== $quote) {
            $this->setError($dst, 'unterminated string literal');
            return;
        }
        $this->pos++;
        $dst->pos_to = $this->pos;
    }

    /**
     * @param Token $dst
     * @param bool $minus
     */
    private function scanNumberInto($dst, $minus = false) {
        $dst->kind = Token::INT_LIT;
        $dst->pos_from = $this->pos;
        $this->pos++;
        if ($minus) {
            $this->pos++;   
        }
        while ($this->pos < $this->src_len && self::isDigitChar(ord($this->src[$this->pos]))) {
            $this->pos++;
        }
        $dst->pos_to = $this->pos;
    }

    /**
     * @param Token $dst
     */
    private function scanIdentInto($dst) {
        $dst->kind = ord($this->src[$this->pos]) === ord('$') ? Token::DOLLAR_IDENT : Token::IDENT;
        $dst->pos_from = $this->pos;
        $this->pos++;
        while ($this->pos < $this->src_len && self::isIdentChar(ord($this->src[$this->pos]))) {
            $this->pos++;
        }
        $dst->pos_to = $this->pos;
        if ($dst->kind === Token::DOLLAR_IDENT) {
            return; // Keywords never start with '$'
        }
        switch ((int)($dst->pos_to - $dst->pos_from)) {
        case 2:
            if (substr_compare($this->src, 'or', $dst->pos_from, strlen('or')) === 0) {
                $dst->kind = Token::KEYWORD_OR;
                return;
            }
            if (substr_compare($this->src, 'if', $dst->pos_from, strlen('if')) === 0) {
                $dst->kind = Token::KEYWORD_IF;
                return;
            }
            if (substr_compare($this->src, 'do', $dst->pos_from, strlen('do')) === 0) {
                $dst->kind = Token::KEYWORD_DO;
                return;
            }
        case 3:
            if (substr_compare($this->src, 'not', $dst->pos_from, strlen('not')) === 0) {
                $dst->kind = Token::KEYWORD_NOT;
                return;
            }
            if (substr_compare($this->src, 'and', $dst->pos_from, strlen('and')) === 0) {
                $dst->kind = Token::KEYWORD_AND;
                return;
            }
            if (substr_compare($this->src, 'for', $dst->pos_from, strlen('for')) === 0) {
                $dst->kind = Token::KEYWORD_FOR;
                return;
            }
            if (substr_compare($this->src, 'use', $dst->pos_from, strlen('use')) === 0) {
                $dst->kind = Token::KEYWORD_USE;
                return;
            }
            if (substr_compare($this->src, 'set', $dst->pos_from, strlen('set')) === 0) {
                $dst->kind = Token::KEYWORD_SET;
                return;
            }
            if (substr_compare($this->src, 'let', $dst->pos_from, strlen('let')) === 0) {
                $dst->kind = Token::KEYWORD_LET;
                return;
            }
        case 4:
            if (substr_compare($this->src, 'true', $dst->pos_from, strlen('true')) === 0) {
                $dst->kind = Token::KEYWORD_TRUE;
                return;
            }
            if (substr_compare($this->src, 'null', $dst->pos_from, strlen('null')) === 0) {
                $dst->kind = Token::KEYWORD_NULL;
                return;
            }
            if (substr_compare($this->src, 'else', $dst->pos_from, strlen('else')) === 0) {
                $dst->kind = Token::KEYWORD_ELSE;
                return;
            }
        case 5:
            if (substr_compare($this->src, 'false', $dst->pos_from, strlen('false')) === 0) {
                $dst->kind = Token::KEYWORD_FALSE;
                return;
            }
            if (substr_compare($this->src, 'endif', $dst->pos_from, strlen('endif')) === 0) {
                $dst->kind = Token::KEYWORD_ENDIF;
                return;
            }
        case 6:
            if (substr_compare($this->src, 'endfor', $dst->pos_from, strlen('endfor')) === 0) {
                $dst->kind = Token::KEYWORD_ENDFOR;
                return;
            }
            if (substr_compare($this->src, 'elseif', $dst->pos_from, strlen('elseif')) === 0) {
                $dst->kind = Token::KEYWORD_ELSEIF;
                return;
            }
        }
    }

    private function scanCommentInto(Token $dst) {
        $dst->kind = Token::COMMENT;
        $dst->pos_from = $this->pos + 2;
        $end_pos = strpos($this->src, '#}', $this->pos + 2);
        if ($end_pos === false) {
            $this->setError($dst, 'missing #}');
            return;
        }
        $dst->pos_to = $end_pos;
        $this->pos = $end_pos + 2;
    }

    private function scanTextInto(Token $dst) {
        $dst->kind = Token::TEXT;
        $dst->pos_from = $this->pos;
        while (true) {
            $lbrace_pos = strpos($this->src, '{', $this->pos);
            if ($lbrace_pos === false || ($lbrace_pos === $this->src_len - 1)) {
                $dst->pos_to = $this->src_len;
                $this->pos = $dst->pos_to;
                return;
            }
            $next_char = $this->src[$lbrace_pos + 1];
            if ($next_char === '{' || $next_char === '%' || $next_char === '#') {
                $dst->pos_to = $lbrace_pos;
                $this->pos = $lbrace_pos;
                return;
            }
            $this->pos = $lbrace_pos;
        }
    }

    private function acceptSimpleToken(Token $dst, int $kind, int $width) {
        $dst->kind = $kind;
        $dst->pos_from = $this->pos;
        $dst->pos_to = $this->pos + $width;
        $this->pos += $width;
    }

    private function setError(Token $dst, string $err) {
        $dst->kind = Token::ERROR;
        $this->err = $err;
        $this->pos = $this->src_len;
    }
}
