<?php

namespace KTemplate;

use KTemplate\Compile\Types;

class Op {
    public const UNKNOWN = 0;
    
    // Encoding: 0x01
    // Result type: unknown/varying
    public const RETURN = 1;
    
    // Encoding: 0x02 arg:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    // Result type: unknown/varying
    public const OUTPUT = 2;
    
    // Encoding: 0x03
    // Flags: FLAG_IMPLICIT_SLOT0
    // Result type: unknown/varying
    public const OUTPUT_SLOT0 = 3;
    
    // Encoding: 0x04 val:intindex
    // Result type: unknown/varying
    public const OUTPUT_INT_CONST = 4;
    
    // Encoding: 0x05 val:strindex
    // Result type: unknown/varying
    public const OUTPUT_STRING_CONST = 5;
    
    // Encoding: 0x06 cache:cacheslot k:keyoffset
    // Result type: unknown/varying
    public const OUTPUT_EXTDATA_1 = 6;
    
    // Encoding: 0x07 cache:cacheslot k:keyoffset
    // Result type: unknown/varying
    public const OUTPUT_EXTDATA_2 = 7;
    
    // Encoding: 0x08 cache:cacheslot k:keyoffset
    // Result type: unknown/varying
    public const OUTPUT_EXTDATA_3 = 8;
    
    // Encoding: 0x09 dst:wslot val:imm8
    // Flags: FLAG_HAS_SLOT_ARG
    // Result type: Types::BOOL
    public const LOAD_BOOL = 9;
    
    // Encoding: 0x0a val:imm8
    // Flags: FLAG_IMPLICIT_SLOT0
    // Result type: Types::BOOL
    public const LOAD_SLOT0_BOOL = 10;
    
    // Encoding: 0x0b dst:wslot val:intindex
    // Flags: FLAG_HAS_SLOT_ARG
    // Result type: Types::INT
    public const LOAD_INT_CONST = 11;
    
    // Encoding: 0x0c val:intindex
    // Flags: FLAG_IMPLICIT_SLOT0
    // Result type: Types::INT
    public const LOAD_SLOT0_INT_CONST = 12;
    
    // Encoding: 0x0d dst:wslot val:floatindex
    // Flags: FLAG_HAS_SLOT_ARG
    // Result type: Types::FLOAT
    public const LOAD_FLOAT_CONST = 13;
    
    // Encoding: 0x0e val:floatindex
    // Flags: FLAG_IMPLICIT_SLOT0
    // Result type: Types::FLOAT
    public const LOAD_SLOT0_FLOAT_CONST = 14;
    
    // Encoding: 0x0f dst:wslot val:strindex
    // Flags: FLAG_HAS_SLOT_ARG
    // Result type: Types::STRING
    public const LOAD_STRING_CONST = 15;
    
    // Encoding: 0x10 val:strindex
    // Flags: FLAG_IMPLICIT_SLOT0
    // Result type: Types::STRING
    public const LOAD_SLOT0_STRING_CONST = 16;
    
    // Encoding: 0x11 dst:wslot cache:cacheslot k:keyoffset
    // Flags: FLAG_HAS_SLOT_ARG
    // Result type: unknown/varying
    public const LOAD_EXTDATA_1 = 17;
    
    // Encoding: 0x12 cache:cacheslot k:keyoffset
    // Flags: FLAG_IMPLICIT_SLOT0
    // Result type: unknown/varying
    public const LOAD_SLOT0_EXTDATA_1 = 18;
    
    // Encoding: 0x13 dst:wslot cache:cacheslot k:keyoffset
    // Flags: FLAG_HAS_SLOT_ARG
    // Result type: unknown/varying
    public const LOAD_EXTDATA_2 = 19;
    
    // Encoding: 0x14 cache:cacheslot k:keyoffset
    // Flags: FLAG_IMPLICIT_SLOT0
    // Result type: unknown/varying
    public const LOAD_SLOT0_EXTDATA_2 = 20;
    
    // Encoding: 0x15 dst:wslot cache:cacheslot k:keyoffset
    // Flags: FLAG_HAS_SLOT_ARG
    // Result type: unknown/varying
    public const LOAD_EXTDATA_3 = 21;
    
    // Encoding: 0x16 cache:cacheslot k:keyoffset
    // Flags: FLAG_IMPLICIT_SLOT0
    // Result type: unknown/varying
    public const LOAD_SLOT0_EXTDATA_3 = 22;
    
