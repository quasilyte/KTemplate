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
    public const OUTPUT = 3;
    
    // Encoding: 0x04 val:intindex
    public const OUTPUT_INT_CONST = 4;
    
    // Encoding: 0x05 val:strindex
    public const OUTPUT_STRING_CONST = 5;
    
    // Encoding: 0x06 p1:strindex
    public const OUTPUT_VAR_1 = 6;
    
    // Encoding: 0x07 p1:strindex p2:strindex
    public const OUTPUT_VAR_2 = 7;
    
    // Encoding: 0x08 p1:strindex p2:strindex p3:strindex
    public const OUTPUT_VAR_3 = 8;
    
    // Encoding: 0x09 dst:wslot val:imm8
    public const LOAD_BOOL = 9;
    
    // Encoding: 0x0a val:imm8
    // Flags: FLAG_IMPLICIT_SLOT0
    public const LOAD_SLOT0_BOOL = 10;
    
    // Encoding: 0x0b dst:wslot val:intindex
    public const LOAD_INT_CONST = 11;
    
    // Encoding: 0x0c val:intindex
    // Flags: FLAG_IMPLICIT_SLOT0
    public const LOAD_SLOT0_INT_CONST = 12;
    
    // Encoding: 0x0d dst:wslot val:strindex
    public const LOAD_STRING_CONST = 13;
    
    // Encoding: 0x0e val:strindex
    // Flags: FLAG_IMPLICIT_SLOT0
    public const LOAD_SLOT0_STRING_CONST = 14;
    
    // Encoding: 0x0f dst:wslot p1:strindex
    public const LOAD_VAR_1 = 15;
    
    // Encoding: 0x10 dst:wslot p1:strindex p2:strindex
    public const LOAD_VAR_2 = 16;
    
    // Encoding: 0x11 dst:wslot p1:strindex p2:strindex p3:strindex
    public const LOAD_VAR_3 = 17;
    
    // Encoding: 0x12 dst:wslot
    public const LOAD_NULL = 18;
    
    // Encoding: 0x13
    public const LOAD_SLOT0_NULL = 19;
    
    // Encoding: 0x14 pcdelta:rel8
    public const JUMP = 20;
    
    // Encoding: 0x15 pcdelta:rel8
    // Flags: FLAG_IMPLICIT_SLOT0
    public const JUMP_ZERO = 21;
    
    // Encoding: 0x16 pcdelta:rel8
    // Flags: FLAG_IMPLICIT_SLOT0
    public const JUMP_NOT_ZERO = 22;
    
    // Encoding: 0x17 dst:wslot arg1:rslot arg2:rslot
    public const CONCAT = 23;
    
    // Encoding: 0x18 arg1:rslot arg2:rslot
    // Flags: FLAG_IMPLICIT_SLOT0
    public const CONCAT_SLOT0 = 24;
    
    // Encoding: 0x19 dst:wslot arg1:rslot arg2:rslot
    public const EQ = 25;
    
    // Encoding: 0x1a arg1:rslot arg2:rslot
    // Flags: FLAG_IMPLICIT_SLOT0
    public const EQ_SLOT0 = 26;
    
    // Encoding: 0x1b dst:wslot arg1:rslot arg2:rslot
    public const GT = 27;
    
    // Encoding: 0x1c arg1:rslot arg2:rslot
    // Flags: FLAG_IMPLICIT_SLOT0
    public const GT_SLOT0 = 28;
    
    // Encoding: 0x1d dst:wslot arg1:rslot arg2:rslot
    public const LT = 29;
    
    // Encoding: 0x1e arg1:rslot arg2:rslot
    // Flags: FLAG_IMPLICIT_SLOT0
    public const LT_SLOT0 = 30;
    
    // Encoding: 0x1f dst:wslot arg1:rslot arg2:rslot
    public const NOT_EQ = 31;
    
    // Encoding: 0x20 arg1:rslot arg2:rslot
    // Flags: FLAG_IMPLICIT_SLOT0
    public const NOT_EQ_SLOT0 = 32;
    
    // Encoding: 0x21 dst:wslot arg1:rslot arg2:rslot
    public const ADD = 33;
    
    // Encoding: 0x22 arg1:rslot arg2:rslot
    // Flags: FLAG_IMPLICIT_SLOT0
    public const ADD_SLOT0 = 34;
    
    // Encoding: 0x23 dst:wslot arg1:rslot arg2:rslot
    public const MUL = 35;
    
    // Encoding: 0x24 arg1:rslot arg2:rslot
    // Flags: FLAG_IMPLICIT_SLOT0
    public const MUL_SLOT0 = 36;
    
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
            return 'OUTPUT_VAR_1';
        case 7:
            return 'OUTPUT_VAR_2';
        case 8:
            return 'OUTPUT_VAR_3';
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
            return 'LOAD_VAR_1';
        case 16:
            return 'LOAD_VAR_2';
        case 17:
            return 'LOAD_VAR_3';
        case 18:
            return 'LOAD_NULL';
        case 19:
            return 'LOAD_SLOT0_NULL';
        case 20:
            return 'JUMP';
        case 21:
            return 'JUMP_ZERO';
        case 22:
            return 'JUMP_NOT_ZERO';
        case 23:
            return 'CONCAT';
        case 24:
            return 'CONCAT_SLOT0';
        case 25:
            return 'EQ';
        case 26:
            return 'EQ_SLOT0';
        case 27:
            return 'GT';
        case 28:
            return 'GT_SLOT0';
        case 29:
            return 'LT';
        case 30:
            return 'LT_SLOT0';
        case 31:
            return 'NOT_EQ';
        case 32:
            return 'NOT_EQ_SLOT0';
        case 33:
            return 'ADD';
        case 34:
            return 'ADD_SLOT0';
        case 35:
            return 'MUL';
        case 36:
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
            return 0;
        case 4: // OUTPUT_INT_CONST
            return 0;
        case 5: // OUTPUT_STRING_CONST
            return 0;
        case 6: // OUTPUT_VAR_1
            return 0;
        case 7: // OUTPUT_VAR_2
            return 0;
        case 8: // OUTPUT_VAR_3
            return 0;
        case 9: // LOAD_BOOL
            return 0;
        case 10: // LOAD_SLOT0_BOOL
            return OpInfo::FLAG_IMPLICIT_SLOT0;
        case 11: // LOAD_INT_CONST
            return 0;
        case 12: // LOAD_SLOT0_INT_CONST
            return OpInfo::FLAG_IMPLICIT_SLOT0;
        case 13: // LOAD_STRING_CONST
            return 0;
        case 14: // LOAD_SLOT0_STRING_CONST
            return OpInfo::FLAG_IMPLICIT_SLOT0;
        case 15: // LOAD_VAR_1
            return 0;
        case 16: // LOAD_VAR_2
            return 0;
        case 17: // LOAD_VAR_3
            return 0;
        case 18: // LOAD_NULL
            return 0;
        case 19: // LOAD_SLOT0_NULL
            return 0;
        case 20: // JUMP
            return 0;
        case 21: // JUMP_ZERO
            return OpInfo::FLAG_IMPLICIT_SLOT0;
        case 22: // JUMP_NOT_ZERO
            return OpInfo::FLAG_IMPLICIT_SLOT0;
        case 23: // CONCAT
            return 0;
        case 24: // CONCAT_SLOT0
            return OpInfo::FLAG_IMPLICIT_SLOT0;
        case 25: // EQ
            return 0;
        case 26: // EQ_SLOT0
            return OpInfo::FLAG_IMPLICIT_SLOT0;
        case 27: // GT
            return 0;
        case 28: // GT_SLOT0
            return OpInfo::FLAG_IMPLICIT_SLOT0;
        case 29: // LT
            return 0;
        case 30: // LT_SLOT0
            return OpInfo::FLAG_IMPLICIT_SLOT0;
        case 31: // NOT_EQ
            return 0;
        case 32: // NOT_EQ_SLOT0
            return OpInfo::FLAG_IMPLICIT_SLOT0;
        case 33: // ADD
            return 0;
        case 34: // ADD_SLOT0
            return OpInfo::FLAG_IMPLICIT_SLOT0;
        case 35: // MUL
            return 0;
        case 36: // MUL_SLOT0
            return OpInfo::FLAG_IMPLICIT_SLOT0;
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
        self::OUTPUT_VAR_1 => [OpInfo::ARG_STRING_CONST],
        self::OUTPUT_VAR_2 => [OpInfo::ARG_STRING_CONST, OpInfo::ARG_STRING_CONST],
        self::OUTPUT_VAR_3 => [OpInfo::ARG_STRING_CONST, OpInfo::ARG_STRING_CONST, OpInfo::ARG_STRING_CONST],
        self::LOAD_BOOL => [OpInfo::ARG_SLOT, OpInfo::ARG_IMM8],
        self::LOAD_SLOT0_BOOL => [OpInfo::ARG_IMM8],
        self::LOAD_INT_CONST => [OpInfo::ARG_SLOT, OpInfo::ARG_INT_CONST],
        self::LOAD_SLOT0_INT_CONST => [OpInfo::ARG_INT_CONST],
        self::LOAD_STRING_CONST => [OpInfo::ARG_SLOT, OpInfo::ARG_STRING_CONST],
        self::LOAD_SLOT0_STRING_CONST => [OpInfo::ARG_STRING_CONST],
        self::LOAD_VAR_1 => [OpInfo::ARG_SLOT, OpInfo::ARG_STRING_CONST],
        self::LOAD_VAR_2 => [OpInfo::ARG_SLOT, OpInfo::ARG_STRING_CONST, OpInfo::ARG_STRING_CONST],
        self::LOAD_VAR_3 => [OpInfo::ARG_SLOT, OpInfo::ARG_STRING_CONST, OpInfo::ARG_STRING_CONST, OpInfo::ARG_STRING_CONST],
        self::LOAD_NULL => [OpInfo::ARG_SLOT],
        self::LOAD_SLOT0_NULL => [],
        self::JUMP => [OpInfo::ARG_REL8],
        self::JUMP_ZERO => [OpInfo::ARG_REL8],
        self::JUMP_NOT_ZERO => [OpInfo::ARG_REL8],
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
