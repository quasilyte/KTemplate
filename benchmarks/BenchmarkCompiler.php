<?php

use KTemplate\Internal\Compile\Compiler;
use KTemplate\Internal\Env;
use KTemplate\Context;


class BenchmarkCompiler {
    /** @var Compiler */
    private $compiler;
    private $env;

    public function __construct() {
        $ctx = new Context();
        $this->compiler = new Compiler();
        $this->env = new Env($ctx, null);
    }

    public function benchmarkSimple() {
        $src = '{{ x.y }}';
        return $this->compiler->compile($this->env, 'test', $src);
    }

    public function benchmarkNormal1() {
        $src = '
            {% let $v = y %}
            {% for $item in items %}
                {# comment #}
                {% let $s = $item ~ x ~ $v ~ x %}
                {% if $item %}
                    > {{ $s }}
                {% end %}
            {% end %}
        ';
        return $this->compiler->compile($this->env, 'test', $src);
    }

    public function benchmarkNormal2() {
        $base_src = '
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
        ';
        $child_src = '
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
        ';
        return [
            $this->compiler->compile($this->env, 'base.ktemplate', $base_src),
            $this->compiler->compile($this->env, 'child.ktemplate', $child_src),
        ];
    }
}
