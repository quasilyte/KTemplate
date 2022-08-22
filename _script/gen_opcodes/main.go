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
}

type opcodeInfo struct {
	Name       string
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
	boolType    = "Types::BOOL"
	intType     = "Types::INT"
	floatType   = "Types::FLOAT"
	stringType  = "Types::STRING"
	nullType    = "Types::NULL"
	unknownType = "Types::UNKNOWN"
)

var rawOpcodes = []opcodeTemplate{
	{"RETURN", "op", unknownType},

	{"OUTPUT", "op arg:rslot", unknownType},
	{"OUTPUT_SLOT0", "op *slot0", unknownType},
	{"OUTPUT_INT_CONST", "op val:intindex", unknownType},
	{"OUTPUT_STRING_CONST", "op val:strindex", unknownType},
	{"OUTPUT_EXTDATA_1", "op cache:cacheslot k:keyoffset", unknownType},
	{"OUTPUT_EXTDATA_2", "op cache:cacheslot k:keyoffset", unknownType},
	{"OUTPUT_EXTDATA_3", "op cache:cacheslot k:keyoffset", unknownType},

	{"LOAD_BOOL", "op dst:wslot val:imm8", boolType},
	{"LOAD_SLOT0_BOOL", "op *slot0 val:imm8", boolType},
	{"LOAD_INT_CONST", "op dst:wslot val:intindex", intType},
	{"LOAD_SLOT0_INT_CONST", "op *slot0 val:intindex", intType},
	{"LOAD_FLOAT_CONST", "op dst:wslot val:floatindex", floatType},
	{"LOAD_SLOT0_FLOAT_CONST", "op *slot0 val:floatindex", floatType},
	{"LOAD_STRING_CONST", "op dst:wslot val:strindex", stringType},
	{"LOAD_SLOT0_STRING_CONST", "op *slot0 val:strindex", stringType},
	{"LOAD_EXTDATA_1", "op dst:wslot cache:cacheslot k:keyoffset", unknownType},
	{"LOAD_SLOT0_EXTDATA_1", "op *slot0 cache:cacheslot k:keyoffset", unknownType},
	{"LOAD_EXTDATA_2", "op dst:wslot cache:cacheslot k:keyoffset", unknownType},
	{"LOAD_SLOT0_EXTDATA_2", "op *slot0 cache:cacheslot k:keyoffset", unknownType},
	{"LOAD_EXTDATA_3", "op dst:wslot cache:cacheslot k:keyoffset", unknownType},
	{"LOAD_SLOT0_EXTDATA_3", "op *slot0 cache:cacheslot k:keyoffset", unknownType},
	{"LOAD_NULL", "op dst:wslot", nullType},
	{"LOAD_SLOT0_NULL", "op", nullType},

	{"INDEX", "op dst:wslot src:rslot key:rslot", unknownType},
	{"INDEX_SLOT0", "op *slot0 src:rslot key:rslot", unknownType},
	{"INDEX_INT_KEY", "op dst:wslot src:rslot key:intindex", unknownType},
	{"INDEX_SLOT0_INT_KEY", "op *slot0 src:rslot key:intindex", unknownType},
	{"INDEX_STRING_KEY", "op dst:wslot src:rslot key:strindex", unknownType},
	{"INDEX_SLOT0_STRING_KEY", "op *slot0 src:rslot key:strindex", unknownType},

	{"MOVE", "op dst:wslot src:rslot", unknownType},
	{"MOVE_SLOT0", "op *slot0 src:rslot", unknownType},
	{"MOVE_BOOL", "op dst:wslot src:rslot", boolType},
	{"MOVE_SLOT0_BOOL", "op *slot0 src:rslot", boolType},

	{"CONV_BOOL", "op dst:wslot", boolType},
	{"CONV_SLOT0_BOOL", "op *slot0", boolType},

	{"JUMP", "op pcdelta:rel16", unknownType},
	{"JUMP_FALSY", "op pcdelta:rel16 cond:rslot", unknownType},
	{"JUMP_SLOT0_FALSY", "op *slot0 pcdelta:rel16", unknownType},
	{"JUMP_TRUTHY", "op pcdelta:rel16 cond:rslot", unknownType},
	{"JUMP_SLOT0_TRUTHY", "op *slot0 pcdelta:rel16", unknownType},

	{"FOR_VAL", "op *slot0 pcdelta:rel16 val:wslot", unknownType},
	{"FOR_KEY_VAL", "op *slot0 pcdelta:rel16 key:wslot val:wslot", unknownType},

	{"CALL_FILTER1", "op dst:wslot arg1:rslot fn:filterid", unknownType},
	{"CALL_SLOT0_FILTER1", "op *slot0 arg1:rslot fn:filterid", unknownType},
	{"CALL_FILTER2", "op dst:wslot arg1:rslot arg2:rslot fn:filterid", unknownType},
	{"CALL_SLOT0_FILTER2", "op *slot0 arg1:rslot arg2:rslot fn:filterid", unknownType},
	{"CALL_FUNC0", "op dst:wslot fn:funcid", unknownType},
	{"CALL_SLOT0_FUNC0", "op *slot0 fn:funcid", unknownType},
	{"CALL_FUNC1", "op dst:wslot arg1:rslot fn:funcid", unknownType},
	{"CALL_SLOT0_FUNC1", "op *slot0 arg1:rslot fn:funcid", unknownType},
	{"CALL_FUNC2", "op dst:wslot arg1:rslot arg2:rslot fn:funcid", unknownType},
	{"CALL_SLOT0_FUNC2", "op *slot0 arg1:rslot arg2:rslot fn:funcid", unknownType},
	{"CALL_FUNC3", "op dst:wslot arg1:rslot arg2:rslot arg3:rslot fn:funcid", unknownType},
	{"CALL_SLOT0_FUNC3", "op *slot0 arg1:rslot arg2:rslot arg3:rslot fn:funcid", unknownType},
	{"LENGTH_FILTER", "op dst:wslot arg1:rslot", intType},
	{"LENGTH_SLOT0_FILTER", "op dst:wslot arg1:rslot", intType},
	{"DEFAULT_FILTER", "op dst:wslot arg1:rslot arg2:rslot", unknownType},
	{"DEFAULT_SLOT0_FILTER", "op dst:wslot arg1:rslot arg2:rslot", unknownType},

	{"NOT", "op dst:wslot arg:rslot", boolType},
	{"NOT_SLOT0", "op *slot0 arg:rslot", boolType},
	{"NEG", "op dst:wslot arg:rslot", unknownType},
	{"NEG_SLOT0", "op *slot0 arg:rslot", unknownType},

	{"OR", "op dst:wslot arg1:rslot arg2:rslot", boolType},
	{"OR_SLOT0", "op *slot0 arg1:rslot arg2:rslot", boolType},
	{"AND", "op dst:wslot arg1:rslot arg2:rslot", boolType},
	{"AND_SLOT0", "op *slot0 arg1:rslot arg2:rslot", boolType},
	{"CONCAT", "op dst:wslot arg1:rslot arg2:rslot", stringType},
	{"CONCAT_SLOT0", "op *slot0 arg1:rslot arg2:rslot", stringType},
	{"EQ", "op dst:wslot arg1:rslot arg2:rslot", boolType},
	{"EQ_SLOT0", "op *slot0 arg1:rslot arg2:rslot", boolType},
	{"LT", "op dst:wslot arg1:rslot arg2:rslot", boolType},
	{"LT_SLOT0", "op *slot0 arg1:rslot arg2:rslot", boolType},
	{"LT_EQ", "op dst:wslot arg1:rslot arg2:rslot", boolType},
	{"LT_EQ_SLOT0", "op *slot0 arg1:rslot arg2:rslot", boolType},
	{"NOT_EQ", "op dst:wslot arg1:rslot arg2:rslot", boolType},
	{"NOT_EQ_SLOT0", "op *slot0 arg1:rslot arg2:rslot", boolType},
	{"ADD", "op dst:wslot arg1:rslot arg2:rslot", unknownType},
	{"ADD_SLOT0", "op *slot0 arg1:rslot arg2:rslot", unknownType},
	{"SUB", "op dst:wslot arg1:rslot arg2:rslot", unknownType},
	{"SUB_SLOT0", "op *slot0 arg1:rslot arg2:rslot", unknownType},
	{"MUL", "op dst:wslot arg1:rslot arg2:rslot", unknownType},
	{"MUL_SLOT0", "op *slot0 arg1:rslot arg2:rslot", unknownType},
	{"QUO", "op dst:wslot arg1:rslot arg2:rslot", unknownType},
	{"QUO_SLOT0", "op *slot0 arg1:rslot arg2:rslot", unknownType},
	{"MOD", "op dst:wslot arg1:rslot arg2:rslot", unknownType},
	{"MOD_SLOT0", "op *slot0 arg1:rslot arg2:rslot", unknownType},
}