    // Encoding: 0x17 dst:wslot
    // Flags: FLAG_HAS_SLOT_ARG
    // Result type: Types::NULL
    public const LOAD_NULL = 23;
    
    // Encoding: 0x18
    // Result type: Types::NULL
    public const LOAD_SLOT0_NULL = 24;
    
    // Encoding: 0x19 dst:wslot src:rslot key:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    // Result type: unknown/varying
    public const INDEX = 25;
    
    // Encoding: 0x1a src:rslot key:rslot
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    // Result type: unknown/varying
    public const INDEX_SLOT0 = 26;
    
    // Encoding: 0x1b dst:wslot src:rslot key:intindex
    // Flags: FLAG_HAS_SLOT_ARG
    // Result type: unknown/varying
    public const INDEX_INT_KEY = 27;
    
    // Encoding: 0x1c src:rslot key:intindex
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    // Result type: unknown/varying
    public const INDEX_SLOT0_INT_KEY = 28;
    
    // Encoding: 0x1d dst:wslot src:rslot key:strindex
    // Flags: FLAG_HAS_SLOT_ARG
    // Result type: unknown/varying
    public const INDEX_STRING_KEY = 29;
    
    // Encoding: 0x1e src:rslot key:strindex
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    // Result type: unknown/varying
    public const INDEX_SLOT0_STRING_KEY = 30;
    
    // Encoding: 0x1f dst:wslot src:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    // Result type: unknown/varying
    public const MOVE = 31;
    
    // Encoding: 0x20 src:rslot
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    // Result type: unknown/varying
    public const MOVE_SLOT0 = 32;
    
    // Encoding: 0x21 dst:wslot src:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    // Result type: Types::BOOL
    public const MOVE_BOOL = 33;
    
    // Encoding: 0x22 src:rslot
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    // Result type: Types::BOOL
    public const MOVE_SLOT0_BOOL = 34;
    
    // Encoding: 0x23 dst:wslot
    // Flags: FLAG_HAS_SLOT_ARG
    // Result type: Types::BOOL
    public const CONV_BOOL = 35;
    
    // Encoding: 0x24
    // Flags: FLAG_IMPLICIT_SLOT0
    // Result type: Types::BOOL
    public const CONV_SLOT0_BOOL = 36;
    
    // Encoding: 0x25 pcdelta:rel16
    // Result type: unknown/varying
    public const JUMP = 37;
    
    // Encoding: 0x26 pcdelta:rel16 cond:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    // Result type: unknown/varying
    public const JUMP_FALSY = 38;
    
    // Encoding: 0x27 pcdelta:rel16
    // Flags: FLAG_IMPLICIT_SLOT0
    // Result type: unknown/varying
    public const JUMP_SLOT0_FALSY = 39;
    
    // Encoding: 0x28 pcdelta:rel16 cond:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    // Result type: unknown/varying
    public const JUMP_TRUTHY = 40;
    
    // Encoding: 0x29 pcdelta:rel16
    // Flags: FLAG_IMPLICIT_SLOT0
    // Result type: unknown/varying
    public const JUMP_SLOT0_TRUTHY = 41;
    
    // Encoding: 0x2a pcdelta:rel16 val:wslot
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    // Result type: unknown/varying
    public const FOR_VAL = 42;
    
    // Encoding: 0x2b pcdelta:rel16 key:wslot val:wslot
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    // Result type: unknown/varying
    public const FOR_KEY_VAL = 43;
    
    // Encoding: 0x2c dst:wslot arg1:rslot fn:filterid
    // Flags: FLAG_HAS_SLOT_ARG
    // Result type: unknown/varying
    public const CALL_FILTER1 = 44;
    
    // Encoding: 0x2d arg1:rslot fn:filterid
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    // Result type: unknown/varying
    public const CALL_SLOT0_FILTER1 = 45;
    
    // Encoding: 0x2e dst:wslot arg1:rslot arg2:rslot fn:filterid
    // Flags: FLAG_HAS_SLOT_ARG
    // Result type: unknown/varying
    public const CALL_FILTER2 = 46;
    
    // Encoding: 0x2f arg1:rslot arg2:rslot fn:filterid
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    // Result type: unknown/varying
    public const CALL_SLOT0_FILTER2 = 47;
    
