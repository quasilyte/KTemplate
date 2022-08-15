# Template language overview

A template is a text that may contain special tags that are processed by the engine.

Rendering a template results in a string with all special tags being expanded. All text outside of the special tags is emitted as is.

There are 3 main kinds of template engine tags:

* `{{ ... }}` output tags
* `{# ... #}` comment tags
* `{% ... %}` control tags

The **output tags** evaluate the **expression** between `{{` and `}}`, then add that evaluation result to the output.

The **comment tags** are just stripped out and do not affect the output in any way.

The **control tags** effect varies from one tag to another, we'll discuss them separately.

## Expressions

When template is executed, **expressions** can occur inside output tags as well as some control tags.

Different expression evaluation contexts may imply different conversion rules. Output tags will convert the result to a string while `if` tag would try to interpret the result as boolean.

Supported kinds of expressions:

| Expression | Examples |
|---|---|
| Int/float literals | `10`, `10.5` |
| String literals | `"hello"`, `'world'` |
| Bool literals | `true`, `false` |
| Variables | `external_var`, `$local_var` |
| Member access | `obj.field`, `arr.key`, `$arr.key` |
| Array indexing | `$arr[0]`, `$arr['key']` |
| Operators | `x ~ y`, `x and y`, `not x` |

It's possible to use parentheses to group the expressions and force some specific evaluation order in case the default precedence would not work for you.

### Variables and data providers

The simplest form of expression is a variable.

There are two types of variables in KTemplate:

* Local: declared inside the template
* External: the ones that are provided outside

The external variables lookup is resolved by the `DataProvider`. This configured data provider should know how to evaluate `x` and `x.y.z` to something meaningful.

This code fragement uses the local variable `x`:

```html
{% set $x = 10 %}
{{ $x }}
```

This code fragement may end up using the data provider to resolve `x`:

```html
{{ x }}
```

Both local and external variables can be used to access the data members, but they do it differently. Local variables interpret `$x.y` as an array lookup. External variables will look at the entire expression `x.y` and may access some object property or call some function to evaluate the result.

Since there is a functional distinction between the two, the local variables are always prefixed with `$`. It also makes it easier for the template compiler to complain at compile-time when some undefined local variable is referenced instead of assuming that it's an external variable.

The local variables are lexically scoped.

### Operators

| Example | Effect |
|---|---|
| `x ~ y` | Apply PHP `.` operator to `x` and `y` |
| `x + y` | Apply PHP `+` operator to `x` and `y` |
| `x - y` | Apply PHP `-` operator to `x` and `y` |

Operator precedence groups:

| Precedence level | Tokens |
|---|---|
| 4 | `~` `+` `-` |

## Control tags

### If

The simplest form includes only `if` and `endif`:

```html
{% if <expr> %}
    This text will be rendered if <expr> evaluates to true.
{% endif %}
```

`else` and `elseif` can be used to write a chain of conditions:

```html
{% if <expr> %}
    The first conditional text.
{% elseif <expr> %}
    The second conditional text.
{% else %}
    The text that is rendered otherwise.
{% endif %}
```
