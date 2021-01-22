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

use ArrayAccess;
use Elastic\Transport\Exception\UnknownContentTypeException;
use Elastic\Transport\Serializer\CsvSerializer;
use Elastic\Transport\Serializer\JsonArraySerializer;
use Elastic\Transport\Serializer\JsonObjectSerializer;
use Elastic\Transport\Serializer\NDJsonArraySerializer;
use Elastic\Transport\Serializer\NDJsonObjectSerializer;
use Elastic\Transport\Serializer\XmlSerializer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use SimpleXMLElement;

/**
 * Wraps a PSR-7 ResponseInterface offering helper to deserialize the body.
 * Implements the ArrayAccess to facilitate the access of the body contents
 * using an associative array.
 */
class Response implements ArrayAccess
{
    use ResponseArrayAccessTrait;

    /**
     * @var array
     */
    protected $asArray;

    /**
     * @var object
     */
    protected $asObject;

    /**
     * @var SimpleXMLElement
     */
    protected $asXml;

    /**
     * @var string
     */
    protected $asString;

    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    /**
     * Converts the response body to array, if possible.
     * Otherwise, it throws an UnknownContentTypeException
     * if Content-Type is not specified or unknown.
     * 
     * @throws UnknownContentTypeException
     */
    public function asArray(): array
    {
        if (isset($this->asArray)) {
            return $this->asArray;
        }
        if (!$this->response->hasHeader('Content-Type')) {
            throw new UnknownContentTypeException('No Content-Type specified in the response');
        }
        $contentType = $this->response->getHeaderLine('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            $this->asArray = JsonArraySerializer::deserialize($this->asString());
            return $this->asArray;
        }
        if (strpos($contentType, 'application/x-ndjson') !== false) {
            $this->asArray = NDJsonArraySerializer::deserialize($this->asString());
            return $this->asArray;
        }
        if (strpos($contentType, 'text/csv') !== false) {
            $this->asArray = CsvSerializer::deserialize($this->asString());
            return $this->asArray;
        }
        throw new UnknownContentTypeException(sprintf(
            "Cannot deserialize the reponse as array with Content-Type: %s",
            $contentType
        ));
    }

    /**
     * Converts the response body to object, if possible.
     * Otherwise, it throws an UnknownContentTypeException
     * if Content-Type is not specified or unknown.
     * 
     * @throws UnknownContentTypeException
     */
    public function asObject(): object
    {
        if (isset($this->asObject)) {
            return $this->asObject;
        }
        if (!$this->response->hasHeader('Content-Type')) {
            throw new UnknownContentTypeException('No Content-Type specified in the response');
        }
        $contentType = $this->response->getHeaderLine('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            $this->asObject = JsonObjectSerializer::deserialize($this->asString());
            return $this->asObject;
        }
        if (strpos($contentType, 'application/x-ndjson') !== false) {
            $this->asObject = NDJsonObjectSerializer::deserialize($this->asString());
            return $this->asObject;
        }
        throw new UnknownContentTypeException(sprintf(
            "Cannot deserialize the reponse as object with Content-Type: %s",
            $contentType
        ));
    }

    /**
     * Converts the response body to SimpleXMLElement, if possible.
     * Otherwise, it throws an UnknownContentTypeException
     * if Content-Type is not specified or unknown.
     * 
     * @throws UnknownContentTypeException
     */
    public function asXml(): SimpleXMLElement
    {
        if (isset($this->asXml)) {
            return $this->asXml;
        }
        if (!$this->response->hasHeader('Content-Type')) {
            throw new UnknownContentTypeException('No Content-Type specified in the response');
        }
        $contentType = $this->response->getHeaderLine('Content-Type');
        if (strpos($contentType, 'text/xml') !== false || strpos($contentType, 'application/xml') !== false) {
            $this->asXml = XmlSerializer::deserialize($this->asString());
            return $this->asXml;
        }
        throw new UnknownContentTypeException(sprintf(
            "Cannot deserialize the reponse as XML with Content-Type: %s",
            $contentType
        ));
    }

    /**
     * Converts the response body to string
     */
    public function asString(): string
    {
        if (isset($this->asString)) {
            return $this->asString;
        }
        $this->asString = $this->response->getBody()->getContents();
        return $this->asString;
    }

    /**
     * Converts the response body to string
     */
    public function __toString(): string
    {
        return $this->asString();
    }

    /**
     * Converts the response body to StreamInterface
     * 
     * @see https://www.php-fig.org/psr/psr-7/#13-streams
     */
    public function asStream(): StreamInterface
    {
        return $this->response->getBody();
    }

    /**
     * Get the HTTP response as PSR-7 ResponseInterface
     * 
     * @see https://www.php-fig.org/psr/psr-7/#33-psrhttpmessageresponseinterface
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * Get the headers from the PSR-7 HTTP response
     * 
     * @see https://www.php-fig.org/psr/psr-7/#31-psrhttpmessagemessageinterface
     */
    public function getHeaders(): array
    {
        return $this->response->getHeaders();
    }

    /**
     * Get the status code from the PSR-7 HTTP response
     * 
     * @see https://www.php-fig.org/psr/psr-7/#33-psrhttpmessageresponseinterface
     */
    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }
}