# Template language overview

A template is a text that may contain special tags that are processed by the engine.

Rendering a template results in a string with all special tags being expanded. All text outside of the special tags is emitted as is.

There are 3 main kinds of template tags:

* `{{ ... }}` output tags
* `{# ... #}` comment tags
* `{% ... %}` control tags

The **output tags** evaluate the **expression** between `{{` and `}}`, then add that evaluation result to the output (the expression result is coerced into a string).

The **comment tags** are just stripped out and do not affect the output in any way.

The **control tags** effect varies from one tag to another, we'll discuss them separately.

## Whitespace control

By default, the text around the tag is not modified and printed "as is". There are a few exceptions for this:

1. The text inside `{% include %}` directive is ignored (only `arg` tags are processed)
2. `{{-` and `{%-` open tags trim whitespace before the tag
3. `-}}` and `-%}` close tags trim whitespace after the tag

Given the template:

```html
{% include "partial.html" %}
  {% arg $title = "Example" %}
{% end %}
Trim left| {{- "OK" }}
{{ "OK" -}} |Trim right
Trim left| {{- "OK" -}} |Trim right
```

We'll get this output:

```
<h1>Example</h1>
Trim left|OK
OK|Trim right
Trim left|OK|Trim right
```

## Expressions

When template is executed, **expressions** can occur inside output tags as well as some control tags.

Different expression evaluation contexts may imply different conversion rules. Output tags will convert the result to a string while `if` tag would try to interpret the result as boolean.

Supported kinds of expressions:

| Expression | Examples |
|---|---|
| Null literals | `null` |
| Bool literals | `true`, `false` |
| Int/float literals | `10`, `10.5` |
| String literals | `"hello"`, `'world'` |
| Local variables | `$x`, `$foo` |
| External variables | `x`, `x.y.z` |
| Array indexing | `$arr[0]`, `$arr['key']`, `$arr[$k]` |
| Operators | `x ~ y`, `x and y`, `not x` |
| Function calls | `f()`, `g($x, $y)` |
| Filter pipelines | `$x|filter`, `$x|e("url")`, `$x|filter|raw` |

It's possible to use parentheses to group the expressions and force some specific evaluation order in case the default precedence would not work for you.