func getOpcodeInfo(data opcodeTemplate) opcodeInfo {
	var result opcodeInfo
	result.Name = data.name
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
	hasSlotArg := false
	for _, p := range strings.Split(desc, " ") {
		if p == "*slot0" {
			flagparts = append(flagparts, "OpInfo::FLAG_IMPLICIT_SLOT0")
			continue
		}
		var arg argumentInfo
		parts := strings.Split(p, ":")
		if len(parts) != 2 {
			panic(fmt.Sprintf("%s: can't split by :", data.name))
		}
		kind := parts[1]
		switch kind {
		case "wslot", "rslot":
			hasSlotArg = true
			arg.Kind = "OpInfo::ARG_SLOT"
		case "cacheslot":
			arg.Kind = "OpInfo::ARG_CACHE_SLOT"
		case "keyoffset":
			arg.Kind = "OpInfo::ARG_KEY_OFFSET"
		case "strindex":
			arg.Kind = "OpInfo::ARG_STRING_CONST"
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

	if hasSlotArg {
		flagparts = append(flagparts, "OpInfo::FLAG_HAS_SLOT_ARG")
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

namespace KTemplate;

use KTemplate\Compile\Types;

class Op {
    public const UNKNOWN = 0;
    {{ range $.Opcodes }}
    {{.Comment}}
    public const {{.Name}} = {{.Opcode}};
    {{ end }}

    /**
     * @param int $op
     * @return string
     */
    public static function opcodeString($op) {
        switch ($op) {
        {{- range $.Opcodes }}
        case {{.Opcode}}:
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
        case {{.Opcode}}: // {{.Name}}
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
