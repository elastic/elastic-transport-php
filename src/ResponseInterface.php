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

use Psr\Http\Message\ResponseInterface as MessageResponseInterface;

interface ResponseInterface
{
    /**
     * Get the response body as array
     */
    public function asArray(): array;

    /**
     * Get the response body as object
     */
    public function asObject(): object;
    
    /**
     * Get the response body as string
     */
    public function asString(): string;

    /**
     * Get the HTTP response as PSR-7 ResponseInterface
     * 
     * @see https://www.php-fig.org/psr/psr-7/#33-psrhttpmessageresponseinterface
     */
    public function getResponse(): MessageResponseInterface;
}