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
    
    // Encoding: 0x17 pcdelta:rel8
    public const JUMP = 23;
    
    // Encoding: 0x18 pcdelta:rel8
    // Flags: FLAG_IMPLICIT_SLOT0
    public const JUMP_ZERO = 24;
    
    // Encoding: 0x19 pcdelta:rel8
    // Flags: FLAG_IMPLICIT_SLOT0
    public const JUMP_NOT_ZERO = 25;
    
    // Encoding: 0x1a dst:wslot arg1:rslot fn:filterid
    // Flags: FLAG_HAS_SLOT_ARG
    public const CALL_FILTER1 = 26;
    
    // Encoding: 0x1b arg1:rslot fn:filterid
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    public const CALL_SLOT0_FILTER1 = 27;
    
    // Encoding: 0x1c dst:wslot arg1:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    public const LENGTH_FILTER = 28;
    
    // Encoding: 0x1d dst:wslot arg1:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    public const LENGTH_SLOT0_FILTER = 29;
    
    // Encoding: 0x1e dst:wslot arg:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    public const NOT = 30;
    
    // Encoding: 0x1f arg:rslot
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    public const NOT_SLOT0 = 31;
    
    // Encoding: 0x20 dst:wslot arg1:rslot arg2:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    public const CONCAT = 32;
    
    // Encoding: 0x21 arg1:rslot arg2:rslot
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    public const CONCAT_SLOT0 = 33;
    
    // Encoding: 0x22 dst:wslot arg1:rslot arg2:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    public const EQ = 34;
    
    // Encoding: 0x23 arg1:rslot arg2:rslot
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    public const EQ_SLOT0 = 35;
    
    // Encoding: 0x24 dst:wslot arg1:rslot arg2:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    public const GT = 36;
    
    // Encoding: 0x25 arg1:rslot arg2:rslot
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    public const GT_SLOT0 = 37;
    
    // Encoding: 0x26 dst:wslot arg1:rslot arg2:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    public const LT = 38;
    
    // Encoding: 0x27 arg1:rslot arg2:rslot
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    public const LT_SLOT0 = 39;
    
    // Encoding: 0x28 dst:wslot arg1:rslot arg2:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    public const NOT_EQ = 40;
    
    // Encoding: 0x29 arg1:rslot arg2:rslot
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    public const NOT_EQ_SLOT0 = 41;
    
    // Encoding: 0x2a dst:wslot arg1:rslot arg2:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    public const ADD = 42;
    
    // Encoding: 0x2b arg1:rslot arg2:rslot
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    public const ADD_SLOT0 = 43;
    
    // Encoding: 0x2c dst:wslot arg1:rslot arg2:rslot
    // Flags: FLAG_HAS_SLOT_ARG
    public const MUL = 44;
    
    // Encoding: 0x2d arg1:rslot arg2:rslot
    // Flags: FLAG_IMPLICIT_SLOT0 | FLAG_HAS_SLOT_ARG
    public const MUL_SLOT0 = 45;
    
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
            return 'JUMP';
        case 24:
            return 'JUMP_ZERO';
        case 25:
            return 'JUMP_NOT_ZERO';
        case 26:
            return 'CALL_FILTER1';
        case 27:
            return 'CALL_SLOT0_FILTER1';
        case 28:
            return 'LENGTH_FILTER';
        case 29:
            return 'LENGTH_SLOT0_FILTER';
        case 30:
            return 'NOT';
        case 31:
            return 'NOT_SLOT0';
        case 32:
            return 'CONCAT';
        case 33:
            return 'CONCAT_SLOT0';
        case 34:
            return 'EQ';
        case 35:
            return 'EQ_SLOT0';
        case 36:
            return 'GT';
        case 37:
            return 'GT_SLOT0';
        case 38:
            return 'LT';
        case 39:
            return 'LT_SLOT0';
        case 40:
            return 'NOT_EQ';
        case 41:
            return 'NOT_EQ_SLOT0';
        case 42:
            return 'ADD';
        case 43:
            return 'ADD_SLOT0';
        case 44:
            return 'MUL';
        case 45:
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
        case 23: // JUMP
            return 0;
        case 24: // JUMP_ZERO
            return OpInfo::FLAG_IMPLICIT_SLOT0;
        case 25: // JUMP_NOT_ZERO
            return OpInfo::FLAG_IMPLICIT_SLOT0;
        case 26: // CALL_FILTER1
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 27: // CALL_SLOT0_FILTER1
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 28: // LENGTH_FILTER
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 29: // LENGTH_SLOT0_FILTER
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 30: // NOT
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 31: // NOT_SLOT0
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 32: // CONCAT
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 33: // CONCAT_SLOT0
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 34: // EQ
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 35: // EQ_SLOT0
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 36: // GT
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 37: // GT_SLOT0
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 38: // LT
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 39: // LT_SLOT0
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 40: // NOT_EQ
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 41: // NOT_EQ_SLOT0
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 42: // ADD
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 43: // ADD_SLOT0
            return OpInfo::FLAG_IMPLICIT_SLOT0 | OpInfo::FLAG_HAS_SLOT_ARG;
        case 44: // MUL
            return OpInfo::FLAG_HAS_SLOT_ARG;
        case 45: // MUL_SLOT0
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
        self::JUMP => [OpInfo::ARG_REL8],
        self::JUMP_ZERO => [OpInfo::ARG_REL8],
        self::JUMP_NOT_ZERO => [OpInfo::ARG_REL8],
        self::CALL_FILTER1 => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT, OpInfo::ARG_FILTER_ID],
        self::CALL_SLOT0_FILTER1 => [OpInfo::ARG_SLOT, OpInfo::ARG_FILTER_ID],
        self::LENGTH_FILTER => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::LENGTH_SLOT0_FILTER => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::NOT => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::NOT_SLOT0 => [OpInfo::ARG_SLOT],
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
        self::MUL => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::MUL_SLOT0 => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
    ];
}
