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

	{"OUTPUT_SLOT0", "op *slot0"},
	{"OUTPUT", "op arg:rslot"},
	{"OUTPUT_INT_CONST", "op val:intindex"},
	{"OUTPUT_STRING_CONST", "op val:strindex"},
	{"OUTPUT_VAR_1", "op p1:strindex"},
	{"OUTPUT_VAR_2", "op p1:strindex p2:strindex"},
	{"OUTPUT_VAR_3", "op p1:strindex p2:strindex p3:strindex"},

	{"LOAD_SLOT0_INT_CONST", "op *slot0 val:intindex"},
	{"LOAD_INT_CONST", "op dst:wslot val:intindex"},
	{"LOAD_STRING_CONST", "op dst:wslot val:strindex"},
	{"LOAD_VAR_1", "op dst:wslot p1:strindex"},
	{"LOAD_VAR_2", "op dst:wslot p1:strindex p2:strindex"},
	{"LOAD_VAR_3", "op dst:wslot p1:strindex p2:strindex p3:strindex"},

	{"CONCAT_SLOT0_2", "op *slot0 arg2:rslot"},
	{"CONCAT_SLOT0_3", "op *slot0 arg2:rslot arg3:rslot"},

	{"JUMP", "op pcdelta:rel8"},
	{"JUMP_ZERO", "op *slot0 pcdelta:rel8"},
	{"JUMP_NOT_ZERO", "op *slot0 pcdelta:rel8"},

	{"ADD", "op dst:wslot arg1:rslot arg2:rslot"},
	{"ADD_SLOT0", "op *slot0 arg1:rslot arg2:rslot"},
	{"MUL", "op dst:wslot arg1:rslot arg2:rslot"},
	{"MUL_SLOT0", "op *slot0 arg1:rslot arg2:rslot"},
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
			arg.Kind = "OpInfo::ARG_SLOT"
		case "strindex":
			arg.Kind = "OpInfo::ARG_STRING_CONST"
		case "intindex":
			arg.Kind = "OpInfo::ARG_INT_CONST"
		case "rel8":
			arg.Kind = "OpInfo::ARG_REL8"
		default:
			panic(fmt.Sprintf("%s: unexpected %s arg kind", data.name, kind))
		}
		result.Args = append(result.Args, arg)
		encparts = append(encparts, p)
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
