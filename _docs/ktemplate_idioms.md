# KTemplate Idioms

## Twig-like template inheritance

There are no distincs `extends`/`block` tags in KTemplate, but this functionallity can be achieved by `include` alone.

Let's take the Twig example and rewrite it accordingly.

`base.twig`:

```html
<!DOCTYPE html>
<html>
    <head>
        {% block head %}
            <link rel="stylesheet" href="style.css"/>
            <title>{% block title %}{% endblock %} - My Webpage</title>
        {% endblock %}
    </head>
    <body>
        <div id="content">{% block content %}{% endblock %}</div>
        <div id="footer">
            {% block footer %}
                &copy; Copyright 2011 by <a href="http://domain.invalid/">you</a>.
            {% endblock %}
        </div>
    </body>
</html>
```

`child.twig`:

```html
{% extends "base.twig" %}

{% block title %}Index{% endblock %}
{% block head %}
    {{ parent() }}
    <style type="text/css">
        .important { color: #336699; }
    </style>
{% endblock %}
{% block content %}
    <h1>Index</h1>
    <p class="important">
        Welcome to my awesome homepage.
    </p>
{% endblock %}
```

Most of the time, the transformation is straightforward.

`base.ktemplate`:

```html
{% param $title = "" %}
{% param $head = "" %}
{% param $content = "" %}
{% param $footer %}
    &copy; Copyright 2011 by <a href="http://domain.invalid/">you</a>.
{% end %}

<!DOCTYPE html>
<html>
    <head>
        <link rel="stylesheet" href="style.css"/>
        <title>{{ $title }} - My Webpage</title>
        {{ $head|raw }}
    </head>
    <body>
        <div id="content">{{ $content|raw }}</div>
        <div id="footer">
            {{ $footer|raw }}
        </div>
    </body>
</html>
```

`child.ktemplate`:

```html
{% let $head %}
    <style type="text/css">
        .important { color: #336699; }
    </style>
{% end %}
{% let $content %}
    <h1>Index</h1>
    <p class="important">
        Welcome to my awesome homepage.
    </p>
{% end %}

{% include "base.ktemplate" %}
    {% arg $title = "Index" %}
    {% arg $head = $head %}
    {% arg $content = $content %}
{% end %}
```

Note that there is no `parent()` feature in KTemplate.

* `extends` becomes `include` and goes to the end of the template
* `block` on the **clildren** side is `arg`
* `block` on the **parent** side is declared as `param`, rendered as normal local variable

There is no extra magic here: inheritance-style templates are identical to the normal "partial" templates or any other included template. KTemplate templates are like functions, when you invoke them, you can pass arguments that will override the default values of the template parameters.
