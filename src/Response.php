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

use Elastic\Transport\Exception\InvalidSerializerException;
use Elastic\Transport\Exception\UnknownContentTypeException;
use Elastic\Transport\Serializer\CsvSerializer;
use Elastic\Transport\Serializer\JsonArraySerializer;
use Elastic\Transport\Serializer\NDJsonArraySerializer;
use Elastic\Transport\Serializer\SerializerInterface;
use Elastic\Transport\Serializer\TextSerializer;
use Elastic\Transport\Serializer\XmlSerializer;
use Psr\Http\Message\ResponseInterface;

/**
 * Response 
 * 
 * This class is a decorator of the PSR-7 response that returns a
 * deserialized value of the HTTP response body, using a Serializer object.
  */
class Response
{
    protected $response;
    protected $serializer;

    const CONTENT_TYPE = [
        'application/json'     => JsonArraySerializer::class,
        'application/x-ndjson' => NDJsonArraySerializer::class,
        'text/xml'             => XmlSerializer::class,
        'application/xml'      => XmlSerializer::class,
        'text/plain'           => TextSerializer::class,
        'text/csv'             => CsvSerializer::class
    ];

    /**
     * $contentType is an associative array of Content-Type => Serializer class
     * this array is merged with the default self::CONTENT_TYPE
     */
    public function __construct(ResponseInterface $response, array $contentType = [])
    {
        $this->response = $response;

        if (! $response->hasHeader('Content-Type')) {
            $this->serializer = new TextSerializer();
            return;
        }
        $contentType = array_merge(self::CONTENT_TYPE, $contentType);
        foreach ($contentType as $type => $serializerName) {
            if (strpos($response->getHeaderLine('Content-Type'), $type) !== false) {
                $this->serializer = new $serializerName;
                if (! $this->serializer instanceof SerializerInterface) {
                    throw new InvalidSerializerException(sprintf(
                        "The serializer specified %s is not valid. It must implement %s.",
                        $serializerName,
                        SerializerInterface::class
                    ));
                }
                return;
            }
        }
        throw new UnknownContentTypeException(sprintf(
            "I don't have a Serializer for Content-Type: %s",
            $response->getHeaderLine('Content-Type')
        ));
    }

    public function getPsr7Response(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * Get the deserialized response using the Serializer class passed in contrustor
     * @return mixed the Response type depends on the deserialize output
     */
    public function getResponse()
    {
        return $this->serializer->deserialize($this->response);
    }

    public function getSerializer(): SerializerInterface
    {
        return $this->serializer;
    }
}