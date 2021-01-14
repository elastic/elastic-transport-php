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

namespace Elastic\Transport\ConnectionPool\Selector;

use Elastic\Transport\ConnectionPool\Connection;
use Elastic\Transport\Exception\InvalidArrayException;
use Elastic\Transport\Exception\NoConnectionAvailableException;

class RoundRobin implements SelectorInterface
{
    use SelectorTrait;

    public function nextConnection(): Connection
    {
        if (empty($this->connections)) {
            $className = substr(__CLASS__, strrpos(__CLASS__, '\\') + 1);
            throw new NoConnectionAvailableException(sprintf(
                "No connection available. Please use %s::setConnections() before calling %s::nextConnection().",
                $className,
                $className
            ));
        }
        $current = current($this->connections);
        if (false === next($this->connections)) {
            reset($this->connections);
        }
        return $current;
    }   
}