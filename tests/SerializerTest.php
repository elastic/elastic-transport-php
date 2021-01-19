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

namespace Elastic\Transport\Test;

use Elastic\Transport\Exception\InvalidSerializerException;
use Elastic\Transport\Exception\UnknownContentTypeException;
use Elastic\Transport\Response;
use Elastic\Transport\Serializer;
use Elastic\Transport\Serializer\CsvSerializer;
use Elastic\Transport\Serializer\JsonArraySerializer;
use Elastic\Transport\Serializer\NDJsonArraySerializer;
use Elastic\Transport\Serializer\SerializerInterface;
use Elastic\Transport\Serializer\TextSerializer;
use Elastic\Transport\Serializer\XmlSerializer;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use SimpleXMLElement;
use stdClass;

use function PHPUnit\Framework\assertInstanceOf;

final class SerializerTest extends TestCase
{
    public function setUp(): void
    {
        $this->serializer = new Serializer();
        $this->response = $this->createStub(ResponseInterface::class);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testSetContentType()
    {
        $customSerializer = $this->createStub(SerializerInterface::class);
        $this->serializer->setContentType('xxx', $customSerializer);
    }

    public function getContentType()
    {
        return [
            ['application/json', '{}', []],
            ['application/json', '{"foo":"bar"}', ['foo' => 'bar']],
            ['application/x-ndjson', "{\"foo\":\"bar\"}\n{}\n", [['foo' => 'bar'], []]],
            ['application/xml', '<?xml version="1.0"?><document></document>', new SimpleXMLElement('<document></document>')],
            ['text/xml', '<?xml version="1.0"?><document></document>', new SimpleXMLElement('<document></document>')],
            ['text/plain', 'Hello World!', 'Hello World!'],
            ['text/csv', "1,2,3\n4,5,6", [[1,2,3], [4,5,6]]]
        ];
    }

    /**
     * @dataProvider getContentType
     */
    public function testDeserializeResponse(string $type, string $body, $deserialized)
    {
        $this->response->method('getHeaderLine')
            ->with($this->equalTo('Content-Type'))
            ->willReturn($type);

        $stream = $this->createStub(StreamInterface::class);
        $stream->method('getContents')
            ->willReturn($body);

        $this->response->method('getBody')
            ->willReturn($stream);

        $result = $this->serializer->deserializeResponse($this->response);

        $this->assertEquals($result, $deserialized);
    }
}