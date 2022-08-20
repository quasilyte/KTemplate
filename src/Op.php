<?php

namespace KTemplate;

class Op {
    public const UNKNOWN = 0;
    
    // Encoding: 0x01
    public const RETURN = 1;
    
    // Encoding: 0x02
    // Flags: FLAG_IMPLICIT_SLOT0
    public const OUTPUT_SLOT0 = 2;
    
    // Encoding: 0x03 arg:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    public const OUTPUT = 3;
    
    // Encoding: 0x04 val:intindex
    public const OUTPUT_INT_CONST = 4;
    
    // Encoding: 0x05 val:strindex
    public const OUTPUT_STRING_CONST = 5;
    
    // Encoding: 0x06 cache:cacheslot k:keyoffset
    public const OUTPUT_EXTDATA_1 = 6;
    
    // Encoding: 0x07 cache:cacheslot k:keyoffset
    public const OUTPUT_EXTDATA_2 = 7;
    
    // Encoding: 0x08 cache:cacheslot k:keyoffset
    public const OUTPUT_EXTDATA_3 = 8;
    
    // Encoding: 0x09 dst:wslot val:imm8
    // Flags: FLAG_HAS_SLOT_ARG
    public const LOAD_BOOL = 9;
    
    // Encoding: 0x0a val:imm8
    // Flags: FLAG_IMPLICIT_SLOT0
    public const LOAD_SLOT0_BOOL = 10;
    
    // Encoding: 0x0b dst:wslot val:intindex
    // Flags: FLAG_HAS_SLOT_ARG
    public const LOAD_INT_CONST = 11;
    
    // Encoding: 0x0c val:intindex
    // Flags: FLAG_IMPLICIT_SLOT0
    public const LOAD_SLOT0_INT_CONST = 12;
    
    // Encoding: 0x0d dst:wslot val:strindex
    // Flags: FLAG_HAS_SLOT_ARG
    public const LOAD_STRING_CONST = 13;
    
    // Encoding: 0x0e val:strindex
    // Flags: FLAG_IMPLICIT_SLOT0
    public const LOAD_SLOT0_STRING_CONST = 14;
    
    // Encoding: 0x0f dst:wslot cache:cacheslot k:keyoffset
    // Flags: FLAG_HAS_SLOT_ARG
    public const LOAD_EXTDATA_1 = 15;
    
    // Encoding: 0x10 cache:cacheslot k:keyoffset
    // Flags: FLAG_IMPLICIT_SLOT0
    public const LOAD_SLOT0_EXTDATA_1 = 16;
    
    // Encoding: 0x11 dst:wslot cache:cacheslot k:keyoffset
    // Flags: FLAG_HAS_SLOT_ARG
    public const LOAD_EXTDATA_2 = 17;
    
    // Encoding: 0x12 cache:cacheslot k:keyoffset
    // Flags: FLAG_IMPLICIT_SLOT0
    public const LOAD_SLOT0_EXTDATA_2 = 18;
    
    // Encoding: 0x13 dst:wslot cache:cacheslot k:keyoffset
    // Flags: FLAG_HAS_SLOT_ARG
    public const LOAD_EXTDATA_3 = 19;
    
    // Encoding: 0x14 cache:cacheslot k:keyoffset
    // Flags: FLAG_IMPLICIT_SLOT0
    public const LOAD_SLOT0_EXTDATA_3 = 20;
    
    // Encoding: 0x15 dst:wslot
    // Flags: FLAG_HAS_SLOT_ARG
    public const LOAD_NULL = 21;
    
    // Encoding: 0x16
    public const LOAD_SLOT0_NULL = 22;
    
    // Encoding: 0x17 dst:wslot src:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    public const MOVE_BOOL = 23;
    
    // Encoding: 0x18 src:rslot
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    public const MOVE_SLOT0_BOOL = 24;
    
    // Encoding: 0x19 arg:wslot
    // Flags: FLAG_HAS_SLOT_ARG
    public const CONV_BOOL = 25;
    
    // Encoding: 0x1a
    // Flags: FLAG_IMPLICIT_SLOT0
    public const CONV_SLOT0_BOOL = 26;
    
    // Encoding: 0x1b pcdelta:rel8
    public const JUMP = 27;
    
    // Encoding: 0x1c pcdelta:rel8 cond:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    public const JUMP_FALSY = 28;
    
    // Encoding: 0x1d pcdelta:rel8
    // Flags: FLAG_IMPLICIT_SLOT0
    public const JUMP_SLOT0_FALSY = 29;
    
    // Encoding: 0x1e pcdelta:rel8 cond:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    public const JUMP_TRUTHY = 30;
    
