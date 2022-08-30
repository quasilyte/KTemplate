<?php

use PHPUnit\Framework\TestCase;
use KTemplate\Compile\Compiler;
use KTemplate\Engine;
use KTemplate\Renderer;
use KTemplate\ArrayLoader;
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

        $loader = new ArrayLoader();
        $engine = new Engine($loader);
        
        $engine->registerFilter1('strlen', function ($s) { return strlen($s); });
        $engine->registerFilter1('add1', function ($x) { return $x + 1; });
        $engine->registerFilter1('sub1', function ($x) { return $x - 1; });

        $engine->registerFilter2('add', function ($x, $delta) { return $x + $delta; });

        $engine->registerFunction0('zero', function () { return 0; });
        $engine->registerFunction1('abs', function ($x) { return abs($x); });
        $engine->registerFunction2('fmt', function ($format, $x) { return sprintf($format, $x); });
        $engine->registerFunction3('concat3', function ($s1, $s2, $s3) { return "$s1$s2$s3"; });

        $engine->registerFunction1('assert_true', function ($x) {
            return $x === true ? 'yes' : 'no';
        });
        $engine->registerFunction1('assert_false', function ($x) {
            return $x === false ? 'yes' : 'no';
        });

        $data_provider = new SimpleTestDataProvider();
        foreach ($tests as $test) {
            $full_name = "$dir/$test";
            $source = (string)file_get_contents($full_name);
            $loader->setSources([$full_name => $source]);
            $t = $engine->getTemplate($full_name);
            $data_provider->setTestName($test);
            $have = $engine->renderTemplate($t, $data_provider);
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
                    1 => 'one',
                ],
                0 => 'zero',
                'key' => 'z',
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
        case 'html_chunk':
            return '<b>boom</b>';
        case 'test_name':
            return $key->num_parts === 1 ? $this->test_name : null;
        case 'time':
            if ($key->num_parts === 1) {
                return $this->time;
            }
            if ($key->num_parts === 2) {
                return $this->time[$key->part2];
            }
            return null;
        case 'arr':
            return $key->num_parts === 1 ? $this->arr : null;
        case 'empty_arr':
            return [];
        case 'arr1':
            return [1];
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