Single-quoted and double-quoted string literals are functionally identical. They can have escape sequences recognized by [stripcslashes](https://www.php.net/manual/en/function.stripcslashes.php).

### Variables and data providers

There are two types of variables in KTemplate:

* Local: declared inside the template
* External: the ones that are provided from the outside

The external variables lookup is resolved by the bound `DataProviderInterface` implementation. This data provider should know how to evaluate `x` and `x.y.z` to something meaningful.

This code fragement uses the local variable `x`:

```html
{% let $x = 10 %}
{{ $x }}
```

This code fragement is asking the data provider to resolve `x`:

```html
{{ x }}
```

Since there is a functional distinction between the two, the local variables are always prefixed with `$`. It also makes it easier for the template compiler to complain at compile-time when some undefined local variable is referenced instead of assuming that it's an external variable.

The local variables are lexically scoped. You define variables with `let` and change their value with `set`. It's a compile-time error to refer to an undefined local variable.

These control tags create a block scope:

* `if`/`else` chain
* `for` loop block
* `let`, `set`, `arg` forms with block assignment

### Operators

| Example | PHP Equivalent | Result Type |
|---|---|---|
| `x or y` | `x || y` | `bool` |
| `x and y` | `x && y` | `bool` |
| `x == y` | `x == y` | `bool` |
| `x != y` | `x != y` | `bool` |
| `x < y` | `x < y` | `bool` |
| `x <= y` | `x <= y` | `bool` |
| `x > y` | `x > y` | `bool` |
| `x >= y` | `x >= y` | `bool` |
| `x ~ y` | `x . y` | `string` |
| `x + y` | `x + y` | `int|float` |
| `x - y` | `x - y` | `int|float` |
| `x * y` | `x * y` | `int|float` |
| `x / y` | `x / y` | `int|float` |
| `x % y` | `x % y` | `int|float` |

Binary operator precedence groups:

| Precedence level | Tokens |
|---|---|
| 1 | `or` |
| 3 | `and` |
| 4 | `==`, `!=`, `<`, `<=`, `>`, `>=` |
| 5 | `~` `+` `-` |
| 7 | `*`, `/`, `%` |
| 9 | `[]` |
| 13 | `|` |
| 14 | `.` |

Unary operator precedence groups:

| Precedence level | Tokens |
|---|---|
| 6 | `not` |
| 11 | `+`, `-` |

>  The operator precedence is identical to Twig.

## Output escaping

Depending on the configuration, KTemplate may have auto-escaping enabled for some kinds of expression (or for everything that is being appended to the output).

Auto-escaping is usually applied to everything except the literal text and constants, as well as some integer-typed expressions. For everything else we apply a **default escaping strategy**. That escaping strategy can be configured (`html` by default).

When using an escape filter explicitely, it's possible to use two forms:

```html
{{ $x|escape        }} {# -- uses a default escape strategy #}
{{ $x|escape("url") }} {# -- uses an "url" escape strategy #}
```

For convenience, `escape` filter is aliased to `e`.

KTemplate tries to track which values were escaped and what doesn't need to be auto-escaped at all. For instance, for explicitely escaped expressions there will be no extra auto-escaping call involved. Keep in mind that double escaping is possible in some complex situations.

## Control tags

### Let

`let` declares and initializes a **local variable**.

After a variable is defined, it can be used as an expression.

```html
{% let $minutes_per_hour = 60 %}
{{ 24 * $minutes_per_hour }}
```

Note that local variables are block-scoped. You can't access that variable outside of that block.

```html
{% if cond %}
    {% let $x = 10 %}
    {{ $x }} {# OK, can use $x here #}
{% end %}   {# This tag closes the block started by if #}
{{ $x }}     {# Compile time error: can't use $x here }
```

The second form of `let` is block-assignment.

```html
{% let $part %}
  Everything inside this block will be rendered into the variable.
  {% if $cond %}
    It can contain any kinds of nested tags.
  {% end %}
{% end %}
```

### Set

`set` changes the value of already declared **local variable**.

It supports both `=` and block-style initializations, just like `let`.

```html
{% let $x = 0 %}

{% set $x = 10 %}

{% set $x %}
  {{ $x * 2 }}
{% end %}
```

### If

The simplest form includes only `if` and `end`:

```html
{% if <expr> %}
    This text will be rendered if <expr> evaluates to true.
{% end %}
```

`else` and `elseif` can be used to write a chain of conditions:

```html
{% if <expr> %}
    The first conditional text.
{% elseif <expr> %}
    The second conditional text.
{% else %}
    The text that is rendered otherwise.
{% end %}
```

### For

Loop over the values or keys and values of the array, rendering the loop body repeatedly.

```html
<ul>
  {% for $title in page.titles %}
    <li>{{ $title }}</li>
  {% end %}
</ul>
```

```html
{% for $i, $title in page.titles %}
  {{ $i }}: {{ $title }}<br>
{% end %}
```

### Include

Lookup the specified template and render it in place of the `include` tag.

Templates can have params. The default values of these params can be overriden by `arg` tags.

The block inside `include` can only contain `arg` tags and whitespace. The whitespace will be removed.

Given the `ui/button.template` defined like this:

```html
{% param $name = "button" %}
{% param $label = "" %}
{% if $label %}
<label>
  {{$label}}:
  <input id="ui-{{$name}}" type="button" value="{{$name}}">
</label>
{% else %}
  <input id="ui-{{$name}}" type="button" value="{{$name}}">
{% end %}
```

And the template that uses them defined as follow:

```html
{% include "ui/button.template" %}
  {% arg $name = "example1" %}
{% end %}
{% include "ui/button.template" %}
  {% arg $name = "example2" %}
  {% arg $label = "Example" %}
{% end %}
```

We can get these rendering results (some whitespace editted out):

```html
  <input id="ui-example1" type="button" value="example1">

<label>
  Example:
  <input id="ui-example2" type="button" value="example2">
</label>
```

The included templates don't have the access to the caller local variables, but it has the same external data provider, so expressions like `x.y` will evaluate to the same value.

### Arg

`arg` is like `set`, but for the template parameter declared with `param`.

`arg` can only be used inside the `include` block.

It's not a good idea to pass `null` value as argument, since `null` will force the parameter to be initialized to its default value.

### Param

`param` is like `let`, but for the template parameters that can be overriden by the caller side.

`param` tags can only be present at the beginning of template.

Parameters that don't get an explicit value inside the `include` block will be initialized to their default value.
