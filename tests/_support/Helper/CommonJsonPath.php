<?php namespace Helper;

use Codeception\Util\JsonArray;
use Codeception\Util\JsonType;
use Flow\JSONPath\JSONPath;

trait CommonJsonPath
{
    //-----------------------------------
    // JSON Path
    //-----------------------------------

    // match

    public function seeJSONPath($data, $json_path, callable $callback = null)
    {
        $result = (new JSONPath($data))->find($json_path);

        $this->assertTrue(
            $result->valid(),
            "JSON did not match the JsonPath provided\n{$json_path}"
        );

        if (is_null($callback)) {
            return;
        }

        $data = $result->data();

        $this->assertNotEmpty($data, "JSON match is empty\n");

        array_map(function($item) use ($callback) {
            $callback($item);
        }, $data);
    }

    public function seeNotJSONPath($data, $json_path)
    {
        $result = (new JSONPath($data))->find($json_path);

        $this->assertFalse($result->valid(), "JSON matched the JsonPath provided\n{$json_path}");
    }

    // length

    public function seeJsonPathLength(array $data, $json_path, $expected_length)
    {
        $this->assertSame(
            $expected_length,
            count($data),
            "JSON did not match expected length {$expected_length}\n{$json_path}"
        );
    }

    // same

    public function seeJsonPathSame(array $data, $json_path, $expected)
    {
        $this->seeJSONPath($data, $json_path, function($item) use ($expected) {
            $this->assertSame($expected, $item, "JSON does not match expected\n{$expected}");
        });
    }

    public function seeJsonPathNotSame(array $data, $json_path, $expected)
    {
        $this->seeJSONPath($data, $json_path, function($item) use ($expected) {
            $this->assertNotSame($expected, $item, "JSON does not match expected\n{$expected}");
        });
    }

    // null

    public function seeJsonPathNull(array $data, $json_path)
    {
        $this->seeJSONPath($data, $json_path, function($item) {
            $this->assertNull($item, "JSON is not null");
        });
    }

    public function seeJsonPathNotNull(array $data, $json_path)
    {
        $this->seeJSONPath($data, $json_path, function($item) {
            $this->assertNotNull($item, "JSON is null");
        });
    }

    // empty

    public function seeJsonPathEmpty(array $data, $json_path)
    {
        $this->seeJSONPath($data, $json_path, function($item) {
            $this->assertEmpty($item, "JSON is not empty");
        });
    }

    public function seeJsonPathNotEmpty(array $data, $json_path)
    {
        $this->seeJSONPath($data, $json_path, function($item) {
            $this->assertNotEmpty($item, "JSON is empty");
        });
    }

    // data type

    public function seeJsonPathType(array $data, $json_path, $type)
    {
        $json = json_encode($data);
        $json_array = new JsonArray($json);

        if ($json_path) {
            $json_array = $json_array->filterByJsonPath($json_path);
        }

        $this->assertNotEmpty($json_array, "JSON is empty");

        // ensure each item is an array for comparison
        $json_array = array_map(function($item) {
            return [ $item ];
        }, $json_array);

        $json_type = new JsonType($json_array);
        $matched = $json_type->matches([ $type ]);

        $this->assertTrue($matched, "JSON match is not of type\n{$type}");
    }

    // regex

    public function seeJsonPathRegex(array $data, $json_path, $regex)
    {
        $this->seeJSONPath($data, $json_path, function($item) use ($regex) {
            $this->assertNotNull($item, "could not match null JSON");
            $this->assertRegExp($regex, $item, "JSON does not match regex\n{$regex}");
        });
    }

    public function seeJsonPathNotRegex(array $data, $json_path, $regex)
    {
        $this->seeJSONPath($data, $json_path, function($item) use ($regex) {
            $this->assertNotNull($item, "could not match null JSON");
            $this->assertNotRegExp($regex, $item, "JSON does not match regex\n{$regex}");
        });
    }

    // regex : all once
    // asserts that each expression in regex array is matched once with a in json path array item

    public function seeJsonPathRegexMatchAllOnce(array $data, $json_path, $regex)
    {
        $result = (new JSONPath($data))->find($json_path);

        $this->assertTrue($result->valid(), "JSON did not match the JsonPath provided\n{$json_path}");

        $data = $result->data();

        $this->assertNotEmpty($data, "JSON match is empty\n");

        $results = array_reduce($data, function($carry, $item) use ($regex) {
            return array_reduce($regex, function($carry, $regex_item) use ($item) {
                if (!array_key_exists($regex_item, $carry)) {
                    $carry[$regex_item] = 0;
                }
                $carry[$regex_item] = $carry[$regex_item] + (preg_match($regex_item, $item) ? 1 : 0);
                return $carry;
            }, $carry);
        }, []);

        foreach ($results as $key => $result) {
            $this->assertSame(1, $result, "Regex was not matched once in JSON\n{$key}");
        }
    }
}
