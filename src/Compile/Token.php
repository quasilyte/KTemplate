<?php

namespace KTemplate\Compile;

class Token {
    public const UNSET = 0;
    public const EOF = 1;
    public const TEXT = 2;
    public const ECHO_START = 3; // {{
    public const ECHO_END = 4; // }}
    public const CONTROL_START = 5; // {%
    public const CONTROL_END = 6; // %}
    public const COMMENT = 7; // {# ... #}
    public const ERROR = 8;
    public const IDENT = 9;
    public const INT_LIT = 10;
    public const STR_LIT = 11;
    public const PLUS = 12; // +
    public const MINUS = 13; // -
    public const STAR = 14; // *
    public const SLASH = 15; // /
    public const LPAREN = 16; // (
    public const RPAREN = 17; // )
    public const TILDE = 18; // ~
    public const KEYWORD_OR = 19;
    public const KEYWORD_IF = 20;
    public const KEYWORD_DO = 21;
    public const KEYWORD_AND = 22;
    public const KEYWORD_FOR = 23;
    public const KEYWORD_USE = 24;
    public const KEYWORD_SET = 25;
    public const EQ = 26; // ==
    public const NOT_EQ = 27; // !=
    public const LT = 28; // <
    public const GT = 29; // >
    public const LT_EQ = 30; // <=
    public const GT_EQ = 31; // >=
    public const KEYWORD_NOT = 32;
    public const KEYWORD_TRUE = 33;
    public const KEYWORD_FALSE = 34;

    public $kind = 0;
    public $pos_from = 0;
    public $pos_to = 0;

    public function reset() {
        $this->kind = self::UNSET;
        $this->pos_from = 0;
        $this->pos_to = 0;
    }

    public function assign(Token $other) {
        $this->kind = $other->kind;
        $this->pos_from = $other->pos_from;
        $this->pos_to = $other->pos_to;
    }

    public static function hasValue(int $kind): bool {
        switch ($kind) {
        case self::TEXT:
        case self::COMMENT:
        case self::IDENT:
        case self::INT_LIT:
        case self::STR_LIT:
            return true;
        default:
            return false;
        }
    }

    public static function prettyKindString(int $kind): string {
        switch ($kind) {
        case self::ECHO_START:
            return '{{';
        case self::ECHO_END:
            return '}}';
        case self::CONTROL_START:
            return '{%';
        case self::CONTROL_END:
            return '%}';
        case self::COMMENT:
            return '{##}';
        case self::PLUS:
            return '+';
        case self::MINUS:
            return '-';
        case self::STAR:
            return '*';
        case self::SLASH:
            return '/';
        case self::LPAREN:
            return ')';
        case self::RPAREN:
            return '(';
        case self::TILDE:
            return '~';
        case self::EQ:
            return '==';
        case self::NOT_EQ:
            return '!=';
        case self::LT:
            return '<';
        case self::GT:
            return '>';
        case self::LT_EQ:
            return '<=';
        case self::GT_EQ:
            return '>=';
        default:
            return strtolower(self::kindString($kind));
        }
    }

    public static function kindString(int $kind): string {
        switch ($kind) {
        case self::UNSET:
            return 'UNSET';
        case self::EOF:
            return 'EOF';
        case self::TEXT:
            return 'TEXT';
        case self::ECHO_START:
            return 'ECHO_START';
        case self::ECHO_END:
            return 'ECHO_END';
        case self::CONTROL_START:
            return 'CONTROL_START';
        case self::CONTROL_END:
            return 'CONTROL_END';
        case self::COMMENT:
            return 'COMMENT';
        case self::ERROR:
            return 'ERROR';
        case self::IDENT:
            return 'IDENT';
        case self::INT_LIT:
            return 'INT_LIT';
        case self::STR_LIT:
            return 'STR_LIT';
        case self::PLUS:
            return 'PLUS';
        case self::MINUS:
            return 'MINUS';
        case self::STAR:
            return 'STAR';
        case self::SLASH:
            return 'SLASH';
        case self::LPAREN:
            return 'LPAREN';
        case self::RPAREN:
            return 'RPAREN';
        case self::TILDE:
            return 'TILDE';
        case self::KEYWORD_OR:
            return 'OR';
        case self::KEYWORD_IF:
            return 'IF';
        case self::KEYWORD_DO:
            return 'DO';
        case self::KEYWORD_AND:
            return 'AND';
        case self::KEYWORD_FOR:
            return 'FOR';
        case self::KEYWORD_USE:
            return 'USE';
        case self::KEYWORD_SET:
            return 'SET';
        case self::EQ:
            return 'EQ';
        case self::NOT_EQ:
            return 'NOT_EQ';
        case self::LT:
            return 'LT';
        case self::GT:
            return 'GT';
        case self::LT_EQ:
            return 'LT_EQ';
        case self::GT_EQ:
            return 'GT_EQ';
        case self::KEYWORD_NOT:
            return 'NOT';
        case self::KEYWORD_TRUE:
            return 'TRUE';
        case self::KEYWORD_FALSE:
            return 'FALSE';
        default:
            return '<?>';
        }
    }
}
