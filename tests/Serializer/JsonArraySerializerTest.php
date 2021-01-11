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

use Elastic\Transport\Serializer\JsonArraySerializer;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

final class JsonArraySerializerTest extends TestCase
{
    public function setUp(): void
    {
        $this->serializer = new JsonArraySerializer();
        $this->request = $this->createStub(ResponseInterface::class);
        $this->stream = $this->createStub(StreamInterface::class);

        $this->request->method('getBody')
            ->willReturn($this->stream);
    }

    public function testDeserialize()
    {
        $json = <<<'EOT'
{
    "fruit": "Apple",
    "size": "Large",
    "color": "Red"
}
EOT;

        $this->stream->method('getContents')
            ->willReturn($json);

        $result = $this->serializer->deserialize($this->request);
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
            'size' => 'Large',
            'color' => 'Red'
        ];
        $result = $this->serializer->serialize($data);
        $this->assertEquals($json, $result);
    }

    public function testSerializeEmptyArray()
    {
        $json = '{}';
        $data = [];
        $result = $this->serializer->serialize($data);
        $this->assertEquals($json, $result);
    }
}