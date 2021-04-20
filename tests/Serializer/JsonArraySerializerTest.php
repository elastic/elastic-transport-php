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

final class JsonArraySerializerTest extends TestCase
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

        $result = JsonSerializer::unserialize($json);
        $this->assertIsArray($result);
        $this->assertEquals('Apple', $result['fruit']);
        $this->assertEquals('Large', $result['size']);
        $this->assertEquals('Red', $result['color']);
    }

    public function testSerialize()
    {
        $json = '{"fruit":"Apple","size":"Large","color":"Red"}';
        $data = [
            'fruit' => 'Apple',
            'size'  => 'Large',
            'color' => 'Red'
        ];
        $result = JsonSerializer::serialize($data);
        $this->assertEquals($json, $result);
    }

    public function testSerializeEmptyArray()
    {
        $json = '{}';
        $data = [];
        $result = JsonSerializer::serialize($data);
        $this->assertEquals($json, $result);
    }

    public function testSerializeArrayWithEmptyValues()
    {
        $json = '{"fruit":"Apple","color":"Red"}';
        $data = [
            'fruit' => 'Apple',
            'size'  => null,
            'color' => 'Red'
        ];
        $result = JsonSerializer::serialize($data);
        $this->assertEquals($json, $result);
    }

    public function testSerializeArrayWithArrayAndEmptyValues()
    {
        $json = '{"fruit":"Apple","size":{"format":{"label":"xl"}},"color":"Red"}';
        $data = [
            'fruit' => 'Apple',
            'size'  => [
                'format' => [
                    'dimension' => null,
                    'label' => 'xl'
                ],
                'value' => null
            ],
            'color' => 'Red'
        ];
        $result = JsonSerializer::serialize($data);
        $this->assertEquals($json, $result);
    }
}