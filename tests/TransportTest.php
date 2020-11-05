<?php
/**
 * Elastic Transport
 *
 * @link      https://github.com/elastic/elastic-transport-php
 * @copyright Copyright (c) Elasticsearch B.V (https://www.elastic.co)
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @license   https://www.gnu.org/licenses/lgpl-2.1.html GNU Lesser General Public License, Version 2.1
 *
 * Licensed to Elasticsearch B.V under one or more agreements.
 * Elasticsearch B.V licenses this file to you under the Apache 2.0 License or
 * the GNU Lesser General Public License, Version 2.1, at your option.
 * See the LICENSE file in the project root for more information.
 */
declare(strict_types=1);

namespace Elastic\Transport\Test;

use Elastic\Transport\ConnectionPool\Connection;
use Elastic\Transport\ConnectionPool\SimpleConnectionPool;
use Elastic\Transport\Transport;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\Test\TestLogger;

class TransportTest extends TestCase
{
    public function setUp(): void
    {
        $this->mock = new MockHandler();
        $this->handlerStack = HandlerStack::create($this->mock);
        $this->client = new Client(['handler' => $this->handlerStack]);

        $this->connection = $this->createStub(Connection::class);
        $this->connectionPool = $this->createStub(SimpleConnectionPool::class);
        $this->connectionPool->method('nextConnection')
            ->willReturn($this->connection);

        $this->logger = new TestLogger();

        $this->transport = new Transport(
            $this->client,
            $this->connectionPool,
            $this->logger
        );
    }

    public function testConstructorWithClientAndConnectionPool()
    {
        $transport = new Transport(
            $this->client,
            $this->connectionPool
        );

        $this->assertInstanceOf(Transport::class, $transport);
    }

    public function testConstructorWithClientAndConnectionPoolAndLogger()
    {
        $this->assertInstanceOf(Transport::class, $this->transport);
    }

    public function testSendRequest()
    {
        $expectedResponse = new Response(200);
        $this->mock->append($expectedResponse);

        $request = new Request('GET', '/');
        $response = $this->transport->sendRequest($request);
        
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($expectedResponse, $response);
    }

    public function testSendRequestWithNetworkException()
    {
        $expectedException = new ConnectException('Error Communicating with Server', new Request('GET', 'test'));
        $this->mock->append($expectedException);
       
        try {
            $this->transport->sendRequest(new Request('GET', '/'));
        } catch (NetworkExceptionInterface $e) {
            $this->assertTrue($this->logger->hasError([
                'level'   => 'error',
                'message' => 'Error Communicating with Server',
                'context' => []
            ]));
        }
    }

    public function testSendRequestWithClientException()
    {
        $expectedException = new TransferException('Error Transfer');
        $this->mock->append($expectedException);
        
        try {
            $this->transport->sendRequest(new Request('GET', '/'));
        } catch (ClientExceptionInterface $e) {
            $this->assertTrue($this->logger->hasError([
                'level'   => 'error',
                'message' => 'Error Transfer',
                'context' => []
            ]));
        }
    }

    public function testLoggerWithSendRequest()
    {
        $statusCode = 200;
        $body = 'Hello, World';

        $expectedResponse = new Response($statusCode, ['X-Foo' => 'Bar'], $body);
        $this->mock->append($expectedResponse);

        $request = new Request('GET', '/');
        $response = $this->transport->sendRequest($request);

        $this->assertTrue($this->logger->hasInfo([
            'level'   => 'info',
            'message' => 'Request: GET /',
            'context' => []
        ]));
        $this->assertTrue($this->logger->hasInfo([
            'level'   => 'info',
            'message' => sprintf("Response: %s %s", $statusCode, $body),
            'context' => []
        ]));
    }

    public function testGetLastRequest()
    {
        $this->mock->append(new Response(200));

        $request = new Request('GET', '/');
        $this->transport->sendRequest($request);

        $this->assertEquals($request, $this->transport->getLastRequest());
    }

    public function testGetLastResponse()
    {
        $expectedResponse = new Response(200, ['X-Foo' => 'Bar'], 'Hello, World');
        $this->mock->append($expectedResponse);

        $request = new Request('GET', '/');
        $response = $this->transport->sendRequest($request);

        $this->assertEquals($response, $this->transport->getLastResponse());
        $this->assertEquals($expectedResponse, $this->transport->getLastResponse());
    }
}