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

namespace Elastic\Transport\ConnectionPool\Resurrect;

use Elastic\Transport\ConnectionPool\Connection;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\ClientExceptionInterface;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request;

class ElasticsearchResurrect implements ResurrectInterface
{
    /**
     * @var ClientInterface
     */
    protected $client;

    public function __construct(ClientInterface $client = null)
    {
        $this->client = $client ?? new GuzzleClient;
    }

    public function ping(Connection $connection): bool
    {
        $request = new Request("HEAD", $connection->getUri());
        try {
            $response = $this->client->sendRequest($request);
            return $response->getStatusCode() === 200;
        } catch (ClientExceptionInterface $e) {
            return false;
        }
    }
}