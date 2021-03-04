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

use ArrayAccess;
use Elastic\Transport\Serializer\NDJsonObjectSerializer;
use PHPUnit\Framework\TestCase;
use stdClass;

final class NDJsonObjectSerializerTest extends TestCase
{
    /**
     * @var string
     */
    private $json;

    public function setUp(): void
    {
        $this->json = <<<'EOT'
{"index":{"_index":"test","_id":"1"}}
{"field1":"value1"}
{"delete":{"_index":"test","_id":"2"}}

EOT;
    }

    public function testUnserialize()
    {
        $result = NDJsonObjectSerializer::unserialize($this->json);
        $this->assertInstanceOf(ArrayAccess::class, $result);
        foreach ($result as $res) {
            $this->assertInstanceOf(stdClass::class, $res);
        }
        $this->assertEquals('test', $result[0]->index->_index);
        $this->assertEquals('value1', $result[1]->field1);
        $this->assertEquals('2', $result[2]->delete->_id);
    }

    public function testSerialize()
    {
        $data = [
            (object) [
                'index' => [
                    '_index' => 'test',
                    '_id' => '1'
                ]
            ],
            (object) [
                'field1' => 'value1'
            ],
            (object) [
                'delete' => [
                    '_index' => 'test',
                    '_id' => '2'
                ]
            ]
        ];
        $result = NDJsonObjectSerializer::serialize($data);
        $this->assertEquals($this->json, $result);
    }
}