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

use Elastic\Transport\Exception\UnknownContentTypeException;
use Elastic\Transport\Response;
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

use function PHPUnit\Framework\assertInstanceOf;

final class ResponseTest extends TestCase
{
    public function setUp(): void
    {
        $this->psr7Response = $this->createStub(ResponseInterface::class);
        $this->stream = $this->createStub(StreamInterface::class);
        $this->psr7Response->method('getBody')
            ->willReturn($this->stream);
    }

    public function testConstructorWithoutContentTypeHeader()
    {
        $this->psr7Response->method('hasHeader')
            ->with($this->equalTo('Content-Type'))
            ->willReturn(false);

        $response = new Response($this->psr7Response);
        $this->assertInstanceOf(Response::class, $response);
    }

    public function getContentType()
    {
        return [
            ['application/json', JsonArraySerializer::class, '{}'],
            ['application/json', JsonArraySerializer::class, '{"foo":"bar"}'],
            ['application/x-ndjson', NDJsonArraySerializer::class, "{\"foo\":\"bar\"}\n{}\n"],
            ['application/xml', XmlSerializer::class, '<?xml version="1.0"?><document></document>'],
            ['text/xml', XmlSerializer::class, '<?xml version="1.0"?><document></document>'],
            ['text/plain', TextSerializer::class, 'Hello World!'],
            ['text/csv', CsvSerializer::class, "1,2,3\n4,5,6"]
        ];
    }

    /**
     * @dataProvider getContentType
     */
    public function testConstructorWithContentTypeResponse($type, $serializerName, $body)
    {
        $this->psr7Response->method('hasHeader')
            ->with($this->equalTo('Content-Type'))
            ->willReturn(true);

        $this->psr7Response->method('getHeaderLine')
            ->with($this->equalTo('Content-Type'))
            ->willReturn($type);

        $this->stream->method('getContents')
            ->willReturn($body);

        $response = new Response($this->psr7Response);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertInstanceOf($serializerName, $response->getSerializer());
    }

    public function testConstructorWithUnknownTypeResponse()
    {
        $this->psr7Response->method('hasHeader')
            ->with($this->equalTo('Content-Type'))
            ->willReturn(true);

        $this->psr7Response->method('getHeaderLine')
            ->with($this->equalTo('Content-Type'))
            ->willReturn('xxx');

        $this->expectException(UnknownContentTypeException::class);
        $response = new Response($this->psr7Response);
    }

    public function testGetSerializerWithDefault()
    {
        $this->psr7Response->method('hasHeader')
            ->with($this->equalTo('Content-Type'))
            ->willReturn(true);

        $this->psr7Response->method('getHeaderLine')
            ->with($this->equalTo('Content-Type'))
            ->willReturn('application/json');

        $response = new Response($this->psr7Response);
        $this->assertInstanceOf(JsonArraySerializer::class, $response->getSerializer());
    }

    public function testGetSerializerWithCustomJsonSerializer()
    {
        $this->psr7Response->method('hasHeader')
            ->with($this->equalTo('Content-Type'))
            ->willReturn(true);

        $this->psr7Response->method('getHeaderLine')
            ->with($this->equalTo('Content-Type'))
            ->willReturn('application/json');

        $customSerializer = $this->createStub(SerializerInterface::class);
        $contentType = [
            'application/json' => $customSerializer
        ];

        $response = new Response($this->psr7Response, $contentType);
        $this->assertEquals($customSerializer, $response->getSerializer());
    }

    public function testGetResponseWithArrayJson()
    {
        $this->psr7Response->method('hasHeader')
            ->with($this->equalTo('Content-Type'))
            ->willReturn(true);

        $this->psr7Response->method('getHeaderLine')
            ->with($this->equalTo('Content-Type'))
            ->willReturn('application/json');

        $this->stream->method('getContents')
            ->willReturn('{"foo":"bar"}');

        $response = new Response($this->psr7Response);
        $this->assertEquals(['foo' => 'bar'], $response->getResponse());
    }
}