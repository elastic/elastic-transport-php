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
 * Elasticsearch B.V licenses this file to you under the Apache 2.0 License.
 * See the LICENSE file in the project root for more information.
 */
declare(strict_types=1);

namespace Elastic\Transport;

use Elastic\Transport\ConnectionPool\ConnectionPoolInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Transport implements ClientInterface
{
    private LoggerInterface $logger;
    private ConnectionPoolInterface $connectionPool;

    public function __construct(
        ClientInterface $client,
        ConnectionPoolInterface $connectionPool,
        LoggerInterface $logger = null
    ) {
        $this->client = $client;
        $this->connectionPool = $connectionPool;
        $this->logger = $logger ?? new NullLogger();
    }

    public function getLastRequest() : RequestInterface
    {
        return $this->lastRequest;
    }

    public function getLastResponse() : ResponseInterface
    {
        return $this->lastResponse;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->lastRequest = $request;

        // Get the host to be connected
        $connection = $this->connectionPool->nextConnection();

        try {
            $response = $this->client->sendRequest($request);

            $this->logger->info(sprintf(
                "Request: %s %s", 
                $request->getMethod(),
                $request->getUri()->getPath()
            ));
            $this->logger->info(sprintf(
                "Response: %s %s", 
                $response->getStatusCode(),
                $response->getBody()->getContents()
            ));
            $this->lastResponse = $response;
    
            return $response;
        } catch (NetworkExceptionInterface $e) {
            $this->logger->error($e->getMessage());
            $connection->markAlive(false);
            throw $e;
        } catch (ClientExceptionInterface $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        }
    }
}