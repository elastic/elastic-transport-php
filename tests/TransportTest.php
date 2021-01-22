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

use Elastic\Transport\ConnectionPool\Connection;
use Elastic\Transport\ConnectionPool\ConnectionPoolInterface;
use Elastic\Transport\Transport;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\Test\TestLogger;

final class TransportTest extends TestCase
{
    private $mock;
    private $handlerStack;
    private $client;
    private $connection;
    private $connectionPool;
    private $logger;
    private $transport;

    public function setUp(): void
    {
        $this->mock = new MockHandler();
        $this->handlerStack = HandlerStack::create($this->mock);
        $this->client = new Client(['handler' => $this->handlerStack]);

        $this->connection = $this->createStub(Connection::class);
        $this->connectionPool = $this->createStub(ConnectionPoolInterface::class);
        $this->connectionPool->method('nextConnection')
            ->willReturn($this->connection);

        $this->logger = new TestLogger();
        $this->transport = new Transport($this->client, $this->connectionPool, $this->logger);
    }

    public function testSendRequestWith200Response()
    {
        $expectedResponse = new Response(200);
        $this->mock->append($expectedResponse);

        $this->connection->method('getUri')
            ->willReturn(new Uri('http://localhost'));

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
            $this->connection->method('getUri')
                ->willReturn(new Uri('http://localhost'));

            $request = new Request('GET', '/');
            $this->transport->sendRequest($request);
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
            $this->connection->method('getUri')
                ->willReturn(new Uri('http://localhost'));
            
                $request = new Request('GET', '/');
            $this->transport->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            $this->assertTrue($this->logger->hasError([
                'level'   => 'error',
                'message' => 'Error Transfer',
                'context' => []
            ]));
        }
    }

    public function testSendRequestWithEmptyHost()
    {
        $expectedResponse = new Response(200);
        $this->mock->append($expectedResponse);

        $this->connection->method('getUri')
            ->willReturn(new Uri('http://localhost'));

        $request = new Request('GET', '/');
        $response = $this->transport->sendRequest($request);

        $lastRequest = $this->transport->getLastRequest();
        $this->assertEquals('localhost', $lastRequest->getUri()->getHost());
    }

    public function testSendRequestWithHost()
    {
        $expectedResponse = new Response(200);
        $this->mock->append($expectedResponse);

        $request = new Request('GET', 'http://domain/path');
        $response = $this->transport->sendRequest($request);

        $lastRequest = $this->transport->getLastRequest();
        $this->assertEquals('domain', $lastRequest->getUri()->getHost());
        $this->assertEquals('/path', $lastRequest->getUri()->getPath());
    }

    public function testLoggerWithSendRequest()
    {
        $statusCode = 200;
        $body = 'Hello, World';
        $expectedResponse = new Response($statusCode, ['X-Foo' => 'Bar'], $body);
        $this->mock->append($expectedResponse);

        $this->connection->method('getUri')
            ->willReturn(new Uri('http://localhost'));

        $request = new Request('GET', '/');
        $response = $this->transport->sendRequest($request);

        $this->assertTrue($this->logger->hasInfo([
            'level'   => 'info',
            'message' => "Request: GET http://localhost/\nBody: ",
            'context' => []
        ]));
        $this->assertTrue($this->logger->hasDebug([
            'level'   => 'debug',
            'message' => 'Request Headers: {"Host":["localhost"]}',
            'context' => []
        ]));
        $this->assertTrue($this->logger->hasInfo([
            'level'   => 'info',
            'message' => sprintf("Response: %s\nBody: %s", $statusCode, $body),
            'context' => []
        ]));
        $this->assertTrue($this->logger->hasDebug([
            'level'   => 'debug',
            'message' => 'Response Headers: {"X-Foo":["Bar"]}',
            'context' => []
        ]));
    }

    public function testGetLastRequestWithoutPort()
    {
        $this->mock->append(new Response(200));

        $this->connection->method('getUri')
            ->willReturn(new Uri('http://localhost'));

        $request = new Request('GET', '/');
        $this->transport->sendRequest($request);

        $this->assertInstanceOf(RequestInterface::class, $this->transport->getLastRequest());
        $this->assertEquals('localhost', $this->transport->getLastRequest()->getUri()->getHost());
        $this->assertEquals('http', $this->transport->getLastRequest()->getUri()->getScheme());
        $this->assertEquals(null, $this->transport->getLastRequest()->getUri()->getPort());
        $this->assertEquals('/', $this->transport->getLastRequest()->getUri()->getPath());
    }

    public function testGetLastRequestWithSpecificPort()
    {
        $this->mock->append(new Response(200));

        $this->connection->method('getUri')
            ->willReturn(new Uri('http://localhost:9200'));

        $request = new Request('GET', '/');
        $this->transport->sendRequest($request);

        $this->assertInstanceOf(RequestInterface::class, $this->transport->getLastRequest());
        $this->assertEquals('localhost', $this->transport->getLastRequest()->getUri()->getHost());
        $this->assertEquals('http', $this->transport->getLastRequest()->getUri()->getScheme());
        $this->assertEquals(9200, $this->transport->getLastRequest()->getUri()->getPort());
        $this->assertEquals('/', $this->transport->getLastRequest()->getUri()->getPath());
    }

    public function testGetLastResponse()
    {
        $expectedResponse = new Response(200);
        $this->mock->append($expectedResponse);

        $this->connection->method('getUri')
            ->willReturn(new Uri('http://localhost'));

        $request = new Request('GET', '/');
        $response = $this->transport->sendRequest($request);

        $this->assertEquals($response, $this->transport->getLastResponse());
        $this->assertEquals($expectedResponse, $this->transport->getLastResponse());
    }

    public function testSetUserInfo()
    {
        $expectedResponse = new Response(200);
        $this->mock->append($expectedResponse);

        $this->connection->method('getUri')
            ->willReturn(new Uri('http://localhost'));

        $user = 'test';
        $password = '1234567890';

        $this->transport->setUserInfo($user, $password);

        $request = new Request('GET', '/');
        $response = $this->transport->sendRequest($request);

        $this->assertEquals(
            $user . ':' . $password,
            $this->transport->getLastRequest()->getUri()->getUserInfo(),
        );
        $this->assertEquals(
            'http://test:1234567890@localhost/',
            (string) $this->transport->getLastRequest()->getUri()
        );
    }

    public function testSetHeaders()
    {
        $expectedResponse = new Response(200);
        $this->mock->append($expectedResponse);

        $headers = [ 'X-Foo' => 'Bar' ];
        $this->transport->setHeaders($headers);

        $this->connection->method('getUri')
            ->willReturn(new Uri('http://localhost'));
        
        $request = new Request('GET', '/');
        $response = $this->transport->sendRequest($request);
        
        $this->assertTrue($this->transport->getLastRequest()->hasHeader('X-Foo'));
        $this->assertEquals($headers['X-Foo'], $this->transport->getLastRequest()->getHeader('X-Foo')[0]);
    }
}