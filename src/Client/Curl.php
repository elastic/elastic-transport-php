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

use Elastic\Transport\Exception\CurlException;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Curl implements ClientInterface
{
    /**
     * @var resource $curl
     */
    protected $curl;

    /**
     * @throws CurlException
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    { 
        curl_reset($this->getCurl());

        $headers = array_map(function($value){
            return implode(',', $value);
        }, $request->getHeaders());

        curl_setopt_array($this->getCurl(), [
            CURLOPT_CUSTOMREQUEST  => $request->getMethod(),
            CURLOPT_URL            => (string) $request->getUri(),
            CURLOPT_POSTFIELDS     => (string) $request->getBody(),
            CURLOPT_NOBODY         => $request->getMethod() === 'HEAD',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_HEADERFUNCTION => function($curl, $headerLine) {
                list($name, $value) = explode(':', $headerLine);
                $responseHeaderLine[$name] = $value;
            }
        ]);
        $responseHeaderLine = [];
        $responseBody = curl_exec($this->getCurl());
        
        if (!curl_errno($this->getCurl())) {
            $responseCode = (int) curl_getinfo($this->getCurl(), CURLINFO_HTTP_CODE);
            return new Response($responseCode, $responseHeaderLine, $responseBody);
        }

        throw new CurlException(sprintf(
            "Error sending with cURL (%d): %s",
            curl_errno($this->getCurl()),
            curl_error($this->getCurl())
        ));
    }
    
    private function getCurl()
    {
        if (empty($this->curl)) {
            $init = curl_init();
            if (false == $init) {
                throw new CurlException("I cannot execute curl initialization");
            }
            $this->curl = $init;
        }
        return $this->curl;
    }
}