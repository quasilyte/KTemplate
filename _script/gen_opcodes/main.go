package main

import (
	"fmt"
	"os"
	"strings"
	"text/template"
)

type opcodeTemplate struct {
	name string
	desc string
}

type opcodeInfo struct {
	Name      string
	Opcode    byte
	Args      []argumentInfo
	ArgString string
	Comment   string
	Enc       string
	Flags     string
}

type argumentInfo struct {
	Kind string
}

var rawOpcodes = []opcodeTemplate{
	{"RETURN", "op"},

	{"OUTPUT", "op arg:rslot"},
	{"OUTPUT_SLOT0", "op *slot0"},
	{"OUTPUT_INT_CONST", "op val:intindex"},
	{"OUTPUT_STRING_CONST", "op val:strindex"},
	{"OUTPUT_EXTDATA_1", "op cache:cacheslot k:keyoffset"},
	{"OUTPUT_EXTDATA_2", "op cache:cacheslot k:keyoffset"},
	{"OUTPUT_EXTDATA_3", "op cache:cacheslot k:keyoffset"},

	{"LOAD_BOOL", "op dst:wslot val:imm8"},
	{"LOAD_SLOT0_BOOL", "op *slot0 val:imm8"},
	{"LOAD_INT_CONST", "op dst:wslot val:intindex"},
	{"LOAD_SLOT0_INT_CONST", "op *slot0 val:intindex"},
	{"LOAD_STRING_CONST", "op dst:wslot val:strindex"},
	{"LOAD_SLOT0_STRING_CONST", "op *slot0 val:strindex"},
	{"LOAD_EXTDATA_1", "op dst:wslot cache:cacheslot k:keyoffset"},
	{"LOAD_SLOT0_EXTDATA_1", "op *slot0 cache:cacheslot k:keyoffset"},
	{"LOAD_EXTDATA_2", "op dst:wslot cache:cacheslot k:keyoffset"},
	{"LOAD_SLOT0_EXTDATA_2", "op *slot0 cache:cacheslot k:keyoffset"},
	{"LOAD_EXTDATA_3", "op dst:wslot cache:cacheslot k:keyoffset"},
	{"LOAD_SLOT0_EXTDATA_3", "op *slot0 cache:cacheslot k:keyoffset"},
	{"LOAD_NULL", "op dst:wslot"},
	{"LOAD_SLOT0_NULL", "op"},

	{"INDEX", "op dst:wslot src:rslot key:rslot"},
	{"INDEX_SLOT0", "op *slot0 src:rslot key:rslot"},
	{"INDEX_INT_KEY", "op dst:wslot src:rslot key:intindex"},
	{"INDEX_SLOT0_INT_KEY", "op *slot0 src:rslot key:intindex"},
	{"INDEX_STRING_KEY", "op dst:wslot src:rslot key:strindex"},
	{"INDEX_SLOT0_STRING_KEY", "op *slot0 src:rslot key:strindex"},

	{"MOVE_BOOL", "op dst:wslot src:rslot"},
	{"MOVE_SLOT0_BOOL", "op *slot0 src:rslot"},

	{"CONV_BOOL", "op arg:wslot"},
	{"CONV_SLOT0_BOOL", "op *slot0"},

	{"JUMP", "op pcdelta:rel16"},
	{"JUMP_FALSY", "op pcdelta:rel16 cond:rslot"},
	{"JUMP_SLOT0_FALSY", "op *slot0 pcdelta:rel16"},
	{"JUMP_TRUTHY", "op pcdelta:rel16 cond:rslot"},
	{"JUMP_SLOT0_TRUTHY", "op *slot0 pcdelta:rel16"},

	{"CALL_FILTER1", "op dst:wslot arg1:rslot fn:filterid"},
	{"CALL_SLOT0_FILTER1", "op *slot0 arg1:rslot fn:filterid"},
	{"CALL_FILTER2", "op dst:wslot arg1:rslot arg2:rslot fn:filterid"},
	{"CALL_SLOT0_FILTER2", "op *slot0 arg1:rslot arg2:rslot fn:filterid"},
	{"CALL_FUNC0", "op dst:wslot fn:funcid"},
	{"CALL_SLOT0_FUNC0", "op *slot0 fn:funcid"},
	{"CALL_FUNC1", "op dst:wslot arg1:rslot fn:funcid"},
	{"CALL_SLOT0_FUNC1", "op *slot0 arg1:rslot fn:funcid"},
	{"CALL_FUNC2", "op dst:wslot arg1:rslot arg2:rslot fn:funcid"},
	{"CALL_SLOT0_FUNC2", "op *slot0 arg1:rslot arg2:rslot fn:funcid"},
	{"CALL_FUNC3", "op dst:wslot arg1:rslot arg2:rslot arg3:rslot fn:funcid"},
	{"CALL_SLOT0_FUNC3", "op *slot0 arg1:rslot arg2:rslot arg3:rslot fn:funcid"},
	{"LENGTH_FILTER", "op dst:wslot arg1:rslot"},
	{"LENGTH_SLOT0_FILTER", "op dst:wslot arg1:rslot"},
	{"DEFAULT_FILTER", "op dst:wslot arg1:rslot arg2:rslot"},
	{"DEFAULT_SLOT0_FILTER", "op dst:wslot arg1:rslot arg2:rslot"},

	{"NOT", "op dst:wslot arg:rslot"},
	{"NOT_SLOT0", "op *slot0 arg:rslot"},
	{"NEG", "op dst:wslot arg:rslot"},
	{"NEG_SLOT0", "op *slot0 arg:rslot"},

	{"OR", "op dst:wslot arg1:rslot arg2:rslot"},
	{"OR_SLOT0", "op *slot0 arg1:rslot arg2:rslot"},
	{"AND", "op dst:wslot arg1:rslot arg2:rslot"},
	{"AND_SLOT0", "op *slot0 arg1:rslot arg2:rslot"},
	{"CONCAT", "op dst:wslot arg1:rslot arg2:rslot"},
	{"CONCAT_SLOT0", "op *slot0 arg1:rslot arg2:rslot"},
	{"EQ", "op dst:wslot arg1:rslot arg2:rslot"},
	{"EQ_SLOT0", "op *slot0 arg1:rslot arg2:rslot"},
	{"LT", "op dst:wslot arg1:rslot arg2:rslot"},
	{"LT_SLOT0", "op *slot0 arg1:rslot arg2:rslot"},
	{"LT_EQ", "op dst:wslot arg1:rslot arg2:rslot"},
	{"LT_EQ_SLOT0", "op *slot0 arg1:rslot arg2:rslot"},
	{"NOT_EQ", "op dst:wslot arg1:rslot arg2:rslot"},
	{"NOT_EQ_SLOT0", "op *slot0 arg1:rslot arg2:rslot"},
	{"ADD", "op dst:wslot arg1:rslot arg2:rslot"},
	{"ADD_SLOT0", "op *slot0 arg1:rslot arg2:rslot"},
	{"SUB", "op dst:wslot arg1:rslot arg2:rslot"},
	{"SUB_SLOT0", "op *slot0 arg1:rslot arg2:rslot"},
	{"MUL", "op dst:wslot arg1:rslot arg2:rslot"},
	{"MUL_SLOT0", "op *slot0 arg1:rslot arg2:rslot"},
	{"QUO", "op dst:wslot arg1:rslot arg2:rslot"},
	{"QUO_SLOT0", "op *slot0 arg1:rslot arg2:rslot"},
	{"MOD", "op dst:wslot arg1:rslot arg2:rslot"},
	{"MOD_SLOT0", "op *slot0 arg1:rslot arg2:rslot"},
}

func getOpcodeInfo(data opcodeTemplate) opcodeInfo {
	var result opcodeInfo
	result.Name = data.name
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

class Op {
    public const UNKNOWN = 0;
    {{ range $.Opcodes }}
    {{.Comment}}
    public const {{.Name}} = {{.Opcode}};
    {{ end }}
    public static function opcodeString(int $op): string {
        switch ($op) {
        {{- range $.Opcodes }}
        case {{.Opcode}}:
            return '{{.Name}}';
        {{- end }}
        default:
            return '?';
        }
    }

    public static function opcodeFlags(int $op): int {
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
