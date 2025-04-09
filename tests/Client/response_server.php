<?php
/**
 * Take the HTTP request and build the desired response
 * Send a JSON request with the following structure:
 * {
 *      "response_code": 200,
 *      "http_version": "1.1",
 *      "reason_phrase": "OK",
 *      "headers": {
 *          "X-test": "php unit test"
 *      },
 *      "body": "hello"
 * }
 */

use Nyholm\Psr7\Response;

$body = file_get_contents('php://input');
try {
    $json = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $e) {
    http_response_code(400);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method not allowed
    header('Allow: POST');
    exit;
}
if (!array_key_exists('response_code', $json) ||
    !array_key_exists('http_version', $json) ||
    !array_key_exists('headers', $json)) {
        http_response_code(400);
        exit;
    }
header(sprintf("HTTP/%s %s %s", $json['http_version'], $json['response_code'], $json['reason_phrase'] ?? ''));
foreach($json['headers'] as $key => $value) {
    if (is_array($value)) {
        foreach ($value as $val) {
            header(sprintf("%s: %s", ucfirst($key), $val), false);
        }
    } else {
        header(sprintf("%s: %s", ucfirst($key), $value));
    }
}
if (isset($json['body'])) {
    header(sprintf('Content-length: %d', strlen($json['body'])));
    echo $json['body'];    
}