# Differences From Twig

While KTemplate syntax looks like Twig/Jinja, it doesn't try to mimic every feature.

This document tries to describe the differences between KTemplate and Twig.

## Strings

In Twig, there is a **string interpolation** feature that is enabled inside double-quoted strings.

In KTemplate there is no string interpolation, both single and double quoted strings behave identically.

There is a third kind of strings in KTemplate: raw string literals. They don't process any escape sequences, meaning they're the best candidates for inline regular expressions:

```html
{% if $x matches `/\d+/` %}
{# vs #}
{% if $x matches "/\\d+/" $}
```

## Local variables

In Twig, there is only `{% set %}`. No destinction between the declaration and modification is made.

This is similar to how variables work in PHP, but there are several reasons why KTemplate uses `{% let %}` + `{% set %}` combination. 

The benefits we get from explicit declarations:

1. Readability: you know exactly where this variables comes from
2. Compile-time error from undefined local variables
3. No fear of variable names clashing between the scopes
4. More efficient VM slots allocation with short-lived variables

```html
{% let $x = 10 %}
{{ $x }} => 10

{% set $x = 20 %}
{{ $x }} => 20
```

Local variables are also always prefixed with `$`. It's illegal to use that character in Twig variable names.

## Template inclusion mechanisms

Twig has `include`, `block` and `extends` along with `template_from_string` to work with multi-template applications.

The only mechanism KTemplate has is `include`.

The `block` and `extends` pair covers the **template inheritance**. There is a way to simulate that behavior, see [KTemplate Idioms](ktemplate_idioms.md) for the detailed example.

To make templates more powerfull, there are `{% param %}` and `{% arg %}` tags that allow the included template parametrization.

## Operators support

Twig has a lot of operators: normal binary operators, **tests** (via `is`), `in` operator and others.

KTemplate set of operators is more restricted. For most **tests**, you use normal functions:

```html
Twig style:      {{ $x is empty }}
KTemplate style: {{ is_empty($x) }}
```

There is no **ranges** `..` operator support too. Use functions for that.

## Properties/data access

In Twig, there are no restrictions for the data access thank to the PHP dynamic nature and its reflection API.

KTemplate can't use this approach, so it has to invent some new form of template data binding that could work in both PHP and KPHP.

In KTemplate, `x.y.z` is not necessarily identical to `x["y"]["z"]`, because it's processed by the data provider as a whole. Usually, it's a good practice to map this chain to something predictable, but that's only a convention.

## Misc missing features

KTemplate doesn't implement these features:

* Named arguments for function calls
* Functions are always called with `()`
* Some tags like `spaceless` and other (consult the API reference)
* Macros system
* Array literals
