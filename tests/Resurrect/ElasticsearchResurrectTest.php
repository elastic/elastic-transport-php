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

namespace Elastic\Transport\Test\Resurrect;

use Elastic\Transport\ConnectionPool\Connection;
use Elastic\Transport\ConnectionPool\Resurrect\ElasticsearchResurrect;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;

final class ElasticsearchResurrectTest extends TestCase
{
    /**
     * @var MockHandler
     */
    private $mock;

    /**
     * @var HandlerStack
     */
    private $handlerStack;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var Connection
     */
    private $connection;

    public function setUp(): void
    {
        $this->mock = new MockHandler();
        $this->handlerStack = HandlerStack::create($this->mock);
        $this->client = new Client(['handler' => $this->handlerStack]);

        $this->connection = $this->createStub(Connection::class);
    }

    public function testPingIsTrue()
    {
        $expectedResponse = new Response(200);
        $this->mock->append($expectedResponse);

        $resurrect = new ElasticsearchResurrect($this->client);
        $this->assertTrue($resurrect->ping($this->connection));
    }

    public function testPingIsFalse()
    {
        $expectedResponse = new Response(500);
        $this->mock->append($expectedResponse);

        $resurrect = new ElasticsearchResurrect($this->client);
        $this->assertFalse($resurrect->ping($this->connection));
    }

    public function testPingIsFalseOnConnectionError()
    {
        $uri = new Uri('localhost');
        $this->connection->method('getUri')
            ->willReturn($uri);

        $this->mock->append(
            new RequestException('Error Communicating with Server', new Request('HEAD', 'test'))
        );
        $resurrect = new ElasticsearchResurrect($this->client);

        $this->assertFalse($resurrect->ping($this->connection));
    }
}