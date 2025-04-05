<?php
/**
 * Elastic Transport
 *
 * @link      https://github.com/elastic/elastic-transport-php
 * @copyright Copyright (c) Elasticsearch B.V (https://www.elastic.co)
 * @license   https://opensource.org/licenses/MIT MIT License
 *
 * Licensed to Elasticsearch B.V under one or more agreements.
 * Elasticsearch B.V licenses this file to you under the MIT License.
 * See the LICENSE file in the project root for more information.
 */
declare(strict_types=1);

namespace Elastic\Transport\Test\Serializer;

use Elastic\Transport\Serializer\NDJsonSerializer;
use PHPUnit\Framework\TestCase;

final class NDJsonArraySerializerTest extends TestCase
{
    /**
     * @var string
     */
    private $ndjson;

    public function setUp(): void
    {
        $this->ndjson = <<<'EOT'
{"index":{"_index":"test","_id":"1"}}
{"field1":"value1"}
{"delete":{"_index":"test","_id":"2"}}

EOT;
    }

    public function testUnserialize()
    {
        $result = NDJsonSerializer::unserialize($this->ndjson);
        $this->assertIsArray($result);
        $this->assertEquals('test', $result[0]['index']['_index']);
        $this->assertEquals('value1', $result[1]['field1']);
        $this->assertEquals('2', $result[2]['delete']['_id']);
    }

    public function testSerialize()
    {
        $data = [
            [
                'index' => [
                    '_index' => 'test',
                    '_id' => '1'
                ]
            ],
            [
                'field1' => 'value1'
            ],
            [
                'delete' => [
                    '_index' => 'test',
                    '_id' => '2'
                ]
            ]
        ];
        $result = NDJsonSerializer::serialize($data);
        $this->assertEquals($this->ndjson, $result);
    }

    public function testSerializeWithEmptyElement()
    {
        $ndjson = <<<'EOT'
{"index":{"_index":"test","_id":"1"}}
{}
{"delete":{"_index":"test","_id":"2"}}

EOT;

        $data = [
            [
                'index' => [
                    '_index' => 'test',
                    '_id' => '1'
                ]
            ],
            [],
            [
                'delete' => [
                    '_index' => 'test',
                    '_id' => '2'
                ]
            ]
        ];
        $result = NDJsonSerializer::serialize($data);
        $this->assertEquals($ndjson, $result);
    }
}