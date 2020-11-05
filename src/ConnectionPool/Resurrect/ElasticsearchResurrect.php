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

namespace Elastic\Transport\ConnectionPool\Resurrect;

use Elastic\Transport\ConnectionPool\Connection;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientExceptionInterface;

class ElasticsearchResurrect extends AbstractResurrect
{
    public function ping(Connection $connection): bool
    {
        $url = sprintf("%s:%s", $connection->getUrl(), $connection->getPort());

        try {
            $response = $this->client->sendRequest(new Request('HEAD', $url));
        } catch (ClientExceptionInterface $e) {
            return false;
        }
        return $response->getStatusCode() === 200;
    }
}