<?php

use PHPUnit\Framework\TestCase;
use KTemplate\Compile\Compiler;
use KTemplate\Env;
use KTemplate\Renderer;
use KTemplate\DataKey;
use KTemplate\DataProviderInterface;
use KTemplate\Internal\Strings;

class End2EndTest extends TestCase {
    public function testSingleFile() {
        $dir = __DIR__ . '/testdata/end2end/singlefile';
        $tests = [];
        foreach (scandir($dir) as $filename) {
            if (Strings::hasPrefix($filename, '.')) {
                continue;
            }
            if (Strings::hasSuffix($filename, '.golden')) {
                continue;
            }
            $tests[] = $filename;
        }

        $env = new Env();
        $env->registerFilter1('strlen', function ($s) { return strlen($s); });

        $compiler = new Compiler();
        $renderer = new Renderer();
        $data_provider = new SimpleTestDataProvider();
        foreach ($tests as $test) {
            $full_name = "$dir/$test";
            $source = (string)file_get_contents($full_name);
            $t = $compiler->compile($env, $full_name, $source);
            $data_provider->setTestName($test);
            $have = $renderer->render($env, $t, $data_provider);
            if (!file_exists("$dir/$test.golden")) {
                file_put_contents("$dir/$test.golden", $have);
                $this->fail("$test: no output file found, auto-creating one");
            }
            $want = file_get_contents("$dir/$test.golden");
            $this->assertEquals($want, $have);
        }
    }
}

class SimpleTestDataProvider implements DataProviderInterface {
    private $test_name;
    private $arr;
    private $time;

    public function __construct() {
        $this->arr = [
            'x' => [
                'y' => [
                    'z' => 111,
                ],
            ],
        ];
        $this->time = [
            'year' => 2022,
            'month' => 'August',
        ];
    }

    public function setTestName($name) {
        $this->test_name = $name;
    }

    public function getData($key) {
        switch ($key->part1) {
        case 'test_name':
            return $key->num_parts === 1 ? $this->test_name : null;
        case 'time':
            if ($key->num_parts === 2) {
                return $this->time[$key->part2];
            }
            return null;
        default:
            switch ($key->num_parts) {
            case 1:
                return $this->arr[$key->part1];
            case 2:
                return $this->arr[$key->part1][$key->part2];
            default:
                return $this->arr[$key->part1][$key->part2][$key->part3];
            }
        }
        return null;
    }
}
