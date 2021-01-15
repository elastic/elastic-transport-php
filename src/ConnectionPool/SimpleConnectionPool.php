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

namespace Elastic\Transport\ConnectionPool;

use Elastic\Transport\ConnectionPool\Resurrect\NoResurrect;
use Elastic\Transport\ConnectionPool\Resurrect\ResurrectInterface;
use Elastic\Transport\ConnectionPool\Selector\RoundRobin;
use Elastic\Transport\ConnectionPool\Selector\SelectorInterface;
use Elastic\Transport\Exception;
use GuzzleHttp\Psr7\Uri;

class SimpleConnectionPool implements ConnectionPoolInterface
{
    protected $connections = [];
    protected $selector;

    public function __construct(SelectorInterface $selector = null, ResurrectInterface $resurrect = null)
    {   
        $this->selector = $selector ?? new RoundRobin;
        $this->resurrect = $resurrect ?? new NoResurrect;
    }

    public function setHosts(array $hosts): void
    {
        $this->connections = [];
        foreach ($hosts as $host) {
            $this->connections[] = new Connection($host);
        }
        shuffle($this->connections); // randomize for use different hosts on each execution
        $this->selector->setConnections($this->connections);
    }

    public function nextConnection(): Connection
    {
        $totConnections = count($this->connections);
        $dead = 0;

        while ($dead < $totConnections) {
            $next = $this->selector->nextConnection();
            if ($next->isAlive()) {
                return $next;
            }
            if ($this->resurrect->ping($next)) {
                $next->markAlive(true);
                return $next;
            }
            $dead++;
        }

        throw new Exception\NoAliveException(sprintf(
            'No alive connection. All the %d nodes seem to be down.',
            $totConnections
        ));
    }
}