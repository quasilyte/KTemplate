package main

import (
	"fmt"
	"os"
	"strings"
	"text/template"
)

type opcodeTemplate struct {
	name       string
	desc       string
	resultType string
	kind       string
}

type opcodeInfo struct {
	Name       string
	Kind       string
	Opcode     byte
	Args       []argumentInfo
	ArgString  string
	Comment    string
	Enc        string
	Flags      string
	ResultType string
}

type argumentInfo struct {
	Kind string
}

var (
	boolType       = "Types::BOOL"
	intType        = "Types::INT"
	floatType      = "Types::FLOAT"
	numericType    = "Types::NUMERIC"
	stringType     = "Types::STRING"
	safeStringType = "Types::SAFE_STRING"
	nullType       = "Types::NULL"
	mixedType      = "Types::MIXED"
	unknownType    = "Types::UNKNOWN"
)

var (
	kindOther         = "KIND_OTHER"
	kindCall          = "KIND_CALL"
	kindJump          = "KIND_JUMP"
	kindSimpleAssign  = "KIND_SIMPLE_ASSIGN"
	kindComplexAssign = "KIND_COMPLEX_ASSIGN"
	kindOutput        = "KIND_OUTPUT"
)

var rawOpcodes = []opcodeTemplate{
	{"RETURN", "op", unknownType, kindOther},

	{"OUTPUT", "op arg:rslot", unknownType, kindOutput},
	{"OUTPUT_SLOT0", "op *slot0", unknownType, kindOutput},
	{"OUTPUT_SAFE", "op arg:rslot", unknownType, kindOutput},
	{"OUTPUT_SAFE_SLOT0", "op *slot0", unknownType, kindOutput},
	{"OUTPUT_STRING_CONST", "op val:strindex", unknownType, kindOutput},
	{"OUTPUT_SAFE_STRING_CONST", "op val:strindex", unknownType, kindOutput},
	{"OUTPUT_SAFE_INT_CONST", "op val:intindex", unknownType, kindOutput},
	{"OUTPUT_EXTDATA_1", "op cache:cacheslot k:keyoffset escapebit:imm8", unknownType, kindOutput},
	{"OUTPUT_EXTDATA_2", "op cache:cacheslot k:keyoffset escapebit:imm8", unknownType, kindOutput},
	{"OUTPUT_EXTDATA_3", "op cache:cacheslot k:keyoffset escapebit:imm8", unknownType, kindOutput},
	{"OUTPUT2_SAFE", "op arg1:rslot arg2:rslot", unknownType, kindOutput},

	{"LOAD_BOOL", "op dst:wslot val:imm8", boolType, kindSimpleAssign},
	{"LOAD_SLOT0_BOOL", "op *slot0 val:imm8", boolType, kindSimpleAssign},
	{"LOAD_INT_CONST", "op dst:wslot val:intindex", intType, kindSimpleAssign},
	{"LOAD_SLOT0_INT_CONST", "op *slot0 val:intindex", intType, kindSimpleAssign},
	{"LOAD_FLOAT_CONST", "op dst:wslot val:floatindex", floatType, kindSimpleAssign},
	{"LOAD_SLOT0_FLOAT_CONST", "op *slot0 val:floatindex", floatType, kindSimpleAssign},
	{"LOAD_STRING_CONST", "op dst:wslot val:strindex", stringType, kindSimpleAssign},
	{"LOAD_SLOT0_STRING_CONST", "op *slot0 val:strindex", stringType, kindSimpleAssign},
	{"LOAD_EXTDATA_1", "op dst:wslot cache:cacheslot k:keyoffset", mixedType, kindSimpleAssign},
	{"LOAD_SLOT0_EXTDATA_1", "op *slot0 cache:cacheslot k:keyoffset", mixedType, kindSimpleAssign},
	{"LOAD_EXTDATA_2", "op dst:wslot cache:cacheslot k:keyoffset", mixedType, kindSimpleAssign},
	{"LOAD_SLOT0_EXTDATA_2", "op *slot0 cache:cacheslot k:keyoffset", mixedType, kindSimpleAssign},
	{"LOAD_EXTDATA_3", "op dst:wslot cache:cacheslot k:keyoffset", mixedType, kindSimpleAssign},
	{"LOAD_SLOT0_EXTDATA_3", "op *slot0 cache:cacheslot k:keyoffset", mixedType, kindSimpleAssign},
	{"LOAD_NULL", "op dst:wslot", nullType, kindSimpleAssign},
	{"LOAD_SLOT0_NULL", "op", nullType, kindSimpleAssign},

	{"INDEX", "op dst:wslot src:rslot key:rslot", mixedType, kindComplexAssign},
	{"INDEX_SLOT0", "op *slot0 src:rslot key:rslot", mixedType, kindComplexAssign},
	{"INDEX_INT_KEY", "op dst:wslot src:rslot key:intindex", mixedType, kindComplexAssign},
	{"INDEX_SLOT0_INT_KEY", "op *slot0 src:rslot key:intindex", mixedType, kindComplexAssign},
	{"INDEX_STRING_KEY", "op dst:wslot src:rslot key:strindex", mixedType, kindComplexAssign},
	{"INDEX_SLOT0_STRING_KEY", "op *slot0 src:rslot key:strindex", mixedType, kindComplexAssign},

	{"MOVE", "op dst:wslot src:rslot", mixedType, kindSimpleAssign},
	{"MOVE_SLOT0", "op *slot0 src:rslot", mixedType, kindSimpleAssign},
	{"MOVE_BOOL", "op dst:wslot src:rslot", boolType, kindSimpleAssign},
	{"MOVE_SLOT0_BOOL", "op *slot0 src:rslot", boolType, kindSimpleAssign},

	{"CONV_BOOL", "op dst:wslot", boolType, kindSimpleAssign},
	{"CONV_SLOT0_BOOL", "op *slot0", boolType, kindSimpleAssign},

	{"JUMP", "op pcdelta:rel16", unknownType, kindJump},
	{"JUMP_FALSY", "op pcdelta:rel16 cond:rslot", unknownType, kindJump},
	{"JUMP_SLOT0_FALSY", "op *slot0 pcdelta:rel16", unknownType, kindJump},
	{"JUMP_TRUTHY", "op pcdelta:rel16 cond:rslot", unknownType, kindJump},
	{"JUMP_SLOT0_TRUTHY", "op *slot0 pcdelta:rel16", unknownType, kindJump},
	{"JUMP_NOT_NULL", "op pcdelta:rel16 cond:rslot", unknownType, kindJump},
	{"JUMP_SLOT0_NOT_NULL", "op *slot0 pcdelta:rel16 cond:rslot", unknownType, kindJump},

	{"FOR_VAL", "op *slot0 pcdelta:rel16 val:wslot", unknownType, kindJump},
	{"FOR_KEY_VAL", "op *slot0 pcdelta:rel16 key:wslot val:wslot", unknownType, kindJump},

	{"CALL_FILTER1", "op dst:wslot arg1:rslot fn:filterid", mixedType, kindCall},
	{"CALL_SLOT0_FILTER1", "op *slot0 arg1:rslot fn:filterid", mixedType, kindCall},
	{"CALL_FILTER2", "op dst:wslot arg1:rslot arg2:rslot fn:filterid", mixedType, kindCall},
	{"CALL_SLOT0_FILTER2", "op *slot0 arg1:rslot arg2:rslot fn:filterid", mixedType, kindCall},
	{"CALL_FUNC0", "op dst:wslot fn:funcid", mixedType, kindCall},
	{"CALL_SLOT0_FUNC0", "op *slot0 fn:funcid", mixedType, kindCall},
	{"CALL_FUNC1", "op dst:wslot arg1:rslot fn:funcid", mixedType, kindCall},
	{"CALL_SLOT0_FUNC1", "op *slot0 arg1:rslot fn:funcid", mixedType, kindCall},
	{"CALL_FUNC2", "op dst:wslot arg1:rslot arg2:rslot fn:funcid", mixedType, kindCall},
	{"CALL_SLOT0_FUNC2", "op *slot0 arg1:rslot arg2:rslot fn:funcid", mixedType, kindCall},
	{"CALL_FUNC3", "op dst:wslot arg1:rslot arg2:rslot arg3:rslot fn:funcid", mixedType, kindCall},
	{"CALL_SLOT0_FUNC3", "op *slot0 arg1:rslot arg2:rslot arg3:rslot fn:funcid", mixedType, kindCall},
	{"LENGTH_FILTER", "op dst:wslot arg1:rslot", intType, kindCall},
	{"LENGTH_SLOT0_FILTER", "op *slot0 arg1:rslot", intType, kindCall},
	{"DEFAULT_FILTER", "op dst:wslot arg1:rslot arg2:rslot", mixedType, kindCall},
	{"DEFAULT_SLOT0_FILTER", "op *slot0 arg1:rslot arg2:rslot", mixedType, kindCall},
	{"ESCAPE_FILTER1", "op dst:wslot src:rslot", safeStringType, kindCall},
	{"ESCAPE_SLOT0_FILTER1", "op *slot0 src:rslot", safeStringType, kindCall},
	{"ESCAPE_FILTER2", "op dst:wslot src:rslot strategy:strindex", safeStringType, kindCall},
	{"ESCAPE_SLOT0_FILTER2", "op *slot0 src:rslot strategy:strindex", safeStringType, kindCall},

	{"NOT", "op dst:wslot arg:rslot", boolType, kindSimpleAssign},
	{"NOT_SLOT0", "op *slot0 arg:rslot", boolType, kindSimpleAssign},
	{"NEG", "op dst:wslot arg:rslot", numericType, kindSimpleAssign},
	{"NEG_SLOT0", "op *slot0 arg:rslot", numericType, kindSimpleAssign},

	{"OR", "op dst:wslot arg1:rslot arg2:rslot", boolType, kindSimpleAssign},
	{"OR_SLOT0", "op *slot0 arg1:rslot arg2:rslot", boolType, kindSimpleAssign},
	{"AND", "op dst:wslot arg1:rslot arg2:rslot", boolType, kindSimpleAssign},
	{"AND_SLOT0", "op *slot0 arg1:rslot arg2:rslot", boolType, kindSimpleAssign},
	{"CONCAT", "op dst:wslot arg1:rslot arg2:rslot", stringType, kindSimpleAssign},
	{"CONCAT_SLOT0", "op *slot0 arg1:rslot arg2:rslot", stringType, kindSimpleAssign},
	{"CONCAT3", "op dst:wslot arg1:rslot arg2:rslot arg3:rslot", stringType, kindSimpleAssign},
	{"CONCAT3_SLOT0", "op *slot0 arg1:rslot arg2:rslot arg3:rslot", stringType, kindSimpleAssign},
	{"APPEND", "op dst:wslot arg:rslot", stringType, kindSimpleAssign},
	{"APPEND_SLOT0", "op *slot0 arg:rslot", stringType, kindSimpleAssign},
	{"EQ", "op dst:wslot arg1:rslot arg2:rslot", boolType, kindSimpleAssign},
	{"EQ_SLOT0", "op *slot0 arg1:rslot arg2:rslot", boolType, kindSimpleAssign},
	{"LT", "op dst:wslot arg1:rslot arg2:rslot", boolType, kindSimpleAssign},
	{"LT_SLOT0", "op *slot0 arg1:rslot arg2:rslot", boolType, kindSimpleAssign},
	{"LT_EQ", "op dst:wslot arg1:rslot arg2:rslot", boolType, kindSimpleAssign},
	{"LT_EQ_SLOT0", "op *slot0 arg1:rslot arg2:rslot", boolType, kindSimpleAssign},
	{"NOT_EQ", "op dst:wslot arg1:rslot arg2:rslot", boolType, kindSimpleAssign},
	{"NOT_EQ_SLOT0", "op *slot0 arg1:rslot arg2:rslot", boolType, kindSimpleAssign},
	{"ADD", "op dst:wslot arg1:rslot arg2:rslot", numericType, kindSimpleAssign},
	{"ADD_SLOT0", "op *slot0 arg1:rslot arg2:rslot", numericType, kindSimpleAssign},
	{"SUB", "op dst:wslot arg1:rslot arg2:rslot", numericType, kindSimpleAssign},
	{"SUB_SLOT0", "op *slot0 arg1:rslot arg2:rslot", numericType, kindSimpleAssign},
	{"MUL", "op dst:wslot arg1:rslot arg2:rslot", numericType, kindSimpleAssign},
	{"MUL_SLOT0", "op *slot0 arg1:rslot arg2:rslot", numericType, kindSimpleAssign},
	{"QUO", "op dst:wslot arg1:rslot arg2:rslot", numericType, kindSimpleAssign},
	{"QUO_SLOT0", "op *slot0 arg1:rslot arg2:rslot", numericType, kindSimpleAssign},
	{"MOD", "op dst:wslot arg1:rslot arg2:rslot", numericType, kindSimpleAssign},
	{"MOD_SLOT0", "op *slot0 arg1:rslot arg2:rslot", numericType, kindSimpleAssign},

	{"MATCHES", "op dst:wslot s:rslot regexp:strindex", boolType, kindComplexAssign},
	{"MATCHES_SLOT0", "op *slot0 s:rslot regexp:strindex", boolType, kindComplexAssign},

	{"START_TMP_OUTPUT", "op", unknownType, kindOther},
	{"FINISH_TMP_OUTPUT", "op dst:wslot", unknownType, kindOther},

	{"PREPARE_TEMPLATE", "op path:strindex", unknownType, kindOther},
	{"INCLUDE_TEMPLATE", "op", unknownType, kindOther},
}

