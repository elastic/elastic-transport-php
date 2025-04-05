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

namespace Elastic\Transport\Test;

use Elastic\Transport\Exception\NoNodeAvailableException;
use Elastic\Transport\NodePool\Node;
use Elastic\Transport\NodePool\NodePoolInterface;
use Elastic\Transport\Transport;
use Http\Client\Exception\TransferException;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Mock\Client;
use Http\Promise\Promise;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;

final class TransportWithRetriesTest extends TestCase
{
    private ClientInterface $client;
    private NodePoolInterface $nodePool;
    private LoggerInterface $logger;
    private Transport $transport;

    private RequestFactoryInterface $requestFactory;
    private ResponseFactoryInterface $responseFactory;

    public function setUp(): void
    {
        $this->client = new Client();
        $this->nodePool = $this->createStub(NodePoolInterface::class);
        
        $testLogger = sprintf("\Elastic\Transport\Test\TestLogger%d", explode('.',PHP_VERSION)[0]);
        $this->logger = new $testLogger;

        $this->transport = new Transport($this->client, $this->nodePool, $this->logger);

        $this->requestFactory = Psr17FactoryDiscovery::findRequestFactory();
        $this->responseFactory = Psr17FactoryDiscovery::findResponseFactory();
        $this->node = $this->createStub(Node::class);
    }

    public function testOneRetryWithOneFailure()
    {
        $this->transport->setRetries(1);
        $expectedResponse = $this->responseFactory->createResponse(200);
        
        $expectedException = $this->createStub(NetworkExceptionInterface::class);

        $this->client->addException($expectedException);
        $this->client->addResponse($expectedResponse);

        $request = $this->requestFactory->createRequest('GET', 'http://domain/path');
        $response = $this->transport->sendRequest($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($this->logger->hasError([
            'message' => 'Retry 0: '
        ]));
    }

    public function testOneRetryWithTwoFailure()
    {
        $this->transport->setRetries(1);
        $expectedResponse = $this->responseFactory->createResponse(200);
        
        $expectedException = $this->createStub(NetworkExceptionInterface::class);

        $this->client->addException($expectedException);
        $this->client->addException($expectedException);
        $this->client->addResponse($expectedResponse);

        $request = $this->requestFactory->createRequest('GET', 'http://domain/path');
        try {
            $response = $this->transport->sendRequest($request);
        } catch (NoNodeAvailableException $e) {
            $this->assertTrue(
                $this->logger->hasErrorThatContains('Exceeded maximum number of retries (1)')
            );
            $this->assertTrue(
                $this->logger->hasErrorThatContains('Retry 0')
            );
            $this->assertTrue(
                $this->logger->hasErrorThatContains('Retry 1')
            );
        }
    }

    public function testZeroRetryWithOneFailure()
    {
        $this->transport->setRetries(0);
        $expectedResponse = $this->responseFactory->createResponse(200);
        
        $expectedException = $this->createStub(NetworkExceptionInterface::class);

        $this->client->addException($expectedException);
        $this->client->addResponse($expectedResponse);

        $this->expectException(NoNodeAvailableException::class);
        $request = $this->requestFactory->createRequest('GET', 'http://domain/path');
        $response = $this->transport->sendRequest($request);
    }

    /**
     * @group async
     */
    public function testOneRetryWithOneFailureAsync()
    {
        $this->transport->setRetries(1);
        $expectedResponse = $this->responseFactory->createResponse(200);
        $expectedException = new TransferException();

        $this->client->addException($expectedException);
        $this->client->addResponse($expectedResponse);

        $request = $this->requestFactory->createRequest('GET', 'http://domain/path');
        $promise = $this->transport->sendAsyncRequest($request);
        $this->assertInstanceOf(Promise::class, $promise);
        $response = $promise->wait();
        $this->assertEquals($expectedResponse, $response);
        $this->assertTrue($this->logger->hasError([
            'message' => 'Retry 0: '
        ]));
        $this->assertTrue($this->logger->hasInfo([
            'message' => 'Async Response (retry 1): 200'
        ]));
    }

    /**
     * @group async
     */
    public function testOneRetryWithTwoFailureAsync()
    {
        $this->transport->setRetries(1);
        $expectedException = new TransferException();

        $this->client->addException($expectedException);
        $this->client->addException($expectedException);

        $request = $this->requestFactory->createRequest('GET', 'http://domain/path');
        $this->expectException(NoNodeAvailableException::class);
        $promise = $this->transport->sendAsyncRequest($request);
    }

    public function testTwoRetryWithOneFailureAsync()
    {
        $this->transport->setRetries(2);
        $expectedResponse = $this->responseFactory->createResponse(200);
        $expectedException = new TransferException();

        $this->client->addException($expectedException);
        $this->client->addResponse($expectedResponse);

        $request = $this->requestFactory->createRequest('GET', 'http://domain/path');
        $promise = $this->transport->sendAsyncRequest($request);
        $this->assertInstanceOf(Promise::class, $promise);
        $this->assertTrue(
            $this->logger->hasErrorThatContains('Retry 0')
        );
        $this->assertFalse(
            $this->logger->hasErrorThatContains('Retry 1')
        );
        $this->assertTrue(
            $this->logger->hasInfoThatContains('Async Response')
        );
    }

    public function testTwoRetryWithTwoFailureAsync()
    {
        $this->transport->setRetries(2);
        $expectedResponse = $this->responseFactory->createResponse(200);
        $expectedException = new TransferException();

        $this->client->addException($expectedException);
        $this->client->addException($expectedException);
        $this->client->addResponse($expectedResponse);

        $request = $this->requestFactory->createRequest('GET', 'http://domain/path');
        $promise = $this->transport->sendAsyncRequest($request);
        $this->assertInstanceOf(Promise::class, $promise);
        $this->assertTrue(
            $this->logger->hasErrorThatContains('Retry 0')
        );
        $this->assertTrue(
            $this->logger->hasErrorThatContains('Retry 1')
        );
        $this->assertFalse(
            $this->logger->hasErrorThatContains('Retry 2')
        );
        $this->assertTrue(
            $this->logger->hasInfoThatContains('Async Response')
        );
    }
}