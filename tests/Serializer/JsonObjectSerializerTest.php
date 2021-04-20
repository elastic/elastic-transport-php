<?php
/**
 * Elastic Transport
 *
 * @link      https://github.com/elastic/elastic-transport-php
 * @copyright Copyright (c) Elasticsearch B.V (https://www.elastic.co)
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 *
 * Licensed to Elasticsearch B.V under one or more agreements.
 * Elasticsearch B.V licenses this file to you under the Apache 2.0 License.
 * See the LICENSE file in the project root for more information.
 */
declare(strict_types=1);

namespace Elastic\Transport\Test\Serializer;

use Elastic\Transport\Serializer\JsonSerializer;
use PHPUnit\Framework\TestCase;
use stdClass;

final class JsonObjectSerializerTest extends TestCase
{
    public function testUnserialize()
    {
        $json = <<<'EOT'
{
    "fruit": "Apple",
    "size": "Large",
    "color": "Red"
}
EOT;

        $result = JsonSerializer::unserialize($json, ['type' => 'object']);
        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals('Apple', $result->fruit);
        $this->assertEquals('Large', $result->size);
        $this->assertEquals('Red', $result->color);
    }

    public function testSerialize()
    {
        $json = '{"fruit":"Apple","size":"Large","color":"Red"}';

        $data = new stdClass();
        $data->fruit = 'Apple';
        $data->size = 'Large';
        $data->color = 'Red';

        $result = JsonSerializer::serialize($data, ['type' => 'object']);
        $this->assertEquals($json, $result);
    }

    public function testSerializeWithNullValues()
    {
        $json = '{"fruit":"Apple","size":"Large"}';

        $data = new stdClass();
        $data->fruit = 'Apple';
        $data->size = 'Large';
        $data->color = null;

        $result = JsonSerializer::serialize($data, ['type' => 'object']);
        $this->assertEquals($json, $result);
    }

    public function testSerializeWithRecursiveNullValues()
    {
        $json = '{"fruit":"Apple","size":"Large","color":{"red":10,"blue":20,"opacity":{"foo":40,"baz":30}}}';

        $data = new stdClass();
        $data->fruit = 'Apple';
        $data->size = 'Large';

        $color = new stdClass();
        $color->red = 10;
        $color->blue = 20;
        $color->green = null;

        $opacity = new stdClass();
        $opacity->foo = 40;
        $opacity->bar = null;
        $opacity->baz = 30;

        $color->opacity = $opacity;
        $data->color = $color;

        $result = JsonSerializer::serialize($data, ['type' => 'object']);
        $this->assertEquals($json, $result);
    }
}