    // Encoding: 0x30 dst:wslot fn:funcid
    // Flags: FLAG_HAS_SLOT_ARG
    // Result type: unknown/varying
    public const CALL_FUNC0 = 48;
    
    // Encoding: 0x31 fn:funcid
    // Flags: FLAG_IMPLICIT_SLOT0
    // Result type: unknown/varying
    public const CALL_SLOT0_FUNC0 = 49;
    
    // Encoding: 0x32 dst:wslot arg1:rslot fn:funcid
    // Flags: FLAG_HAS_SLOT_ARG
    // Result type: unknown/varying
    public const CALL_FUNC1 = 50;
    
    // Encoding: 0x33 arg1:rslot fn:funcid
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    // Result type: unknown/varying
    public const CALL_SLOT0_FUNC1 = 51;
    
    // Encoding: 0x34 dst:wslot arg1:rslot arg2:rslot fn:funcid
    // Flags: FLAG_HAS_SLOT_ARG
    // Result type: unknown/varying
    public const CALL_FUNC2 = 52;
    
    // Encoding: 0x35 arg1:rslot arg2:rslot fn:funcid
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    // Result type: unknown/varying
    public const CALL_SLOT0_FUNC2 = 53;
    
    // Encoding: 0x36 dst:wslot arg1:rslot arg2:rslot arg3:rslot fn:funcid
    // Flags: FLAG_HAS_SLOT_ARG
    // Result type: unknown/varying
    public const CALL_FUNC3 = 54;
    
    // Encoding: 0x37 arg1:rslot arg2:rslot arg3:rslot fn:funcid
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    // Result type: unknown/varying
    public const CALL_SLOT0_FUNC3 = 55;
    
    // Encoding: 0x38 dst:wslot arg1:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    // Result type: Types::INT
    public const LENGTH_FILTER = 56;
    
    // Encoding: 0x39 dst:wslot arg1:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    // Result type: Types::INT
    public const LENGTH_SLOT0_FILTER = 57;
    
    // Encoding: 0x3a dst:wslot arg1:rslot arg2:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    // Result type: unknown/varying
    public const DEFAULT_FILTER = 58;
    
    // Encoding: 0x3b dst:wslot arg1:rslot arg2:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    // Result type: unknown/varying
    public const DEFAULT_SLOT0_FILTER = 59;
    
    // Encoding: 0x3c dst:wslot arg:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    // Result type: Types::BOOL
    public const NOT = 60;
    
    // Encoding: 0x3d arg:rslot
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    // Result type: Types::BOOL
    public const NOT_SLOT0 = 61;
    
    // Encoding: 0x3e dst:wslot arg:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    // Result type: Types::NUMERIC
    public const NEG = 62;
    
    // Encoding: 0x3f arg:rslot
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    // Result type: Types::NUMERIC
    public const NEG_SLOT0 = 63;
    
    // Encoding: 0x40 dst:wslot arg1:rslot arg2:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    // Result type: Types::BOOL
    public const OR = 64;
    
    // Encoding: 0x41 arg1:rslot arg2:rslot
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    // Result type: Types::BOOL
    public const OR_SLOT0 = 65;
    
    // Encoding: 0x42 dst:wslot arg1:rslot arg2:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    // Result type: Types::BOOL
    public const AND = 66;
    
    // Encoding: 0x43 arg1:rslot arg2:rslot
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    // Result type: Types::BOOL
    public const AND_SLOT0 = 67;
    
    // Encoding: 0x44 dst:wslot arg1:rslot arg2:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    // Result type: Types::STRING
    public const CONCAT = 68;
    
    // Encoding: 0x45 arg1:rslot arg2:rslot
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    // Result type: Types::STRING
    public const CONCAT_SLOT0 = 69;
    
    // Encoding: 0x46 dst:wslot arg1:rslot arg2:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    // Result type: Types::BOOL
    public const EQ = 70;
    
    // Encoding: 0x47 arg1:rslot arg2:rslot
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    // Result type: Types::BOOL
    public const EQ_SLOT0 = 71;
    
    // Encoding: 0x48 dst:wslot arg1:rslot arg2:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    // Result type: Types::BOOL
    public const LT = 72;
    
    // Encoding: 0x49 arg1:rslot arg2:rslot
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    // Result type: Types::BOOL
    public const LT_SLOT0 = 73;
    
    // Encoding: 0x4a dst:wslot arg1:rslot arg2:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    // Result type: Types::BOOL
    public const LT_EQ = 74;
    
