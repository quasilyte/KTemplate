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
    
    // Encoding: 0x09 val:intindex
    // Flags: FLAG_IMPLICIT_SLOT0
    public const LOAD_SLOT0_INT_CONST = 9;
    
    // Encoding: 0x0a dst:wslot val:intindex
    public const LOAD_INT_CONST = 10;
    
    // Encoding: 0x0b dst:wslot val:strindex
    public const LOAD_STRING_CONST = 11;
    
    // Encoding: 0x0c dst:wslot p1:strindex
    public const LOAD_VAR_1 = 12;
    
    // Encoding: 0x0d dst:wslot p1:strindex p2:strindex
    public const LOAD_VAR_2 = 13;
    
    // Encoding: 0x0e dst:wslot p1:strindex p2:strindex p3:strindex
    public const LOAD_VAR_3 = 14;
    
    // Encoding: 0x0f arg2:rslot
    // Flags: FLAG_IMPLICIT_SLOT0
    public const CONCAT_SLOT0_2 = 15;
    
    // Encoding: 0x10 arg2:rslot arg3:rslot
    // Flags: FLAG_IMPLICIT_SLOT0
    public const CONCAT_SLOT0_3 = 16;
    
    // Encoding: 0x11 pcdelta:rel8
    public const JUMP = 17;
    
    // Encoding: 0x12 pcdelta:rel8
    // Flags: FLAG_IMPLICIT_SLOT0
    public const JUMP_ZERO = 18;
    
    // Encoding: 0x13 pcdelta:rel8
    // Flags: FLAG_IMPLICIT_SLOT0
    public const JUMP_NOT_ZERO = 19;
    
    // Encoding: 0x14 dst:wslot arg1:rslot arg2:rslot
    public const ADD = 20;
    
    // Encoding: 0x15 arg1:rslot arg2:rslot
    // Flags: FLAG_IMPLICIT_SLOT0
    public const ADD_SLOT0 = 21;
    
    // Encoding: 0x16 dst:wslot arg1:rslot arg2:rslot
    public const MUL = 22;
    
    // Encoding: 0x17 arg1:rslot arg2:rslot
    // Flags: FLAG_IMPLICIT_SLOT0
    public const MUL_SLOT0 = 23;
    
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
            return 'LOAD_SLOT0_INT_CONST';
        case 10:
            return 'LOAD_INT_CONST';
        case 11:
            return 'LOAD_STRING_CONST';
        case 12:
            return 'LOAD_VAR_1';
        case 13:
            return 'LOAD_VAR_2';
        case 14:
            return 'LOAD_VAR_3';
        case 15:
            return 'CONCAT_SLOT0_2';
        case 16:
            return 'CONCAT_SLOT0_3';
        case 17:
            return 'JUMP';
        case 18:
            return 'JUMP_ZERO';
        case 19:
            return 'JUMP_NOT_ZERO';
        case 20:
            return 'ADD';
        case 21:
            return 'ADD_SLOT0';
        case 22:
            return 'MUL';
        case 23:
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
        case 9: // LOAD_SLOT0_INT_CONST
            return OpInfo::FLAG_IMPLICIT_SLOT0;
        case 10: // LOAD_INT_CONST
            return 0;
        case 11: // LOAD_STRING_CONST
            return 0;
        case 12: // LOAD_VAR_1
            return 0;
        case 13: // LOAD_VAR_2
            return 0;
        case 14: // LOAD_VAR_3
            return 0;
        case 15: // CONCAT_SLOT0_2
            return OpInfo::FLAG_IMPLICIT_SLOT0;
        case 16: // CONCAT_SLOT0_3
            return OpInfo::FLAG_IMPLICIT_SLOT0;
        case 17: // JUMP
            return 0;
        case 18: // JUMP_ZERO
            return OpInfo::FLAG_IMPLICIT_SLOT0;
        case 19: // JUMP_NOT_ZERO
            return OpInfo::FLAG_IMPLICIT_SLOT0;
        case 20: // ADD
            return 0;
        case 21: // ADD_SLOT0
            return OpInfo::FLAG_IMPLICIT_SLOT0;
        case 22: // MUL
            return 0;
        case 23: // MUL_SLOT0
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
        self::LOAD_SLOT0_INT_CONST => [OpInfo::ARG_INT_CONST],
        self::LOAD_INT_CONST => [OpInfo::ARG_SLOT, OpInfo::ARG_INT_CONST],
        self::LOAD_STRING_CONST => [OpInfo::ARG_SLOT, OpInfo::ARG_STRING_CONST],
        self::LOAD_VAR_1 => [OpInfo::ARG_SLOT, OpInfo::ARG_STRING_CONST],
        self::LOAD_VAR_2 => [OpInfo::ARG_SLOT, OpInfo::ARG_STRING_CONST, OpInfo::ARG_STRING_CONST],
        self::LOAD_VAR_3 => [OpInfo::ARG_SLOT, OpInfo::ARG_STRING_CONST, OpInfo::ARG_STRING_CONST, OpInfo::ARG_STRING_CONST],
        self::CONCAT_SLOT0_2 => [OpInfo::ARG_SLOT],
        self::CONCAT_SLOT0_3 => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::JUMP => [OpInfo::ARG_REL8],
        self::JUMP_ZERO => [OpInfo::ARG_REL8],
        self::JUMP_NOT_ZERO => [OpInfo::ARG_REL8],
        self::ADD => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::ADD_SLOT0 => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::MUL => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::MUL_SLOT0 => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
    ];
}
