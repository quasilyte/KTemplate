# KTemplate PHP API

## Introduction

The core public API consists of these classes:

* `Context` - the main configuration source
* `Engine` - API main entry point (render/compile/etc)
* `DataProviderInterface` and related `DataKey` - data binding
* `LoaderInterface` and related `TemplateCacheKey` - templates discovery
* `CompilationException` - an exception that contains error location info
* `Template` - the compiled template object (can be renderer or disassembled)

For convenience, KTemplate provides implementations:

* `ArrayDataProvider` implements `DataProviderInterface`
* `ArrayLoader` implements `LoaderInterface`
* `FilesystemLoader` implements `LoaderInterface`

It's possible to use KTemplate engine with 0 predefined filters and functions. Since that's not always practical, two classes are available to register the required functionality:

* `FilterLib` - implementations for core filters
* `FunctionLib` - implementations for core functions

Here is a minimal example that uses the default config:

```php
$ctx = new Context();
$loader = new ArrayLoader([
    'main.html' => 'Example',
]);
$engine = new Engine($ctx, $loader);
$result = $engine->render('main.html');
var_dump($result); // => "Example"
```

The `Context` object can be used to configure the template engine.

The `LoaderInterface` implementation will be used to lookup the template sources at the run time. `ArrayLoader` maps a **template path** to the **template source code**. The `FilesystemLoader` will use a local filesystem to do that. It's possible to implement your own loader if you need something that can't be achieved by those two.

After that create a single `Engine` object that is used to interact with the templating system. The context and template loader are bound to that engine upon creation.

## Output escaping

By default, KTemplate auto-escapes most expressions using the `html` strategy.

It's possible to turn the auto-escaping off by either setting the `$ctx->escape_func` to `null` or disabling the auto escape for all configurable contexts.

```php
// Solution 1: unset the escaping function/
// Also makes explicit escaping impossible.
$ctx->escape_func = null;

// Solution 2: disable all auto escaping contexts.
$ctx->auto_escape_text = false;       // Optional: already false by default
$ctx->auto_escape_const_expr = false; // Optional: already false by default
$ctx->auto_escape_expr = false;       // Mandatory: enabled by default
```

There are three contexts suitable for auto escaping:

* `auto_escape_text` - everything outside tags (default: `false`)
* `auto_escape_const_expr` - expressions like constant strings (default: `false`)
* `auto_escape_expr` - all other expressions (default: `true`)

Note that some expressions are never auto-escaped, like expressions that evaluate to `int` or `float`.

The `$ctx->escape_func` is used for both auto-escaping and explicit `escape`/`e` filter usages.

To change the default escaping strategy, set `$ctx->default_escape_strategy`. This will be used as a default `$strategy` parameter for the `$ctx->escape_func`.

```php
// This is a simple example of a custom escape function.
$ctx->escape_func = function ($s, $strategy) {
    if ($strategy === 'html') {
        return htmlspecialchars($s, \ENT_QUOTES);
    }
    if ($strategy === 'base64') {
        return base64_encode($s);
    }
    return $s;
};

$ctx->default_escape_strategy = 'base64';
```

```html
{# Note that we set default_escape_strategy to 'base64' #}

{{ $x }}           {# If auto-escaped, escape_func($x, 'base64') #}
{{ $x|e }}         {# escape_func($x, 'base64')                  #}
{{ $x|escape }}    {# escape_func($x, 'base64')                  #}
{{ $x|e('html') }} {# escape_func($x, 'html')                    #}
```

## Template data binding

When rendering a template, you can pass an optional parameter of `DataProviderInterface` type.

This data provider will be used to resolve expressions like `x.y.z` inside the template.

The `DataProviderInterface` consists of only one method:

```php
function getData(DateKey $key): mixed;
```

Where `DataKey` is defines as follow:

```php
class DataKey {
    public int $num_parts;
    public string $part1;
    public string $part2;
    public string $part3;
}
```

| Expression | `$part1` | `$part2` | `$part3` | `$num_parts` |
|------------|----------|----------|----------|--------------|
| `x`        | `"x"`    | `""`     | `""`     | 1            |
| `x.y`      | `"x"`    | `"y"`    | `""`     | 2            |
| `x.y.z`    | `"x"`    | `"y"`    | `"z"`    | 3            |

> 3-part key is the current limit of KTemplate. You can use the dynamic lookup with `a.b.c[$key]` if `a.b.c` evaluates to something that can be indexed (like array or string).

The builtin `ArrayDataProvider` implements `getData` method like so:

```php
public function getData(DataKey $key): mixed {
    switch ($key->num_parts) {
    case 1:
        return $this->data[$key->part1];
    case 2:
        return $this->data[$key->part1][$key->part2];
    default:
        return $this->data[$key->part1][$key->part2][$key->part3];
    }
}
```

For more complex data that can't be represented as a single `mixed[]` array, you may need to implement your own data provider. For example, that data provider can perform an object field lookup.

```php
public function getData(DataKey $key): mixed {
    if ($this->matchKey2($key, 'foo', 'bar')) {
        return $this->foo->bar;
    }
    if ($this->matchKey3($key, 'foo', 'baz', 'qux')) {
        return $this->foo->baz->qux;
    }
    // ... handling other keys
    return null;
}

// You may want to write some data key matching helpers.
//
// Depending on your goals, it's possible to trade some
// performance for convenience. It's also possible to
// map the data more efficiently with nested switch statements.

private function matchKey2(DataKey $key, string $p1, string $p2): bool {
    return $key->num_parts === 2 &&
           $key->part1 === $p1 &&
           $key->part2 === $p2;
}

private function matchKey3(DataKey $key, string $p1, string $p2, string $p3): bool {
    return $key->num_parts === 3 &&
           $key->part1 === $p1 &&
           $key->part2 === $p2 &&
           $key->part3 === $p3;
}
```