    // Encoding: 0x4b arg1:rslot arg2:rslot
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    // Result type: Types::BOOL
    public const LT_EQ_SLOT0 = 75;
    
    // Encoding: 0x4c dst:wslot arg1:rslot arg2:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    // Result type: Types::BOOL
    public const NOT_EQ = 76;
    
    // Encoding: 0x4d arg1:rslot arg2:rslot
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    // Result type: Types::BOOL
    public const NOT_EQ_SLOT0 = 77;
    
    // Encoding: 0x4e dst:wslot arg1:rslot arg2:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    // Result type: Types::NUMERIC
    public const ADD = 78;
    
    // Encoding: 0x4f arg1:rslot arg2:rslot
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    // Result type: Types::NUMERIC
    public const ADD_SLOT0 = 79;
    
    // Encoding: 0x50 dst:wslot arg1:rslot arg2:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    // Result type: Types::NUMERIC
    public const SUB = 80;
    
    // Encoding: 0x51 arg1:rslot arg2:rslot
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    // Result type: Types::NUMERIC
    public const SUB_SLOT0 = 81;
    
    // Encoding: 0x52 dst:wslot arg1:rslot arg2:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    // Result type: Types::NUMERIC
    public const MUL = 82;
    
    // Encoding: 0x53 arg1:rslot arg2:rslot
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    // Result type: Types::NUMERIC
    public const MUL_SLOT0 = 83;
    
    // Encoding: 0x54 dst:wslot arg1:rslot arg2:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    // Result type: Types::NUMERIC
    public const QUO = 84;
    
    // Encoding: 0x55 arg1:rslot arg2:rslot
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    // Result type: Types::NUMERIC
    public const QUO_SLOT0 = 85;
    
    // Encoding: 0x56 dst:wslot arg1:rslot arg2:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    // Result type: Types::NUMERIC
    public const MOD = 86;
    
    // Encoding: 0x57 arg1:rslot arg2:rslot
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    // Result type: Types::NUMERIC
    public const MOD_SLOT0 = 87;
    

