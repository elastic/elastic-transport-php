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

namespace Elastic\Transport;

use Elastic\Transport\Exception\UnknownContentTypeException;
use Elastic\Transport\Serializer\CsvSerializer;
use Elastic\Transport\Serializer\JsonArraySerializer;
use Elastic\Transport\Serializer\NDJsonArraySerializer;
use Elastic\Transport\Serializer\SerializerInterface;
use Elastic\Transport\Serializer\TextSerializer;
use Elastic\Transport\Serializer\XmlSerializer;
use GuzzleHttp\Psr7\Stream;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

use function sprintf;
use function strpos;

class Serializer
{
    protected static $serializer = [];

    protected $contentType = [
        'application/json'     => JsonArraySerializer::class,
        'application/x-ndjson' => NDJsonArraySerializer::class,
        'text/xml'             => XmlSerializer::class,
        'application/xml'      => XmlSerializer::class,
        'text/plain'           => TextSerializer::class,
        'text/csv'             => CsvSerializer::class
    ];

    public function setContentType(string $type, SerializerInterface $serializer)
    {
        self::$serializer[$type] = $serializer;
        $this->contentType[$type] = get_class($serializer);
    }

    public function deserializeResponse(ResponseInterface $response)
    {
        $serializer = $this->getSerializer($response->getHeaderLine('Content-Type'));
        return $serializer->deserialize($response->getBody()->getContents());
    }

    public function serializeRequest($data, RequestInterface $request): RequestInterface
    {
        $serializer = $this->getSerializer($request->getHeaderLine('Content-Type'));
        return $request->withBody(new Stream($serializer->serialize($data)));
    }

    protected function getSerializer(string $contentType): SerializerInterface
    {
        foreach ($this->contentType as $type => $className) {
            if (strpos($contentType, $type) !== false) {
                if (!isset(self::$serializer[$type])) {
                    self::$serializer[$type] = new $className;
                }
                return self::$serializer[$type];
            }
        }
        throw new UnknownContentTypeException(sprintf(
            "I don't have a serializer for Content-Type: %s",
            $contentType
        ));
    }
}