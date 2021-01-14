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

use Elastic\Transport\Serializer\JsonArraySerializer;
use Elastic\Transport\Serializer\NDJsonArraySerializer;
use Elastic\Transport\Serializer\SerializerInterface;
use Elastic\Transport\Serializer\TextSerializer;
use Elastic\Transport\Serializer\XmlSerializer;
use Psr\Http\Message\ResponseInterface;

class Response
{
    protected $response;
    protected $serializer;
    protected $deserialized;

    const CONTENT_TYPE = [
        'application/json'     => JsonArraySerializer::class,
        'application/x-ndjson' => NDJsonArraySerializer::class,
        'text/xml'             => XmlSerializer::class,
        'application/xml'      => XmlSerializer::class,
        'text/plain'           => TextSerializer::class
    ];

    public function __construct(ResponseInterface $response, SerializerInterface $serializer = null)
    {
        $this->response = $response;

        if (null !== $serializer) {
            $this->serializer = $serializer;
            $this->deserialized = $serializer->deserialize($response);
            return;
        }
        if (! $response->hasHeader('Content-Type')) {
            return;
        }
        foreach (self::CONTENT_TYPE as $type => $serializerName) {
            if (strpos($response->getHeaderLine('Content-Type'), $type) !== false) {
                $this->serializer = new $serializerName;
                $this->deserialized = $this->serializer->deserialize($response);
                break;
            }
        }
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    public function getDeserializedResponse()
    {
        return $this->deserialized;
    }

    public function getSerializer(): ?SerializerInterface
    {
        return $this->serializer;
    }
}