    /**
     * @param int $op
     * @return string
     */
    public static function opcodeString($op) {
        switch ($op) {
        case 1:
            return 'RETURN';
        case 2:
            return 'OUTPUT';
        case 3:
            return 'OUTPUT_SLOT0';
        case 4:
            return 'OUTPUT_INT_CONST';
        case 5:
            return 'OUTPUT_STRING_CONST';
        case 6:
            return 'OUTPUT_EXTDATA_1';
        case 7:
            return 'OUTPUT_EXTDATA_2';
        case 8:
            return 'OUTPUT_EXTDATA_3';
        case 9:
            return 'LOAD_BOOL';
        case 10:
            return 'LOAD_SLOT0_BOOL';
        case 11:
            return 'LOAD_INT_CONST';
        case 12:
            return 'LOAD_SLOT0_INT_CONST';
        case 13:
            return 'LOAD_FLOAT_CONST';
        case 14:
            return 'LOAD_SLOT0_FLOAT_CONST';
        case 15:
            return 'LOAD_STRING_CONST';
        case 16:
            return 'LOAD_SLOT0_STRING_CONST';
        case 17:
            return 'LOAD_EXTDATA_1';
        case 18:
            return 'LOAD_SLOT0_EXTDATA_1';
        case 19:
            return 'LOAD_EXTDATA_2';
        case 20:
            return 'LOAD_SLOT0_EXTDATA_2';
        case 21:
            return 'LOAD_EXTDATA_3';
        case 22:
            return 'LOAD_SLOT0_EXTDATA_3';
        case 23:
            return 'LOAD_NULL';
        case 24:
            return 'LOAD_SLOT0_NULL';
        case 25:
            return 'INDEX';
        case 26:
            return 'INDEX_SLOT0';
        case 27:
            return 'INDEX_INT_KEY';
        case 28:
            return 'INDEX_SLOT0_INT_KEY';
        case 29:
            return 'INDEX_STRING_KEY';
        case 30:
            return 'INDEX_SLOT0_STRING_KEY';
        case 31:
            return 'MOVE';
        case 32:
            return 'MOVE_SLOT0';
        case 33:
            return 'MOVE_BOOL';
        case 34:
            return 'MOVE_SLOT0_BOOL';
        case 35:
            return 'CONV_BOOL';
        case 36:
            return 'CONV_SLOT0_BOOL';
        case 37:
            return 'JUMP';
        case 38:
            return 'JUMP_FALSY';
        case 39:
            return 'JUMP_SLOT0_FALSY';
        case 40:
            return 'JUMP_TRUTHY';
        case 41:
            return 'JUMP_SLOT0_TRUTHY';
        case 42:
            return 'FOR_VAL';
        case 43:
            return 'FOR_KEY_VAL';
        case 44:
            return 'CALL_FILTER1';
        case 45:
            return 'CALL_SLOT0_FILTER1';
        case 46:
            return 'CALL_FILTER2';
        case 47:
            return 'CALL_SLOT0_FILTER2';
        case 48:
            return 'CALL_FUNC0';
        case 49:
            return 'CALL_SLOT0_FUNC0';
        case 50:
            return 'CALL_FUNC1';
        case 51:
            return 'CALL_SLOT0_FUNC1';
        case 52:
            return 'CALL_FUNC2';
        case 53:
            return 'CALL_SLOT0_FUNC2';
        case 54:
            return 'CALL_FUNC3';
        case 55:
            return 'CALL_SLOT0_FUNC3';
        case 56:
            return 'LENGTH_FILTER';
        case 57:
            return 'LENGTH_SLOT0_FILTER';
        case 58:
            return 'DEFAULT_FILTER';
        case 59:
            return 'DEFAULT_SLOT0_FILTER';
        case 60:
            return 'NOT';
        case 61:
            return 'NOT_SLOT0';
        case 62:
            return 'NEG';
        case 63:
            return 'NEG_SLOT0';
        case 64:
            return 'OR';
        case 65:
            return 'OR_SLOT0';
        case 66:
            return 'AND';
        case 67:
            return 'AND_SLOT0';
        case 68:
            return 'CONCAT';
        case 69:
            return 'CONCAT_SLOT0';
        case 70:
            return 'EQ';
        case 71:
            return 'EQ_SLOT0';
        case 72:
            return 'LT';
        case 73:
            return 'LT_SLOT0';
        case 74:
            return 'LT_EQ';
        case 75:
            return 'LT_EQ_SLOT0';
        case 76:
            return 'NOT_EQ';
        case 77:
            return 'NOT_EQ_SLOT0';
        case 78:
            return 'ADD';
        case 79:
            return 'ADD_SLOT0';
        case 80:
            return 'SUB';
        case 81:
            return 'SUB_SLOT0';
        case 82:
            return 'MUL';
        case 83:
            return 'MUL_SLOT0';
        case 84:
            return 'QUO';
        case 85:
            return 'QUO_SLOT0';
        case 86:
            return 'MOD';
        case 87:
            return 'MOD_SLOT0';
        default:
            return '?';
        }
    }

    /**
     * @param int $op
     * @return int
     */
    public static function opcodeResultType($op) {
        switch ($op) {
        case self::LOAD_BOOL:
            return Types::BOOL;
        case self::LOAD_SLOT0_BOOL:
            return Types::BOOL;
        case self::LOAD_INT_CONST:
            return Types::INT;
        case self::LOAD_SLOT0_INT_CONST:
            return Types::INT;
        case self::LOAD_FLOAT_CONST:
            return Types::FLOAT;
        case self::LOAD_SLOT0_FLOAT_CONST:
            return Types::FLOAT;
        case self::LOAD_STRING_CONST:
            return Types::STRING;
        case self::LOAD_SLOT0_STRING_CONST:
            return Types::STRING;
        case self::LOAD_NULL:
            return Types::NULL;
        case self::LOAD_SLOT0_NULL:
            return Types::NULL;
        case self::MOVE_BOOL:
            return Types::BOOL;
        case self::MOVE_SLOT0_BOOL:
            return Types::BOOL;
        case self::CONV_BOOL:
            return Types::BOOL;
        case self::CONV_SLOT0_BOOL:
            return Types::BOOL;
        case self::LENGTH_FILTER:
            return Types::INT;
        case self::LENGTH_SLOT0_FILTER:
            return Types::INT;
        case self::NOT:
            return Types::BOOL;
        case self::NOT_SLOT0:
            return Types::BOOL;
        case self::NEG:
            return Types::NUMERIC;
        case self::NEG_SLOT0:
            return Types::NUMERIC;
        case self::OR:
            return Types::BOOL;
        case self::OR_SLOT0:
            return Types::BOOL;
        case self::AND:
            return Types::BOOL;
        case self::AND_SLOT0:
            return Types::BOOL;
        case self::CONCAT:
            return Types::STRING;
        case self::CONCAT_SLOT0:
            return Types::STRING;
        case self::EQ:
            return Types::BOOL;
        case self::EQ_SLOT0:
            return Types::BOOL;
        case self::LT:
            return Types::BOOL;
        case self::LT_SLOT0:
            return Types::BOOL;
        case self::LT_EQ:
            return Types::BOOL;
        case self::LT_EQ_SLOT0:
            return Types::BOOL;
        case self::NOT_EQ:
            return Types::BOOL;
        case self::NOT_EQ_SLOT0:
            return Types::BOOL;
        case self::ADD:
            return Types::NUMERIC;
        case self::ADD_SLOT0:
            return Types::NUMERIC;
        case self::SUB:
            return Types::NUMERIC;
        case self::SUB_SLOT0:
            return Types::NUMERIC;
        case self::MUL:
            return Types::NUMERIC;
        case self::MUL_SLOT0:
            return Types::NUMERIC;
        case self::QUO:
            return Types::NUMERIC;
        case self::QUO_SLOT0:
            return Types::NUMERIC;
        case self::MOD:
            return Types::NUMERIC;
        case self::MOD_SLOT0:
            return Types::NUMERIC;
        default:
            return Types::UNKNOWN;
        }
    }

