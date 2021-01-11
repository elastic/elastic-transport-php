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

namespace Elastic\Transport\Serializer;

use Elastic\Transport\Exception\InvalidJsonException;
use JsonException;
use Psr\Http\Message\ResponseInterface;

use function json_decode;
use function json_encode;
use function sprintf;

class JsonArraySerializer implements SerializerInterface
{
    public function serialize($data): string
    {
        if (empty($data)) {
            return '{}';
        }
        try {
            return json_encode($data, JSON_PRESERVE_ZERO_FRACTION + JSON_INVALID_UTF8_SUBSTITUTE + JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidJsonException(sprintf(
                "I cannot serialize to Json: %s", 
                $e->getMessage()
            ));
        }
    }

    /**
     * @return array
     */
    public function deserialize(ResponseInterface $response)
    {
        try {
            return json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidJsonException(sprintf(
                "Not a valid Json: %s", 
                $e->getMessage()
            ));
        }
    }
}