    // Encoding: 0x1f pcdelta:rel8
    // Flags: FLAG_IMPLICIT_SLOT0
    public const JUMP_SLOT0_TRUTHY = 31;
    
    // Encoding: 0x20 dst:wslot arg1:rslot fn:filterid
    // Flags: FLAG_HAS_SLOT_ARG
    public const CALL_FILTER1 = 32;
    
    // Encoding: 0x21 arg1:rslot fn:filterid
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    public const CALL_SLOT0_FILTER1 = 33;
    
    // Encoding: 0x22 dst:wslot arg1:rslot arg2:rslot fn:filterid
    // Flags: FLAG_HAS_SLOT_ARG
    public const CALL_FILTER2 = 34;
    
    // Encoding: 0x23 arg1:rslot arg2:rslot fn:filterid
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    public const CALL_SLOT0_FILTER2 = 35;
    
    // Encoding: 0x24 dst:wslot fn:funcid
    // Flags: FLAG_HAS_SLOT_ARG
    public const CALL_FUNC0 = 36;
    
    // Encoding: 0x25 fn:funcid
    // Flags: FLAG_IMPLICIT_SLOT0
    public const CALL_SLOT0_FUNC0 = 37;
    
    // Encoding: 0x26 dst:wslot arg1:rslot fn:funcid
    // Flags: FLAG_HAS_SLOT_ARG
    public const CALL_FUNC1 = 38;
    
    // Encoding: 0x27 arg1:rslot fn:funcid
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    public const CALL_SLOT0_FUNC1 = 39;
    
    // Encoding: 0x28 dst:wslot arg1:rslot arg2:rslot fn:funcid
    // Flags: FLAG_HAS_SLOT_ARG
    public const CALL_FUNC2 = 40;
    
    // Encoding: 0x29 arg1:rslot arg2:rslot fn:funcid
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    public const CALL_SLOT0_FUNC2 = 41;
    
    // Encoding: 0x2a dst:wslot arg1:rslot arg2:rslot arg3:rslot fn:funcid
    // Flags: FLAG_HAS_SLOT_ARG
    public const CALL_FUNC3 = 42;
    
    // Encoding: 0x2b arg1:rslot arg2:rslot arg3:rslot fn:funcid
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    public const CALL_SLOT0_FUNC3 = 43;
    
    // Encoding: 0x2c dst:wslot arg1:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    public const LENGTH_FILTER = 44;
    
    // Encoding: 0x2d dst:wslot arg1:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    public const LENGTH_SLOT0_FILTER = 45;
    
    // Encoding: 0x2e dst:wslot arg:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    public const NOT = 46;
    
    // Encoding: 0x2f arg:rslot
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    public const NOT_SLOT0 = 47;
    
    // Encoding: 0x30 dst:wslot arg:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    public const NEG = 48;
    
    // Encoding: 0x31 arg:rslot
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    public const NEG_SLOT0 = 49;
    
    // Encoding: 0x32 dst:wslot arg1:rslot arg2:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    public const OR = 50;
    
    // Encoding: 0x33 arg1:rslot arg2:rslot
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    public const OR_SLOT0 = 51;
    
    // Encoding: 0x34 dst:wslot arg1:rslot arg2:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    public const AND = 52;
    
    // Encoding: 0x35 arg1:rslot arg2:rslot
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    public const AND_SLOT0 = 53;
    
    // Encoding: 0x36 dst:wslot arg1:rslot arg2:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    public const CONCAT = 54;
    
    // Encoding: 0x37 arg1:rslot arg2:rslot
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    public const CONCAT_SLOT0 = 55;
    
    // Encoding: 0x38 dst:wslot arg1:rslot arg2:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    public const EQ = 56;
    
    // Encoding: 0x39 arg1:rslot arg2:rslot
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    public const EQ_SLOT0 = 57;
    
    // Encoding: 0x3a dst:wslot arg1:rslot arg2:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    public const GT = 58;
    
    // Encoding: 0x3b arg1:rslot arg2:rslot
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    public const GT_SLOT0 = 59;
    
    // Encoding: 0x3c dst:wslot arg1:rslot arg2:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    public const LT = 60;
    
    // Encoding: 0x3d arg1:rslot arg2:rslot
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    public const LT_SLOT0 = 61;
    
    // Encoding: 0x3e dst:wslot arg1:rslot arg2:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    public const NOT_EQ = 62;
    
    // Encoding: 0x3f arg1:rslot arg2:rslot
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    public const NOT_EQ_SLOT0 = 63;
    
    // Encoding: 0x40 dst:wslot arg1:rslot arg2:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    public const ADD = 64;
    
    // Encoding: 0x41 arg1:rslot arg2:rslot
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    public const ADD_SLOT0 = 65;
    
