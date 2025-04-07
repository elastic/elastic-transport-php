<?php
/**
 * Elastic Transport
 *
 * @link      https://github.com/elastic/elastic-transport-php
 * @copyright Copyright (c) Elasticsearch B.V (https://www.elastic.co)
 * @license   https://opensource.org/licenses/MIT MIT License
 *
 * Licensed to Elasticsearch B.V under one or more agreements.
 * Elasticsearch B.V licenses this file to you under the MIT License.
 * See the LICENSE file in the project root for more information.
 */
declare(strict_types=1);

namespace Elastic\Transport\Test\Client;

use Elastic\Transport\Client\Curl;
use Nyholm\Psr7\Request;
use PHPUnit\Framework\TestCase;

final class CurlResponseTest extends TestCase
{
    private Curl $curl;

    protected static int $pid;
    protected static string $serverScript = __DIR__ . '/response_server.php';
    protected static string $url = '127.0.0.1:8100';

    public static function setUpBeforeClass(): void
    {
        $cmd = sprintf(
            'php -S %s %s > /dev/null 2>&1 & echo $!',
            self::$url,
            escapeshellarg(self::$serverScript)
        );

        // Start server and write PID to file
        self::$pid = (int) exec($cmd);

        // Give the server a moment to boot
        sleep(1);
    }



    public function setUp(): void
    {
        $this->curl = new Curl();
    }

    public static function getResponses(): array
    {
        return [
            [
                200,
                '1.1',
                'OK',
                [
                    'X-Foo' => ['bar', 'baz']
                ],
                'hi'
            ],
            [
                200,
                '1.1',
                'OK',
                [
                    'X-Foo' => ['bar', 'baz'],
                    'X-Test' => ['test1']
                ],
                json_encode([ 'foo' => 'bar'])
            ],
            [
                400,
                '1.0',
                'Bad Request',
                [
                    'X-Foo' => ['bar']
                ],
                ''
            ],
            [
                404,
                '1.0',
                'Not Found',
                [
                    'X-Foo' => ['bar', 'baz']
                ],
                'Error: resource not found!'
            ]
        ];
    }

    /**
     * @dataProvider getResponses
     */
    public function testCurl(int $status, string $version, string $reason, array $headers, string $body)
    {
        $json = json_encode([
            'response_code' => $status,
            'http_version' => $version,
            'reason_phrase' => $reason,
            'headers' => $headers,
            'body' => $body
        ]);
        $request = new Request('POST', 'http://' . self::$url, [], $json);
        $response = $this->curl->sendRequest($request);

        $this->assertEquals($status, $response->getStatusCode());
        $this->assertEquals($version, $response->getProtocolVersion());
        $this->assertEquals($reason, $response->getReasonPhrase());
        $this->assertEquals($body, $response->getBody()->getContents());

        $responseHeaders = $response->getHeaders();
        foreach ($headers as $k => $v) {
            $this->assertArrayHasKey($k, $responseHeaders);

            $values = is_array($v) ? $v : [$v];
            foreach ($values as $val) {
                $this->assertTrue(in_array($val, $responseHeaders[$k]));
            }
        }
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$pid > 0) {
            exec(sprintf("kill %s", self::$pid));
        }
    }
}