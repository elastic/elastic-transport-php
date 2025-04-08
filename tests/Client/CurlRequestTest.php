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

final class CurlRequestTest extends TestCase
{
    private Curl $curl;

    protected static int $pid;
    protected static string $serverScript = __DIR__ . '/mirror_server.php';
    protected static string $url = '127.0.0.1:8200';

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

    public static function tearDownAfterClass(): void
    {
        if (self::$pid > 0) {
            exec(sprintf("kill %s", self::$pid));
        }
    }
    
    public function setUp(): void
    {
        $this->curl = new Curl();
    }

    public static function getRequests(): array
    {
        return [
            [
                'GET',
                'http://' . self::$url. '/',
                [
                    'foo' => 'bar',
                    'baz' => 'wow'
                ],
                '',
                '1.0'
            ],
            [
                'POST',
                'http://' . self::$url. '/',
                [
                    'foo' => 'bar'
                ],
                'foo=bar&baz=wow',
                '1.1'
            ],
            [
                'PUT',
                'http://' . self::$url. '/',
                [
                    'foo' => 'bar'
                ],
                '{"foo":"bar","baz":"wow"}',
                '2.0'
            ],
        ];
    }

    /**
     * @dataProvider getRequests
     */
    public function testCurl(string $method, string $url, array $headers, string $body, string $version)
    {
        $request = new Request($method, $url, $headers, $body, $version);
        $response = $this->curl->sendRequest($request);
        $httpRequest = unserialize($response->getBody()->getContents());
        
        $this->assertEquals($method, $httpRequest['method']);
        $this->assertEquals($url, $httpRequest['url']);
        foreach ($headers as $k => $v) {
            $this->assertArrayHasKey($k, $httpRequest['headers']);
            $this->assertEquals($v, $httpRequest['headers'][$k]);
        }
        $this->assertEquals($body, $httpRequest['body']);
    }
}