func getOpcodeInfo(data opcodeTemplate) opcodeInfo {
	var result opcodeInfo
	result.Name = data.name
	result.Kind = data.kind
	result.ResultType = data.resultType
	if data.resultType == unknownType {
		result.ResultType = ""
	}
	if !strings.HasPrefix(data.desc, "op") {
		panic(fmt.Sprintf("%s: %s doesn't start with 'op'", data.name, data.desc))
	}
	desc := strings.TrimSpace(strings.TrimPrefix(data.desc, "op"))
	if desc == "" {
		result.Flags = "0"
		return result
	}
	var flagparts []string
	var encparts []string
	hasSlot0Arg := false
	hasSlotArg := false
	hasStringArg := false
	numDst := 0
	dstPos := -1
	argPos := 0
	for _, p := range strings.Split(desc, " ") {
		if p == "*slot0" {
			hasSlot0Arg = true
			flagparts = append(flagparts, "OpInfo::FLAG_IMPLICIT_SLOT0")
			if !strings.Contains(data.name, "SLOT0") && data.resultType != unknownType {
				panic(fmt.Sprintf("%s has slot0 arument but it's not reflected in the opcode name", data.name))
			}
			continue
		}
		argPos++
		var arg argumentInfo
		parts := strings.Split(p, ":")
		if len(parts) != 2 {
			panic(fmt.Sprintf("%s: can't split by :", data.name))
		}
		kind := parts[1]
		switch kind {
		case "wslot":
			dstPos = argPos
			numDst++
			hasSlotArg = true
			arg.Kind = "OpInfo::ARG_SLOT"
		case "rslot":
			hasSlotArg = true
			arg.Kind = "OpInfo::ARG_SLOT"
		case "cacheslot":
			arg.Kind = "OpInfo::ARG_CACHE_SLOT"
		case "keyoffset":
			arg.Kind = "OpInfo::ARG_KEY_OFFSET"
		case "strindex":
			arg.Kind = "OpInfo::ARG_STRING_CONST"
			hasStringArg = true
		case "intindex":
			arg.Kind = "OpInfo::ARG_INT_CONST"
		case "floatindex":
			arg.Kind = "OpInfo::ARG_FLOAT_CONST"
		case "rel16":
			arg.Kind = "OpInfo::ARG_REL16"
		case "imm8":
			arg.Kind = "OpInfo::ARG_IMM8"
		case "filterid":
			arg.Kind = "OpInfo::ARG_FILTER_ID"
		case "funcid":
			arg.Kind = "OpInfo::ARG_FUNC_ID"
		default:
			panic(fmt.Sprintf("%s: unexpected %s arg kind", data.name, kind))
		}
		result.Args = append(result.Args, arg)
		encparts = append(encparts, p)
	}

	if strings.Contains(data.name, "SLOT0") && !hasSlot0Arg {
		panic(fmt.Sprintf("%s: name contains SLOT0, but there is no such arg", data.name))
	}

	if hasSlotArg {
		flagparts = append(flagparts, "OpInfo::FLAG_HAS_SLOT_ARG")
	}
	if hasStringArg {
		flagparts = append(flagparts, "OpInfo::FLAG_HAS_STRING_ARG")
	}

	if data.resultType != unknownType {
		if numDst > 1 {
			panic(fmt.Sprintf("%s: more than 1 wslot", data.name))
		}
		if numDst == 1 && dstPos != 1 {
			panic(fmt.Sprintf("%s: wslot at arg pos %d", data.name, dstPos))
		}
	}

	result.Enc = strings.Join(encparts, " ")
	if len(flagparts) != 0 {
		result.Flags = strings.Join(flagparts, " | ")
	} else {
		result.Flags = "0"
	}

	return result
}

