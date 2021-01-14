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

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

use function sprintf;

class Connection
{
    protected $uri;
    protected $alive = true;

    public function __construct(string $host)
    {
        if (substr($host, 0, 5) !== 'http:' && substr($host, 0, 6) !== 'https:') {
            $host = sprintf("http://%s", $host);
        }
        $this->uri = new Uri($host);
    }

    public function markAlive(bool $alive)
    {
        $this->alive = $alive;
    }

    public function isAlive(): bool
    {
        return $this->alive;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }
}