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

/**
 * Minimal HTTP/1.1 server that writes raw responses, used by CurlInterimResponseTest.
 * The built-in PHP web server cannot send an interim "100 Continue" response.
 *
 * Usage: php interim_server.php <host:port>
 *
 * POST /continue: reads the headers, answers "100 Continue", reads the body
 *                 and then answers the final response.
 * POST /plain:    reads the headers and the body and answers the final response.
 *
 * The final response has a "Content-Type: application/json" header
 * and the body "first\r\nsecond" (a CRLF inside the body).
 */

const CRLF = "\r\n";
const BODY = "first\r\nsecond";

$server = stream_socket_server('tcp://' . $argv[1], $errno, $error);
if ($server === false) {
    fwrite(STDERR, sprintf("Cannot listen on %s: %s\n", $argv[1], $error));
    exit(1);
}

while (true) {
    $conn = @stream_socket_accept($server, -1);
    if ($conn === false) {
        continue;
    }

    $head = '';
    while (!str_contains($head, CRLF . CRLF)) {
        $chunk = fread($conn, 8192);
        if ($chunk === false || $chunk === '') {
            break;
        }
        $head .= $chunk;
    }
    [$head, $received] = array_pad(explode(CRLF . CRLF, $head, 2), 2, '');

    $path = explode(' ', $head, 3)[1] ?? '';
    preg_match('/^Content-Length:\s*(\d+)/mi', $head, $matches);
    $length = (int) ($matches[1] ?? 0);

    if ($path === '/continue') {
        fwrite($conn, 'HTTP/1.1 100 Continue' . CRLF . CRLF);
    }
    while (strlen($received) < $length) {
        $chunk = fread($conn, 65536);
        if ($chunk === false || $chunk === '') {
            break;
        }
        $received .= $chunk;
    }

    fwrite($conn, implode(CRLF, [
        'HTTP/1.1 200 OK',
        'Content-Type: application/json',
        'Content-Length: ' . strlen(BODY),
        'Connection: close',
        '',
        BODY,
    ]));
    fclose($conn);
}
