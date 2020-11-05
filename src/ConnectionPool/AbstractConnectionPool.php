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

namespace Elastic\Transport\ConnectionPool;

use Elastic\Transport\ConnectionPool\Resurrect\FalseResurrect;
use Elastic\Transport\ConnectionPool\Resurrect\ResurrectInterface;
use Elastic\Transport\ConnectionPool\Selector\RoundRobin;
use Elastic\Transport\ConnectionPool\Selector\SelectorInterface;
use GuzzleHttp\Psr7\Uri;

abstract class AbstractConnectionPool implements ConnectionPoolInterface
{
    protected $connections = [];
    protected $urls;
    protected $selector;

    public function __construct(array $urls, SelectorInterface $selector = null, ResurrectInterface $resurrect = null)
    {
        $this->urls = $urls;
        foreach ($urls as $url) {
            $this->connections[] = new Connection(new Uri($url));
        }
        
        $this->selector = $selector ?? new RoundRobin();
        $this->selector->setConnections($this->connections);

        $this->resurrect = $resurrect ?? new FalseResurrect();
    }

    public function nextConnection(): ?Connection
    {
        $totConnections = count($this->connections);
        $dead = 0;

        do {
            $next = $this->selector->nextConnection();
            if ($next->isAlive()) {
                return $next;
            }
            if ($this->resurrect->ping($next)) {
                $next->markAlive(true);
                return $next;
            }
            $dead++;
        } while ($dead <= $totConnections);

        return null;
    }
}