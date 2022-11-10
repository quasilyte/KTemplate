![](docs/logo.png)

## Overview

[KTemplate](https://github.com/quasilyte/KTemplate) is a simple text template engine for PHP and [KPHP](https://github.com/VKCOM/kphp).

KTemplate uses a syntax similar to the Twig, Django and Jinja template languages.

You can try it [online](https://quasilyte.tech/ktemplate/)!

**Features:**

* **Cross-language support**: works for both PHP and KPHP
* **Security**: no eval or dynamic PHP code generation/loading is used
* **Compile-time checks**: many errors are caught during template compilation
* **Zero-copy data binding**: efficient and flexible data provider model
* **Performance**: templates are compiled to optimized bytecode

## Quick Start

```bash
$ composer require quasilyte/ktemplate
```

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use KTemplate\Context;
use KTemplate\Engine;
use KTemplate\ArrayLoader;
use KTemplate\ArrayDataProvider;

$loader = new ArrayLoader([
    'main' => '{{ title }}',
]);
$engine = new Engine(new Context(), $loader);
$data = new ArrayDataProvider(['title' => 'Example']);
$result = $engine->render('main', $data);
var_dump($result); // => "Example"
```

Run with PHP:

```bash
$ php -f example.php
string(7) "Example"
```

Run with KPHP:

```bash
# 1. Compile
$ kphp --composer-root $(pwd) --mode cli example.php
# 2. Execute
$ ./kphp_out/cli
string(7) "Example"
```

## Documentation

* [Template language overview](_docs/template_language.md)
* [KTemplate idioms](_docs/ktemplate_idioms.md)
* [Differences from Twig](_docs/differences_from_twig.md)
* [KTemplate PHP API](_docs/ktemplate_php_api.md)
* [KTemplate architecture](_docs/ktemplate_architecture.md)

## Rationale

None of the template engines for PHP can be used with KPHP.

KTemplate is a solution that works in both languages.
