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

namespace Elastic\Transport\Test\Transport;

use Elastic\Transport\NodePool\Resurrect\NoResurrect;
use Elastic\Transport\NodePool\Selector\RoundRobin;
use Elastic\Transport\NodePool\SimpleNodePool;
use Elastic\Transport\Transport;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Mock\Client;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class SimpleNodePool_RoundRobin_NoResurrectTest extends TestCase
{
    private $client;

    private $nodePool;
    private $logger;
    private $transport;

    public function setUp(): void
    {
        $this->client = new Client();
        $this->nodePool = new SimpleNodePool(
            new RoundRobin(),
            new NoResurrect()
        );
        $this->logger = new NullLogger();
        $this->transport = new Transport($this->client, $this->nodePool, $this->logger);

        $this->requestFactory = Psr17FactoryDiscovery::findRequestFactory();
        $this->responseFactory = Psr17FactoryDiscovery::findResponseFactory();
    }


    public function testSendRequest()
    {
        $hosts = [
            '192.168.0.1:9200',
            '192.168.0.2:9200'
        ];
        $this->nodePool->setHosts($hosts);

        $expectedResponse = $this->responseFactory->createResponse(200);
        $this->client->addResponse($expectedResponse);
        $this->client->addResponse($expectedResponse);

        $request = $this->requestFactory->createRequest('GET', '/');
        $response = $this->transport->sendRequest($request);
        $this->assertEquals(200, $response->getStatusCode());

        // Check if the behaviour is Round-robin
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