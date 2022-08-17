<?php

use PHPUnit\Framework\TestCase;
use KTemplate\Compile\Compiler;
use KTemplate\Renderer;
use KTemplate\Internal\Strings;

class End2EndTest extends TestCase {
    public function testSingleFile() {
        $dir = __DIR__ . '/testdata/end2end/singlefile';
        $tests = [];
        foreach (scandir($dir) as $filename) {
            if (Strings::hasSuffix($filename, '.golden')) {
                continue;
            }
            if (Strings::hasPrefix($filename, '.')) {
                continue;
            }
            $tests[] = $filename;
        }

        $compiler = new Compiler();
        $renderer = new Renderer();
        foreach ($tests as $test) {
            $full_name = "$dir/$test";
            $source = (string)file_get_contents($full_name);
            $t = $compiler->compile($full_name, $source);
            $have = $renderer->render($t, null);
            if (!file_exists("$dir/$test.golden")) {
                file_put_contents("$dir/$test.golden", $have);
                $this->fail("$test: no output file found, auto-creating one");
            }
            $want = file_get_contents("$dir/$test.golden");
            $this->assertEquals($want, $have);
        }
    }
}
