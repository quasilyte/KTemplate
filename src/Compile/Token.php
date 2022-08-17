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
    public const STRING_LIT_Q1 = 11;
    public const PLUS = 12; // +
    public const MINUS = 13; // -
    public const STAR = 14; // *
    public const SLASH = 15; // /
    public const LPAREN = 16; // (
    public const RPAREN = 17; // )
    public const TILDE = 18; // ~
    public const DOT = 19; // .
    public const KEYWORD_OR = 20;
    public const KEYWORD_IF = 21;
    public const KEYWORD_DO = 22;
    public const KEYWORD_AND = 23;
    public const KEYWORD_FOR = 24;
    public const KEYWORD_USE = 25;
    public const KEYWORD_SET = 26;
    public const EQ = 27; // ==
    public const NOT_EQ = 28; // !=
    public const LT = 29; // <
    public const GT = 30; // >
    public const LT_EQ = 31; // <=
    public const GT_EQ = 32; // >=
    public const KEYWORD_NOT = 33;
    public const KEYWORD_TRUE = 34;
    public const KEYWORD_FALSE = 35;
    public const KEYWORD_ENDIF = 36;
    public const KEYWORD_ENDFOR = 37;
    public const KEYWORD_ELSE = 38;
    public const KEYWORD_ELSEIF = 39;
    public const KEYWORD_LET = 40;
    public const ASSIGN = 41; // =
    public const DOLLAR_IDENT = 42;
    public const KEYWORD_NULL = 43;
    public const STRING_LIT_Q2 = 44;

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
        case self::DOLLAR_IDENT:
        case self::INT_LIT:
        case self::STRING_LIT_Q1:
        case self::STRING_LIT_Q2:
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
        case self::ASSIGN:
            return '=';
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
        case self::DOT:
            return '.';
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
        case self::DOLLAR_IDENT:
            return 'DOLLAR_IDENT';
        case self::INT_LIT:
            return 'INT_LIT';
        case self::STRING_LIT_Q1:
            return 'STRING_LIT_Q1';
        case self::STRING_LIT_Q2:
            return 'STRING_LIT_Q2';
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
        case self::DOT:
            return 'DOT';
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
        case self::KEYWORD_ENDIF:
            return 'ENDIF';
        case self::KEYWORD_ENDFOR:
            return 'ENDFOR';
        case self::KEYWORD_ELSE:
            return 'ELSE';
        case self::KEYWORD_ELSEIF:
            return 'ELSEIF';
        case self::KEYWORD_LET:
            return 'LET';
        case self::ASSIGN:
            return 'ASSIGN';
        case self::KEYWORD_NULL:
            return 'NULL';
        default:
            return '<?>';
        }
    }
}
