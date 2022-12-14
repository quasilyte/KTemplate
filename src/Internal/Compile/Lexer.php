<?php

namespace KTemplate\Internal\Compile;

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

    /**
     * @param string $filename
     * @param string $src
     */
    public function setSource($filename, $src) {
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

    /**
     * @param string $filename
     * @param string $src
     */
    public function setExprSource($filename, $src) {
        $this->setSource($filename, $src);
        $this->inside_expr = true;
    }

    /**
     * @return Token
     */
    public function scan() {
        if ($this->has_tok2) {
            $this->has_tok2 = false;
            $this->tok1->assign($this->tok2);
            return $this->tok1;
        }
        $this->scanInto($this->tok1);
        return $this->tok1;
    }

    /**
     * @param int $kind
     * @return bool
     */
    public function consume($kind) {
        if ($this->peek()->kind == $kind) {
            $this->has_tok2 = false;
            return true;
        }
        return false;
    }

    /**
     * @return string
     */
    public function getFilename() { return $this->filename; }

    /**
     * @return int
     */
    public function getPos() { return $this->pos; }

    /**
     * @return Token
     */
    public function peek() {
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

    /**
     * @return string
     */
    public function getError() {
        return $this->err;
    }

    /**
     * @param int $pos
     * @return int
     */
    public function getLineByPos($pos) {
        if ($this->err_cursor > $pos) {
            return -1;
        }
        $len = $pos - $this->err_cursor;
        $num_lines = substr_count($this->src, "\n", $this->err_cursor, $len);
        $this->err_cursor = $pos;
        $this->last_err_line += $num_lines;
        return $this->last_err_line;
    }

    /**
     * @param int $ch
     * @return bool
     */
    private static function isLetter($ch) {
        return ($ch >= ord('a') && $ch <= ord('z')) ||
            ($ch >= ord('A') && $ch <= ord('Z'));
    }

    /**
     * @param int $ch
     * @return bool
     */
    private static function isDigitChar($ch) {
        return $ch >= ord('0') && $ch <= ord('9');
    }

    /**
     * @param int $ch
     * @return bool
     */
    private static function isFirstIdentChar($ch) {
        return $ch === ord('$') || self::isLetter($ch) || $ch === ord('_');
    }

    /**
     * @param int $ch
     * @return bool
     */
    private static function isIdentChar($ch) {
        return self::isLetter($ch) || $ch === ord('_') || self::isDigitChar($ch);
    }

    /**
     * @param int $offset
     * @return int
     */
    private function peekChar($offset) {
        $pos = $this->pos + $offset;
        if ($pos >= $this->src_len) {
            return 0;
        }
        return ord($this->src[$pos]);
    }

    /**
     * @param Token $dst
     */
    private function scanInto($dst) {
        if ($this->inside_expr) {
            $this->skipWhitespace();
            if ($this->pos >= $this->src_len) {
                $dst->kind = TokenKind::EOF;
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
                $this->scanStringInto($dst, TokenKind::STRING_LIT_Q1);
                return;
            case ord('"'):
                $this->scanStringInto($dst, TokenKind::STRING_LIT_Q2);
                return;
            case ord('`'):
                $this->scanStringInto($dst, TokenKind::STRING_LIT_RAW);
                return;
            case ord('['):
                $this->acceptSimpleToken($dst, TokenKind::LBRACKET, 1);
                return;
            case ord(']'):
                $this->acceptSimpleToken($dst, TokenKind::RBRACKET, 1);
                return;
            case ord('+'):
                $this->acceptSimpleToken($dst, TokenKind::PLUS, 1);
                return;
            case ord('-'):
                if ($this->peekChar(1) === ord('}') && $this->peekChar(2) === ord('}')) {
                    $this->acceptSimpleToken($dst, TokenKind::ECHO_END_TRIM, 3);
                    $this->inside_expr = false;
                    return;
                }
                if ($this->peekChar(1) === ord('%') && $this->peekChar(2) === ord('}')) {
                    $this->acceptSimpleToken($dst, TokenKind::CONTROL_END_TRIM, 3);
                    $this->inside_expr = false;
                    return;
                }
                $this->acceptSimpleToken($dst, TokenKind::MINUS, 1);
                return;
            case ord('*'):
                $this->acceptSimpleToken($dst, TokenKind::STAR, 1);
                return;
            case ord('/'):
                $this->acceptSimpleToken($dst, TokenKind::SLASH, 1);
                return;
            case ord('~'):
                $this->acceptSimpleToken($dst, TokenKind::TILDE, 1);
                return;
            case ord('.'):
                $this->acceptSimpleToken($dst, TokenKind::DOT, 1);
                return;
            case ord('('):
                $this->acceptSimpleToken($dst, TokenKind::LPAREN, 1);
                return;
            case ord(')'):
                $this->acceptSimpleToken($dst, TokenKind::RPAREN, 1);
                return;
            case ord('|'):
                $this->acceptSimpleToken($dst, TokenKind::PIPE, 1);
                return;
            case ord(','):
                $this->acceptSimpleToken($dst, TokenKind::COMMA, 1);
                return;
            case ord('%'):
                if ($this->peekChar(1) !== ord('}')) {
                    $this->acceptSimpleToken($dst, TokenKind::PERCENT, 1);
                    return;
                }
                break; // Scan as top-level token
            case ord('<'):
                switch ($this->peekChar(1)) {
                case ord('='):
                    $this->acceptSimpleToken($dst, TokenKind::LT_EQ, 2);
                    return;
                default:
                    $this->acceptSimpleToken($dst, TokenKind::LT, 1);
                    return;
                }
            case ord('>'):
                switch ($this->peekChar(1)) {
                case ord('='):
                    $this->acceptSimpleToken($dst, TokenKind::GT_EQ, 2);
                    return;
                default:
                    $this->acceptSimpleToken($dst, TokenKind::GT, 1);
                    return;
                }
            case ord('='):
                switch ($this->peekChar(1)) {
                case ord('='):
                    $this->acceptSimpleToken($dst, TokenKind::EQ, 2);
                    return;
                default:
                    $this->acceptSimpleToken($dst, TokenKind::ASSIGN, 1);
                    return;
                }
            case ord('!'):
                switch ($this->peekChar(1)) {
                case ord('='):
                    $this->acceptSimpleToken($dst, TokenKind::NOT_EQ, 2);
                    return;
                }
            }
        }

        if ($this->pos >= $this->src_len) {
            $dst->kind = TokenKind::EOF;
            return;
        }
        switch ($this->src[$this->pos]) {
        case '{':
            if ($this->peekChar(1) === ord('{') && $this->peekChar(2) === ord('-')) {
                $dst->kind = TokenKind::ECHO_START_TRIM;
                $this->pos += 3;
                $this->inside_expr = true;
                return;
            }
            if ($this->peekChar(1) === ord('%') && $this->peekChar(2) === ord('-')) {
                $dst->kind = TokenKind::CONTROL_START_TRIM;
                $this->pos += 3;
                $this->inside_expr = true;
                return;
            }
            if ($this->pos < $this->src_len - 1) {
                switch ($this->src[$this->pos + 1]) {
                case '{':
                    $dst->kind = TokenKind::ECHO_START;
                    $this->pos += 2;
                    $this->inside_expr = true;
                    return;
                case '%':
                    $dst->kind = TokenKind::CONTROL_START;
                    $this->pos += 2;
                    $this->inside_expr = true;
                    return;
                case '#':
                    $this->scanCommentInto($dst);
                    return;
                }
            }
            break;
        case '%': {
            if ($this->pos < $this->src_len - 1) {
                switch ($this->src[$this->pos + 1]) {
                case '}':
                    $dst->kind = TokenKind::CONTROL_END;
                    $this->pos += 2;
                    $this->inside_expr = false;
                    return;
                }
            }
            break;
        }
        case '}':
            if ($this->pos < $this->src_len - 1) {
                switch ($this->src[$this->pos + 1]) {
                case '}':
                    $dst->kind = TokenKind::ECHO_END;
                    $this->pos += 2;
                    $this->inside_expr = false;
                    return;
                }
            }
            break;
        }

        $this->scanTextInto($dst);
    }

    private function skipWhitespace() {
        $this->pos += strspn($this->src, " \t\n\r", $this->pos);
    }

    /**
     * @param Token $dst
     * @param int $kind
     */
    private function scanStringInto($dst, $kind) {
        $dst->kind = $kind;
        $dst->pos_from = $this->pos;
        $this->pos++;
        if ($kind === TokenKind::STRING_LIT_RAW) {
                $strpos_result = strpos($this->src, '`', $this->pos);
                if ($strpos_result === false) {
                    $this->pos = $this->src_len;
                } else {
                    $this->pos = (int)$strpos_result;
                }
        } else {
            $quote = $kind === TokenKind::STRING_LIT_Q1 ? ord('\'') : ord('"');
            while ($this->pos < $this->src_len) {
                $ch = ord($this->src[$this->pos]);
                if ($ch === $quote && ord($this->src[$this->pos-1]) !== ord('\\')) {
                    break;
                }
                $this->pos++;
            }
        }
        if ($this->pos >= $this->src_len) {
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
        $dst->kind = TokenKind::INT_LIT;
        $dst->pos_from = $this->pos;
        $this->pos++;
        if ($minus) {
            $this->pos++;   
        }
        while ($this->pos < $this->src_len && self::isDigitChar(ord($this->src[$this->pos]))) {
            $this->pos++;
        }
        if ($this->peekChar(0) === ord('.')) {
            $this->pos++;
            $dst->kind = TokenKind::FLOAT_LIT;
            while ($this->pos < $this->src_len && self::isDigitChar(ord($this->src[$this->pos]))) {
                $this->pos++;
            }
        }
        $dst->pos_to = $this->pos;
    }

    /**
     * @param Token $dst
     */
    private function scanIdentInto($dst) {
        $dst->kind = ord($this->src[$this->pos]) === ord('$') ? TokenKind::DOLLAR_IDENT : TokenKind::IDENT;
        $dst->pos_from = $this->pos;
        $this->pos++;
        while ($this->pos < $this->src_len && self::isIdentChar(ord($this->src[$this->pos]))) {
            $this->pos++;
        }
        $dst->pos_to = $this->pos;
        if ($dst->kind === TokenKind::DOLLAR_IDENT) {
            return; // Keywords never start with '$'
        }
        switch ((int)($dst->pos_to - $dst->pos_from)) {
        case 2:
            if (substr_compare($this->src, 'or', $dst->pos_from, 2) === 0) {
                $dst->kind = TokenKind::KEYWORD_OR;
                return;
            }
            if (substr_compare($this->src, 'if', $dst->pos_from, 2) === 0) {
                $dst->kind = TokenKind::KEYWORD_IF;
                return;
            }
            if (substr_compare($this->src, 'do', $dst->pos_from, 2) === 0) {
                $dst->kind = TokenKind::KEYWORD_DO;
                return;
            }
            if (substr_compare($this->src, 'in', $dst->pos_from, 2) === 0) {
                $dst->kind = TokenKind::KEYWORD_IN;
                return;
            }
            break;
        case 3:
            if (substr_compare($this->src, 'end', $dst->pos_from, 3) === 0) {
                $dst->kind = TokenKind::KEYWORD_END;
                return;
            }
            if (substr_compare($this->src, 'not', $dst->pos_from, 3) === 0) {
                $dst->kind = TokenKind::KEYWORD_NOT;
                return;
            }
            if (substr_compare($this->src, 'and', $dst->pos_from, 3) === 0) {
                $dst->kind = TokenKind::KEYWORD_AND;
                return;
            }
            if (substr_compare($this->src, 'for', $dst->pos_from, 3) === 0) {
                $dst->kind = TokenKind::KEYWORD_FOR;
                return;
            }
            if (substr_compare($this->src, 'use', $dst->pos_from, 3) === 0) {
                $dst->kind = TokenKind::KEYWORD_USE;
                return;
            }
            if (substr_compare($this->src, 'set', $dst->pos_from, 3) === 0) {
                $dst->kind = TokenKind::KEYWORD_SET;
                return;
            }
            if (substr_compare($this->src, 'let', $dst->pos_from, 3) === 0) {
                $dst->kind = TokenKind::KEYWORD_LET;
                return;
            }
            if (substr_compare($this->src, 'arg', $dst->pos_from, 3) === 0) {
                $dst->kind = TokenKind::KEYWORD_ARG;
                return;
            }
            break;
        case 4:
            if (substr_compare($this->src, 'true', $dst->pos_from, 4) === 0) {
                $dst->kind = TokenKind::KEYWORD_TRUE;
                return;
            }
            if (substr_compare($this->src, 'null', $dst->pos_from, 4) === 0) {
                $dst->kind = TokenKind::KEYWORD_NULL;
                return;
            }
            if (substr_compare($this->src, 'else', $dst->pos_from, 4) === 0) {
                $dst->kind = TokenKind::KEYWORD_ELSE;
                return;
            }
            break;
        case 5:
            if (substr_compare($this->src, 'false', $dst->pos_from, 5) === 0) {
                $dst->kind = TokenKind::KEYWORD_FALSE;
                return;
            }
            if (substr_compare($this->src, 'param', $dst->pos_from, 5) === 0) {
                $dst->kind = TokenKind::KEYWORD_PARAM;
                return;
            }
            break;
        case 6:
            if (substr_compare($this->src, 'elseif', $dst->pos_from, 6) === 0) {
                $dst->kind = TokenKind::KEYWORD_ELSEIF;
                return;
            }
            break;
        case 7:
            if (substr_compare($this->src, 'include', $dst->pos_from, 7) === 0) {
                $dst->kind = TokenKind::KEYWORD_INCLUDE;
                return;
            }
            if (substr_compare($this->src, 'matches', $dst->pos_from, 7) === 0) {
                $dst->kind = TokenKind::KEYWORD_MATCHES;
                return;
            }
            break;
        }
    }

    /**
     * @param Token $dst
     */
    private function scanCommentInto($dst) {
        $dst->kind = TokenKind::COMMENT;
        $dst->pos_from = $this->pos + 2;
        $end_pos = strpos($this->src, '#}', $this->pos + 2);
        if ($end_pos === false) {
            $this->setError($dst, 'missing #}');
            return;
        }
        $dst->pos_to = $end_pos;
        $this->pos = $end_pos + 2;
    }

    /**
     * @param Token $dst
     */
    private function scanTextInto($dst) {
        $dst->kind = TokenKind::TEXT;
        $dst->pos_from = $this->pos;
        while (true) {
            $lbrace_pos = strpos($this->src, '{', $this->pos);
            if ($lbrace_pos === false || ($lbrace_pos === $this->src_len - 1)) {
                // End of the source or no '{' found.
                $dst->pos_to = $this->src_len;
                $this->pos = $dst->pos_to;
                return;
            }
            $next_char = $this->src[$lbrace_pos + 1];
            if ($next_char === '{' || $next_char === '%' || $next_char === '#') {
                // One of the control characters.
                $dst->pos_to = $lbrace_pos;
                $this->pos = $lbrace_pos;
                return;
            }
            // Just a single '{', continue scanning the text.
            $this->pos = $lbrace_pos + 1;
        }
    }

    /**
     * @param Token $dst
     * @param int $kind
     * @param int $width
     */
    private function acceptSimpleToken($dst, $kind, $width) {
        $dst->kind = $kind;
        $dst->pos_from = $this->pos;
        $dst->pos_to = $this->pos + $width;
        $this->pos += $width;
    }

    /**
     * @param Token $dst
     * @param string $err
     */
    private function setError($dst, $err) {
        $dst->kind = TokenKind::ERROR;
        $this->err = $err;
        $this->pos = $this->src_len;
    }
}