    // Encoding: 0x42 dst:wslot arg1:rslot arg2:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    public const SUB = 66;
    
    // Encoding: 0x43 arg1:rslot arg2:rslot
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    public const SUB_SLOT0 = 67;
    
    // Encoding: 0x44 dst:wslot arg1:rslot arg2:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    public const MUL = 68;
    
    // Encoding: 0x45 arg1:rslot arg2:rslot
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    public const MUL_SLOT0 = 69;
    
    public static function opcodeString(int $op): string {
        switch ($op) {
        case 1:
            return 'RETURN';
        case 2:
            return 'OUTPUT_SLOT0';
        case 3:
            return 'OUTPUT';
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
            return 'LOAD_STRING_CONST';
        case 14:
            return 'LOAD_SLOT0_STRING_CONST';
        case 15:
            return 'LOAD_EXTDATA_1';
        case 16:
            return 'LOAD_SLOT0_EXTDATA_1';
        case 17:
            return 'LOAD_EXTDATA_2';
        case 18:
            return 'LOAD_SLOT0_EXTDATA_2';
        case 19:
            return 'LOAD_EXTDATA_3';
        case 20:
            return 'LOAD_SLOT0_EXTDATA_3';
        case 21:
            return 'LOAD_NULL';
        case 22:
            return 'LOAD_SLOT0_NULL';
        case 23:
            return 'MOVE_BOOL';
        case 24:
            return 'MOVE_SLOT0_BOOL';
        case 25:
            return 'CONV_BOOL';
        case 26:
            return 'CONV_SLOT0_BOOL';
        case 27:
            return 'JUMP';
        case 28:
            return 'JUMP_FALSY';
        case 29:
            return 'JUMP_SLOT0_FALSY';
        case 30:
            return 'JUMP_TRUTHY';
        case 31:
            return 'JUMP_SLOT0_TRUTHY';
        case 32:
            return 'CALL_FILTER1';
        case 33:
            return 'CALL_SLOT0_FILTER1';
        case 34:
            return 'CALL_FILTER2';
        case 35:
            return 'CALL_SLOT0_FILTER2';
        case 36:
            return 'CALL_FUNC0';
        case 37:
            return 'CALL_SLOT0_FUNC0';
        case 38:
            return 'CALL_FUNC1';
        case 39:
            return 'CALL_SLOT0_FUNC1';
        case 40:
            return 'CALL_FUNC2';
        case 41:
            return 'CALL_SLOT0_FUNC2';
        case 42:
            return 'CALL_FUNC3';
        case 43:
            return 'CALL_SLOT0_FUNC3';
        case 44:
            return 'LENGTH_FILTER';
        case 45:
            return 'LENGTH_SLOT0_FILTER';
        case 46:
            return 'NOT';
        case 47:
            return 'NOT_SLOT0';
        case 48:
            return 'NEG';
        case 49:
            return 'NEG_SLOT0';
        case 50:
            return 'OR';
        case 51:
            return 'OR_SLOT0';
        case 52:
            return 'AND';
        case 53:
            return 'AND_SLOT0';
        case 54:
            return 'CONCAT';
        case 55:
            return 'CONCAT_SLOT0';
        case 56:
            return 'EQ';
        case 57:
            return 'EQ_SLOT0';
        case 58:
            return 'GT';
        case 59:
            return 'GT_SLOT0';
        case 60:
            return 'LT';
        case 61:
            return 'LT_SLOT0';
        case 62:
            return 'NOT_EQ';
        case 63:
            return 'NOT_EQ_SLOT0';
        case 64:
            return 'ADD';
        case 65:
            return 'ADD_SLOT0';
        case 66:
            return 'SUB';
        case 67:
            return 'SUB_SLOT0';
        case 68:
            return 'MUL';
        case 69:
            return 'MUL_SLOT0';
        default:
            return '?';
        }
    }

