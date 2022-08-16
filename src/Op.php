<?php

namespace KTemplate;

class Op {
    public const UNKNOWN = 0;
    
    // Encoding: 0x01
    public const RETURN = 1;
    
    // Encoding: 0x02
    // Flags: FLAG_IMPLICIT_SLOT0
    public const OUTPUT_SLOT0 = 2;
    
    // Encoding: 0x03 val:intindex
    public const OUTPUT_INT_CONST = 3;
    
    // Encoding: 0x04 val:strindex
    public const OUTPUT_STRING_CONST = 4;
    
    // Encoding: 0x05 p1:strindex
    public const OUTPUT_VAR_1 = 5;
    
    // Encoding: 0x06 p1:strindex p2:strindex
    public const OUTPUT_VAR_2 = 6;
    
    // Encoding: 0x07 p1:strindex p2:strindex p3:strindex
    public const OUTPUT_VAR_3 = 7;
    
    // Encoding: 0x08 val:intindex
    // Flags: FLAG_IMPLICIT_SLOT0
    public const LOAD_SLOT0_INT_CONST = 8;
    
    // Encoding: 0x09 dst:wslot val:intindex
    public const LOAD_INT_CONST = 9;
    
    // Encoding: 0x0a dst:wslot val:strindex
    public const LOAD_STRING_CONST = 10;
    
    // Encoding: 0x0b dst:wslot p1:strindex
    public const LOAD_VAR_1 = 11;
    
    // Encoding: 0x0c dst:wslot p1:strindex p2:strindex
    public const LOAD_VAR_2 = 12;
    
    // Encoding: 0x0d dst:wslot p1:strindex p2:strindex p3:strindex
    public const LOAD_VAR_3 = 13;
    
    // Encoding: 0x0e s2:rslot
    // Flags: FLAG_IMPLICIT_SLOT0
    public const CONCAT_2 = 14;
    
    // Encoding: 0x0f s2:rslot s3:rslot
    // Flags: FLAG_IMPLICIT_SLOT0
    public const CONCAT_3 = 15;
    
    // Encoding: 0x10 pcdelta:rel8
    public const JUMP = 16;
    
    // Encoding: 0x11 pcdelta:rel8
    // Flags: FLAG_IMPLICIT_SLOT0
    public const JUMP_ZERO = 17;
    
    // Encoding: 0x12 pcdelta:rel8
    // Flags: FLAG_IMPLICIT_SLOT0
    public const JUMP_NOT_ZERO = 18;
    
    public static function opcodeString(int $op): string {
        switch ($op) {
        case 1:
            return 'RETURN';
        case 2:
            return 'OUTPUT_SLOT0';
        case 3:
            return 'OUTPUT_INT_CONST';
        case 4:
            return 'OUTPUT_STRING_CONST';
        case 5:
            return 'OUTPUT_VAR_1';
        case 6:
            return 'OUTPUT_VAR_2';
        case 7:
            return 'OUTPUT_VAR_3';
        case 8:
            return 'LOAD_SLOT0_INT_CONST';
        case 9:
            return 'LOAD_INT_CONST';
        case 10:
            return 'LOAD_STRING_CONST';
        case 11:
            return 'LOAD_VAR_1';
        case 12:
            return 'LOAD_VAR_2';
        case 13:
            return 'LOAD_VAR_3';
        case 14:
            return 'CONCAT_2';
        case 15:
            return 'CONCAT_3';
        case 16:
            return 'JUMP';
        case 17:
            return 'JUMP_ZERO';
        case 18:
            return 'JUMP_NOT_ZERO';
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
        case 3: // OUTPUT_INT_CONST
            return 0;
        case 4: // OUTPUT_STRING_CONST
            return 0;
        case 5: // OUTPUT_VAR_1
            return 0;
        case 6: // OUTPUT_VAR_2
            return 0;
        case 7: // OUTPUT_VAR_3
            return 0;
        case 8: // LOAD_SLOT0_INT_CONST
            return OpInfo::FLAG_IMPLICIT_SLOT0;
        case 9: // LOAD_INT_CONST
            return 0;
        case 10: // LOAD_STRING_CONST
            return 0;
        case 11: // LOAD_VAR_1
            return 0;
        case 12: // LOAD_VAR_2
            return 0;
        case 13: // LOAD_VAR_3
            return 0;
        case 14: // CONCAT_2
            return OpInfo::FLAG_IMPLICIT_SLOT0;
        case 15: // CONCAT_3
            return OpInfo::FLAG_IMPLICIT_SLOT0;
        case 16: // JUMP
            return 0;
        case 17: // JUMP_ZERO
            return OpInfo::FLAG_IMPLICIT_SLOT0;
        case 18: // JUMP_NOT_ZERO
            return OpInfo::FLAG_IMPLICIT_SLOT0;
        default:
            return 0;
        }
    }

    public static $args = [
        self::RETURN => [],
        self::OUTPUT_SLOT0 => [],
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
        self::CONCAT_2 => [OpInfo::ARG_SLOT],
        self::CONCAT_3 => [OpInfo::ARG_SLOT, OpInfo::ARG_SLOT],
        self::JUMP => [OpInfo::ARG_REL8],
        self::JUMP_ZERO => [OpInfo::ARG_REL8],
        self::JUMP_NOT_ZERO => [OpInfo::ARG_REL8],
    ];
}