    /**
     * @param int $op
     * @return int
     */
    public static function opcodeFlags($op) {
        switch ($op) {
        case 1: // RETURN
            return 0;
        case 2: // OUTPUT
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 3: // OUTPUT_SLOT0
            return OpInfo::FLAG_IMPLICIT_SLOT0;
        case 4: // OUTPUT_INT_CONST
            return 0;
        case 5: // OUTPUT_STRING_CONST
            return 0;
        case 6: // OUTPUT_EXTDATA_1
            return 0;
        case 7: // OUTPUT_EXTDATA_2
            return 0;
        case 8: // OUTPUT_EXTDATA_3
            return 0;
        case 9: // LOAD_BOOL
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 10: // LOAD_SLOT0_BOOL
            return OpInfo::FLAG_IMPLICIT_SLOT0;
        case 11: // LOAD_INT_CONST
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 12: // LOAD_SLOT0_INT_CONST
            return OpInfo::FLAG_IMPLICIT_SLOT0;
        case 13: // LOAD_FLOAT_CONST
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 14: // LOAD_SLOT0_FLOAT_CONST
            return OpInfo::FLAG_IMPLICIT_SLOT0;
        case 15: // LOAD_STRING_CONST
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 16: // LOAD_SLOT0_STRING_CONST
            return OpInfo::FLAG_IMPLICIT_SLOT0;
        case 17: // LOAD_EXTDATA_1
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 18: // LOAD_SLOT0_EXTDATA_1
            return OpInfo::FLAG_IMPLICIT_SLOT0;
        case 19: // LOAD_EXTDATA_2
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 20: // LOAD_SLOT0_EXTDATA_2
            return OpInfo::FLAG_IMPLICIT_SLOT0;
        case 21: // LOAD_EXTDATA_3
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 22: // LOAD_SLOT0_EXTDATA_3
            return OpInfo::FLAG_IMPLICIT_SLOT0;
        case 23: // LOAD_NULL
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 24: // LOAD_SLOT0_NULL
            return 0;
        case 25: // INDEX
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 26: // INDEX_SLOT0
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 27: // INDEX_INT_KEY
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 28: // INDEX_SLOT0_INT_KEY
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 29: // INDEX_STRING_KEY
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 30: // INDEX_SLOT0_STRING_KEY
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 31: // MOVE
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 32: // MOVE_SLOT0
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 33: // MOVE_BOOL
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 34: // MOVE_SLOT0_BOOL
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 35: // CONV_BOOL
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 36: // CONV_SLOT0_BOOL
            return OpInfo::FLAG_IMPLICIT_SLOT0;
        case 37: // JUMP
            return 0;
        case 38: // JUMP_FALSY
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 39: // JUMP_SLOT0_FALSY
            return OpInfo::FLAG_IMPLICIT_SLOT0;
        case 40: // JUMP_TRUTHY
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 41: // JUMP_SLOT0_TRUTHY
            return OpInfo::FLAG_IMPLICIT_SLOT0;
        case 42: // FOR_VAL
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 43: // FOR_KEY_VAL
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 44: // CALL_FILTER1
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 45: // CALL_SLOT0_FILTER1
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 46: // CALL_FILTER2
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 47: // CALL_SLOT0_FILTER2
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 48: // CALL_FUNC0
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 49: // CALL_SLOT0_FUNC0
            return OpInfo::FLAG_IMPLICIT_SLOT0;
        case 50: // CALL_FUNC1
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 51: // CALL_SLOT0_FUNC1
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 52: // CALL_FUNC2
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 53: // CALL_SLOT0_FUNC2
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 54: // CALL_FUNC3
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 55: // CALL_SLOT0_FUNC3
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 56: // LENGTH_FILTER
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 57: // LENGTH_SLOT0_FILTER
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 58: // DEFAULT_FILTER
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 59: // DEFAULT_SLOT0_FILTER
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 60: // NOT
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 61: // NOT_SLOT0
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 62: // NEG
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 63: // NEG_SLOT0
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 64: // OR
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 65: // OR_SLOT0
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 66: // AND
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 67: // AND_SLOT0
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 68: // CONCAT
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 69: // CONCAT_SLOT0
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 70: // EQ
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 71: // EQ_SLOT0
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 72: // LT
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 73: // LT_SLOT0
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 74: // LT_EQ
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 75: // LT_EQ_SLOT0
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 76: // NOT_EQ
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 77: // NOT_EQ_SLOT0
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 78: // ADD
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 79: // ADD_SLOT0
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 80: // SUB
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 81: // SUB_SLOT0
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 82: // MUL
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 83: // MUL_SLOT0
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 84: // QUO
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 85: // QUO_SLOT0
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 86: // MOD
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 87: // MOD_SLOT0
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        default:
            return 0;
        }
    }

