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

namespace Elastic\Transport\Test\Client;

use Elastic\Transport\Client\Curl;
use Nyholm\Psr7\Request;
use PHPUnit\Framework\TestCase;

final class CurlInterimResponseTest extends TestCase
{
    private Curl $curl;

    protected static int $pid;
    protected static string $serverScript = __DIR__ . '/interim_server.php';
    protected static string $url = '127.0.0.1:8300';

    public static function setUpBeforeClass(): void
    {
        $cmd = sprintf(
            'php %s %s > /dev/null 2>&1 & echo $!',
            escapeshellarg(self::$serverScript),
            self::$url
        );

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

    /**
     * Without the fix the response has the status code 100, no headers
     * and the body "HTTP/1.1 200 OK".
     */
    public function testInterimContinueResponseIsSkipped(): void
    {
        // curl sends "Expect: 100-continue" on its own for a body over 1 MB,
        // the header makes the test independent of that threshold.
        $request = new Request(
            'POST',
            'http://' . self::$url . '/continue',
            ['Expect' => '100-continue'],
            '{"query":{"match_all":{}}}'
        );
        $response = $this->curl->sendRequest($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OK', $response->getReasonPhrase());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertSame("first\r\nsecond", (string) $response->getBody());
    }

    public function testLargeBodyWithoutExpectHeader(): void
    {
        $body = '{"size":0,' . str_repeat(' ', 1200000) . '"query":{"match_all":{}}}';
        $request = new Request('POST', 'http://' . self::$url . '/continue', [], $body);
        $response = $this->curl->sendRequest($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
    }

    public function testBodyContainingCrlfIsNotTruncated(): void
    {
        $request = new Request('POST', 'http://' . self::$url . '/plain', [], '{}');
        $response = $this->curl->sendRequest($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame("first\r\nsecond", (string) $response->getBody());
    }
}
