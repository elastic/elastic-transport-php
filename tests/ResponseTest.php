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

use Elastic\Transport\Response;
use Elastic\Transport\Serializer\JsonArraySerializer;
use Elastic\Transport\Serializer\NDJsonArraySerializer;
use Elastic\Transport\Serializer\TextSerializer;
use Elastic\Transport\Serializer\XmlSerializer;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

final class ResponseTest extends TestCase
{
    public function setUp(): void
    {
        $this->psr7Response = $this->createStub(ResponseInterface::class);
        $this->stream = $this->createStub(StreamInterface::class);
        $this->psr7Response->method('getBody')
            ->willReturn($this->stream);
    }

    public function testConstructorWithoutContentTypeResponse()
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
            ['text/plain', TextSerializer::class, 'Hello World!']
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

        $this->stream->method('getContents')
            ->willReturn('yyy');

        $response = new Response($this->psr7Response);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertNull($response->getSerializer());
    }
}