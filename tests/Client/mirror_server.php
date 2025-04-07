<?php
function getCurrentUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
                 || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    
    $host = $_SERVER['HTTP_HOST']; // e.g. www.example.com
    $requestUri = $_SERVER['REQUEST_URI']; // e.g. /path/page.php?foo=bar

    return $protocol . $host . $requestUri;
}

$output = [
    'method' => $_SERVER['REQUEST_METHOD'],
    'url' => getCurrentUrl(),
    'headers' => getallheaders(),
    'body' => file_get_contents('php://input')
];
echo serialize($output);