    public static $args = [
        self::RETURN => [],
        self::OUTPUT => [OpInfo::ARG_SLOT],
        self::OUTPUT_SLOT0 => [],
        self::OUTPUT_INT_CONST => [OpInfo::ARG_INT_CONST],
        self::OUTPUT_STRING_CONST => [OpInfo::ARG_STRING_CONST],
        self::OUTPUT_EXTDATA_1 => [OpInfo::ARG_CACHE_SLOT, OpInfo::ARG_KEY_OFFSET],
        self::OUTPUT_EXTDATA_2 => [OpInfo::ARG_CACHE_SLOT, OpInfo::ARG_KEY_OFFSET],
        self::OUTPUT_EXTDATA_3 => [OpInfo::ARG_CACHE_SLOT, OpInfo::ARG_KEY_OFFSET],
        self::LOAD_BOOL => [OpInfo::ARG_SLOT, OpInfo::ARG_IMM8],
        self::LOAD_SLOT0_BOOL => [OpInfo::ARG_IMM8],
        self::LOAD_INT_CONST => [OpInfo::ARG_SLOT, OpInfo::ARG_INT_CONST],
        self::LOAD_SLOT0_INT_CONST => [OpInfo::ARG_INT_CONST],
        self::LOAD_FLOAT_CONST => [OpInfo::ARG_SLOT, OpInfo::ARG_FLOAT_CONST],
        self::LOAD_SLOT0_FLOAT_CONST => [OpInfo::ARG_FLOAT_CONST],
        self::LOAD_STRING_CONST => [OpInfo::ARG_SLOT, OpInfo::ARG_STRING_CONST],
        self::LOAD_SLOT0_STRING_CONST => [OpInfo::ARG_STRING_CONST],
        self::LOAD_EXTDATA_1 => [OpInfo::ARG_SLOT, OpInfo::ARG_CACHE_SLOT, OpInfo::ARG_KEY_OFFSET],
        self::LOAD_SLOT0_EXTDATA_1 => [OpInfo::ARG_CACHE_SLOT, OpInfo::ARG_KEY_OFFSET],
        self::LOAD_EXTDATA_2 => [OpInfo::ARG_SLOT, OpInfo::ARG_CACHE_SLOT, OpInfo::ARG_KEY_OFFSET],
        self::LOAD_SLOT0_EXTDATA_2 => [OpInfo::ARG_CACHE_SLOT, OpInfo::ARG_KEY_OFFSET],
        self::LOAD_EXTDATA_3 => [OpInfo::ARG_SLOT, OpInfo::ARG_CACHE_SLOT, OpInfo::ARG_KEY_OFFSET],
        self::LOAD_SLOT0_EXTDATA_3 => [OpInfo::ARG_CACHE_SLOT, OpInfo::ARG_KEY_OFFSET],
        self::LOAD_NULL => [OpInfo::ARG_SLOT],
        self::LOAD_SLOT0_NULL => [],
        self::INDEX => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::INDEX_SLOT0 => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::INDEX_INT_KEY => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT, OpInfo::ARG_INT_CONST],
        self::INDEX_SLOT0_INT_KEY => [OpInfo::ARG_SLOT, OpInfo::ARG_INT_CONST],
        self::INDEX_STRING_KEY => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT, OpInfo::ARG_STRING_CONST],
        self::INDEX_SLOT0_STRING_KEY => [OpInfo::ARG_SLOT, OpInfo::ARG_STRING_CONST],
        self::MOVE => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::MOVE_SLOT0 => [OpInfo::ARG_SLOT],
        self::MOVE_BOOL => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::MOVE_SLOT0_BOOL => [OpInfo::ARG_SLOT],
        self::CONV_BOOL => [OpInfo::ARG_SLOT],
        self::CONV_SLOT0_BOOL => [],
        self::JUMP => [OpInfo::ARG_REL16],
        self::JUMP_FALSY => [OpInfo::ARG_REL16, OpInfo::ARG_SLOT],
        self::JUMP_SLOT0_FALSY => [OpInfo::ARG_REL16],
        self::JUMP_TRUTHY => [OpInfo::ARG_REL16, OpInfo::ARG_SLOT],
        self::JUMP_SLOT0_TRUTHY => [OpInfo::ARG_REL16],
        self::FOR_VAL => [OpInfo::ARG_REL16, OpInfo::ARG_SLOT],
        self::FOR_KEY_VAL => [OpInfo::ARG_REL16, OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::CALL_FILTER1 => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT, OpInfo::ARG_FILTER_ID],
        self::CALL_SLOT0_FILTER1 => [OpInfo::ARG_SLOT, OpInfo::ARG_FILTER_ID],
        self::CALL_FILTER2 => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT, OpInfo::ARG_SLOT, OpInfo::ARG_FILTER_ID],
        self::CALL_SLOT0_FILTER2 => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT, OpInfo::ARG_FILTER_ID],
        self::CALL_FUNC0 => [OpInfo::ARG_SLOT, OpInfo::ARG_FUNC_ID],
        self::CALL_SLOT0_FUNC0 => [OpInfo::ARG_FUNC_ID],
        self::CALL_FUNC1 => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT, OpInfo::ARG_FUNC_ID],
        self::CALL_SLOT0_FUNC1 => [OpInfo::ARG_SLOT, OpInfo::ARG_FUNC_ID],
        self::CALL_FUNC2 => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT, OpInfo::ARG_SLOT, OpInfo::ARG_FUNC_ID],
        self::CALL_SLOT0_FUNC2 => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT, OpInfo::ARG_FUNC_ID],
        self::CALL_FUNC3 => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT, OpInfo::ARG_SLOT, OpInfo::ARG_SLOT, OpInfo::ARG_FUNC_ID],
        self::CALL_SLOT0_FUNC3 => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT, OpInfo::ARG_SLOT, OpInfo::ARG_FUNC_ID],
        self::LENGTH_FILTER => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::LENGTH_SLOT0_FILTER => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::DEFAULT_FILTER => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::DEFAULT_SLOT0_FILTER => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::NOT => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::NOT_SLOT0 => [OpInfo::ARG_SLOT],
        self::NEG => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::NEG_SLOT0 => [OpInfo::ARG_SLOT],
        self::OR => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::OR_SLOT0 => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::AND => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::AND_SLOT0 => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::CONCAT => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::CONCAT_SLOT0 => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::EQ => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::EQ_SLOT0 => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::LT => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::LT_SLOT0 => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::LT_EQ => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::LT_EQ_SLOT0 => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::NOT_EQ => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::NOT_EQ_SLOT0 => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::ADD => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::ADD_SLOT0 => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::SUB => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::SUB_SLOT0 => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::MUL => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::MUL_SLOT0 => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::QUO => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::QUO_SLOT0 => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::MOD => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::MOD_SLOT0 => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
    ];
}
