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

use Elastic\Transport\ConnectionPool\SimpleConnectionPool;
use Elastic\Transport\Transport;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

use Psr\Log\Test\TestLogger;

final class TransportithSimpleConnectionPoolTest extends TestCase
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

        $this->connectionPool = new SimpleConnectionPool();

        $this->logger = new TestLogger();
        $this->transport = new Transport($this->client, $this->connectionPool, $this->logger);
    }


    public function testSendRequestWithDefaultSimpleConnectionPoolParams()
    {
        $hosts = [
            '192.168.0.1:9200',
            '192.168.0.2:9200'
        ];
        $this->connectionPool->setHosts($hosts);

        $expectedResponse = new Response(200);
        $this->mock->append(
            $expectedResponse,
            $expectedResponse
        );

        $request = new Request('GET', '/');
        $response = $this->transport->sendRequest($request);
        $this->assertEquals(200, $response->getStatusCode());

        // Check if the behaviour is Round-robin (default Selector of SimpleConnectionPool)
        $host1 = $this->transport->getLastRequest()->getUri()->getHost();
        $port1 = $this->transport->getLastRequest()->getUri()->getPort();
        $url1 = sprintf("%s:%s", $host1, $port1);
        $this->assertContains($url1, $hosts);

        $response = $this->transport->sendRequest($request);
        $this->assertEquals(200, $response->getStatusCode());

        $host2 = $this->transport->getLastRequest()->getUri()->getHost();
        $port2 = $this->transport->getLastRequest()->getUri()->getPort();
        $url2 = sprintf("%s:%s", $host2, $port2);
        $this->assertContains($url2, $hosts);
        $this->assertNotEquals($url1, $url2);
    }
}