The data provider concept allows us to make data independent from the template. We take the application data, wrap it into a custom data provider and then render the template. Only the data provider needs to know about the both ends. The template source is data provider agnostic while the application data does not need to be copied just to render the template.

```php
// $users, $forms and $misc don't know that they're
// about to be used inside a template, but IndexDataProvider
// knows about index.template and those data sources.
$data = new IndexDataProvider($users, $forms, $misc);
$result = $engine->render('ui/index.template', $data);
```

Note that KTemplate has data access caching, so it may evaluate `getData` on some key only once and then use the cached value. Therefore, it's recommended that the data returned by the data provider does not change while the template is being rendered.

## Registering filters and functions

To load core filters and functions, use `FilterLib` and `FunctionLib` classes:

```php
FilterLib::registerAllFilters($ctx, $engine);
FunctionLib::registerAllFunctions($ctx, $engine);
```

`registerAll` methods will call all `register<X>` methods contained inside a class. This it's possible to load functions and filters selectively:

```php
// Register only 'capitalize' and 'join' filters.
FilterLib::registerCapitalize($ctx, $engine);
FilterLib::registerJoin($ctx, $engine);
```

To register a custom function (or a filter), use `Engine` methods:

```php
$engine->registerFunction1('json_encode', function ($x) {
    return json_encode($x);
});
```

Functions with the same name but different arity will never clash with each other. This can be used to implement functions (and filters) with **default arguments**:

```php
$engine->registerFunction0('random', function () {
    return my_rand_impl();
});
$engine->registerFunction1('random', function ($min) {
    return my_rand_impl($min);
});
$engine->registerFunction2('random', function ($min, $max) {
    return my_rand_impl($min, $max);
});
```

Filters are like functions, but they have extra argument that comes before the `|` operator.

```php
// $x|nl2br will call this function with $x passed as an argument.
//
// Generally speaking:
//
//   $x|filter        => filter($x)
//   $x|filter('arg') => filter($x, 'arg')
//
$engine->registerFilter1('nl2br', function ($x) {
    return nl2br($x);
});
```

If you want to provide a library that registers custom functions to KTemplate, consider accepting both `Engine` and `Context`. The context may be needed to know which encoding needs to be used.

```php
public static function registerFirst(Context $ctx, Engine $engine) {
    $engine->registerFilter1('first', function ($x) use ($ctx) {
        // For simplicity, only handling strings here.
        if (!is_string($x)) {
            return null;
        }
        if (strlen($x) === 0) {
            return '';
        }
        // We need to know the encoding to perform this operation correctly.
        return mb_substr($x, 0, 1, $ctx->encoding);
    });
}
```

## Template compilation cache

Compiling a template from its sources is expensive operation. Sometimes it takes even more time to compile the template than actually executing it.

To reduce the compilation overhead, there are two levels of caching:

1. In-memory cache that exists inside one request (very fast, always enabled)
2. Filesystem cache that persists between the requests (fast, not enabled by default)

The first cache level is simple. We same the compiled templates in the PHP arrays.

To enable the second level, you need to specify the **cache directory**:

```php
$ctx->cache_dir = $path_to_cache_dir;
```

KTemplate never clears the given `cache_dir`. If templates are not updated frequently, this is not a concern. If your system has limited resources and template sources updates happen often, consider clearing the `cache_dir` once in a while.

Since cache can become outdated, KTemplate uses two-component keys over the template source:

1. Last modification time
2. Template source size

```php
class TemplateCacheKey {
    public string $full_name = '';
    public int $modification_time = 0;
    public int $source_size = 0;
}
```

> The `$full_name` is not used by the engine, but it can be used to re-use name resolution results peformed by your loader.

For immutable/in-memory templates it doesn't matter that much, you can adjust the values as you like. For filesystem loaders, `filemtime` and `filesize` PHP functions can be used.

This way, the cache knows when it can load the template from cache or it needs to re-compile it.

This is why the `LoaderInterface` has `updateCacheKey` method.

Here is how you can implement the filesystem loader:

```php
public function load(string $path, string $full_name) {
    if (!$full_name) {
        $full_name = $this->resolvePath($path);
    }
    return (string)file_get_contents($full_name);
}

public function updateCacheKey(string $path, TemplateCacheKey $key) {
    $key->full_name = $this->resolvePath($path);
    $filemtime_result = filemtime($key->full_name);
    if ($filemtime_result === false) {
        throw new \Exception("cached $key->full_name appears to be unavailable");
    }
    $key->modification_time = (int)$filemtime_result;
    $key->source_size = (int)filesize($key->full_name);
}
```

For long-running requests or applications like games and services/daemons (that never exit the request) it may be necessary to turn the `cache_recheck` option to `true`:

```php
$ctx->cache_recheck = true;
```

When this option is enabled, KTemplate will re-check whether the first level cache content is not stale. This is redundant for normal PHP applications, therefore it's turned off by default.

## Template inspection

We used `$engine->render` method with a specified **template path**. There is also another way to render a template.

```php
// load() may compile the template if it was not compiled yet.
$t = $engine->load('main.html');
$result = $engine->renderTemplate($t);
```

This approach leaves you with `Template` object that you can use.

It's possible to look inside that template by disassembling it:

```php
$disasm = $engine->disassembleTemplate($t);
```