    public static function opcodeFlags(int $op): int {
        switch ($op) {
        case 1: // RETURN
            return 0;
        case 2: // OUTPUT_SLOT0
            return OpInfo::FLAG_IMPLICIT_SLOT0;
        case 3: // OUTPUT
            return OpInfo::FLAG_HAS_SLOT_ARG;
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
        case 13: // LOAD_STRING_CONST
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 14: // LOAD_SLOT0_STRING_CONST
            return OpInfo::FLAG_IMPLICIT_SLOT0;
        case 15: // LOAD_EXTDATA_1
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 16: // LOAD_SLOT0_EXTDATA_1
            return OpInfo::FLAG_IMPLICIT_SLOT0;
        case 17: // LOAD_EXTDATA_2
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 18: // LOAD_SLOT0_EXTDATA_2
            return OpInfo::FLAG_IMPLICIT_SLOT0;
        case 19: // LOAD_EXTDATA_3
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 20: // LOAD_SLOT0_EXTDATA_3
            return OpInfo::FLAG_IMPLICIT_SLOT0;
        case 21: // LOAD_NULL
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 22: // LOAD_SLOT0_NULL
            return 0;
        case 23: // MOVE_BOOL
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 24: // MOVE_SLOT0_BOOL
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 25: // CONV_BOOL
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 26: // CONV_SLOT0_BOOL
            return OpInfo::FLAG_IMPLICIT_SLOT0;
        case 27: // JUMP
            return 0;
        case 28: // JUMP_FALSY
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 29: // JUMP_SLOT0_FALSY
            return OpInfo::FLAG_IMPLICIT_SLOT0;
        case 30: // JUMP_TRUTHY
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 31: // JUMP_SLOT0_TRUTHY
            return OpInfo::FLAG_IMPLICIT_SLOT0;
        case 32: // CALL_FILTER1
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 33: // CALL_SLOT0_FILTER1
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 34: // CALL_FILTER2
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 35: // CALL_SLOT0_FILTER2
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 36: // CALL_FUNC0
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 37: // CALL_SLOT0_FUNC0
            return OpInfo::FLAG_IMPLICIT_SLOT0;
        case 38: // CALL_FUNC1
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 39: // CALL_SLOT0_FUNC1
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 40: // CALL_FUNC2
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 41: // CALL_SLOT0_FUNC2
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 42: // CALL_FUNC3
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 43: // CALL_SLOT0_FUNC3
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 44: // LENGTH_FILTER
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 45: // LENGTH_SLOT0_FILTER
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 46: // NOT
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 47: // NOT_SLOT0
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 48: // NEG
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 49: // NEG_SLOT0
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 50: // OR
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 51: // OR_SLOT0
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 52: // AND
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 53: // AND_SLOT0
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 54: // CONCAT
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 55: // CONCAT_SLOT0
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 56: // EQ
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 57: // EQ_SLOT0
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 58: // GT
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 59: // GT_SLOT0
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 60: // LT
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 61: // LT_SLOT0
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 62: // NOT_EQ
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 63: // NOT_EQ_SLOT0
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 64: // ADD
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 65: // ADD_SLOT0
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 66: // SUB
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 67: // SUB_SLOT0
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 68: // MUL
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 69: // MUL_SLOT0
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        default:
            return 0;
        }
    }

    public static $args = [
        self::RETURN => [],
        self::OUTPUT_SLOT0 => [],
        self::OUTPUT => [OpInfo::ARG_SLOT],
        self::OUTPUT_INT_CONST => [OpInfo::ARG_INT_CONST],
        self::OUTPUT_STRING_CONST => [OpInfo::ARG_STRING_CONST],
        self::OUTPUT_EXTDATA_1 => [OpInfo::ARG_CACHE_SLOT, OpInfo::ARG_KEY_OFFSET],
        self::OUTPUT_EXTDATA_2 => [OpInfo::ARG_CACHE_SLOT, OpInfo::ARG_KEY_OFFSET],
        self::OUTPUT_EXTDATA_3 => [OpInfo::ARG_CACHE_SLOT, OpInfo::ARG_KEY_OFFSET],
        self::LOAD_BOOL => [OpInfo::ARG_SLOT, OpInfo::ARG_IMM8],
        self::LOAD_SLOT0_BOOL => [OpInfo::ARG_IMM8],
        self::LOAD_INT_CONST => [OpInfo::ARG_SLOT, OpInfo::ARG_INT_CONST],
        self::LOAD_SLOT0_INT_CONST => [OpInfo::ARG_INT_CONST],
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
        self::MOVE_BOOL => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::MOVE_SLOT0_BOOL => [OpInfo::ARG_SLOT],
        self::CONV_BOOL => [OpInfo::ARG_SLOT],
        self::CONV_SLOT0_BOOL => [],
        self::JUMP => [OpInfo::ARG_REL8],
        self::JUMP_FALSY => [OpInfo::ARG_REL8, OpInfo::ARG_SLOT],
        self::JUMP_SLOT0_FALSY => [OpInfo::ARG_REL8],
        self::JUMP_TRUTHY => [OpInfo::ARG_REL8, OpInfo::ARG_SLOT],
        self::JUMP_SLOT0_TRUTHY => [OpInfo::ARG_REL8],
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
        self::GT => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::GT_SLOT0 => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::LT => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::LT_SLOT0 => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::NOT_EQ => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::NOT_EQ_SLOT0 => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::ADD => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::ADD_SLOT0 => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::SUB => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::SUB_SLOT0 => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::MUL => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::MUL_SLOT0 => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
    ];
}