func main() {
	var opcodes []opcodeInfo
	for i, data := range rawOpcodes {
		id := i + 1
		info := getOpcodeInfo(data)
		info.Opcode = byte(id)
		enc := fmt.Sprintf("%#02x", id)
		if info.Enc != "" {
			enc += " " + info.Enc
		}
		info.Comment = fmt.Sprintf("// Encoding: %s", enc)
		if info.Flags != "0" {
			info.Comment += "\n    // Flags: " + strings.ReplaceAll(info.Flags, "OpInfo::", "")
		}
		if info.ResultType != "" {
			info.Comment += "\n    // Result type: " + info.ResultType
		} else {
			info.Comment += "\n    // Result type: unknown/varying"
		}
		if len(info.Args) != 0 {
			args := make([]string, len(info.Args))
			for i := range info.Args {
				args[i] = info.Args[i].Kind
			}
			info.ArgString = strings.Join(args, ", ")
		}
		opcodes = append(opcodes, info)
	}

	templateData := map[string]interface{}{
		"Opcodes": opcodes,
	}
	err := outputTemplate.Execute(os.Stdout, templateData)
	if err != nil {
		panic(err)
	}
}

var outputTemplate = template.Must(template.New("").Parse(`<?php

namespace KTemplate\Internal;

// File generated by gen_opcodes/main.go; DO NOT EDIT!

use KTemplate\Internal\Compile\Types;

class Op {
    public const UNKNOWN = 0;
    {{ range $.Opcodes }}
    {{.Comment}}
    public const {{.Name}} = {{.Opcode}};
    {{ end }}

    /**
     * @param int $op
     * @return int
     */
    public static function opcodeKind($op) {
        switch ($op) {
        {{- range $.Opcodes }}
        case self::{{.Name}}:
            return OpInfo::{{.Kind}};
        {{- end }}
        default:
            return OpInfo::KIND_OTHER;
        }
    }

    /**
     * @param int $op
     * @return string
     */
    public static function opcodeString($op) {
        switch ($op) {
        {{- range $.Opcodes }}
        case self::{{.Name}}:
            return '{{.Name}}';
        {{- end }}
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
        {{- range $.Opcodes }}
        {{- if .ResultType }}
        case self::{{.Name}}:
            return {{.ResultType}};
        {{- end -}}
        {{- end }}
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
        {{- range $.Opcodes }}
        case self::{{.Name}}:
            return {{.Flags}};
        {{- end }}
        default:
            return 0;
        }
    }

    public static $args = [
    {{- range $.Opcodes }}
        self::{{.Name}} => [{{.ArgString}}],
    {{- end }}
    ];
}
`))
