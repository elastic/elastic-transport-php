<img align="right" width="auto" height="auto" src="https://www.elastic.co/static-res/images/elastic-logo-200.png"/>

# HTTP transport for Elastic PHP clients

[![Build status](https://github.com/elastic/elastic-transport-php/workflows/Test/badge.svg)](https://github.com/elastic/elastic-transport-php/actions)

This is a HTTP transport PHP library for communicate with [Elastic](https://www.elastic.co/)
products, like [Elasticsearch](https://github.com/elastic/elasticsearch).

It implements [PSR-7](https://www.php-fig.org/psr/psr-7/) standard for managing
HTTP messages and [PSR-18](https://www.php-fig.org/psr/psr-18/) for sending HTTP requests. 

The architecture of the Transport is flexible and customizable, you can configure it 
using a [PSR-18](https://www.php-fig.org/psr/psr-18/) client, a [PSR-3](https://www.php-fig.org/psr/psr-3/)
logger and a custom [ConnectionPoolInterface](src/ConnectionPool/ConnectionPoolInterface.php), 
to manage a cluster of nodes.

## Quick start

The main component of this library is the [Transport](src/Transport.php) class. 

This class used three components:

- a PSR-18 client, using [ClientInterface](https://www.php-fig.org/psr/psr-18/#interfaces);
- a Connection Pool, using [ConnectionPoolInterface](src/ConnectionPool/ConnectionPoolInterface.php);
- a PSR-3 logger, using [LoggerInterface](https://www.php-fig.org/psr/psr-3/#3-psrlogloggerinterface).

While the PSR-3 and PSR-18 are well known standard in the PHP community, the `ConnectionPoolInterface` is
a new interface proposed in this library. The idea of this interface is to provide a class that is able to
select a node for a list of hosts. If you think about Elasticsearch, it's an HTTP distributed search engine.
This means the service runs on a cluster of nodes and each node exposes a common HTTP API.
In order to use Elasticsearch you need to choose the node where to send the HTTP API request.
This decision is taken care by the `ConnectionPoolInterface` using specific implementations for many use cases.

In order to buid a `Transport` instance, you can use the `TransportBuilder` as follows:

```php
use Elastic\Transport\TransportBuilder;

$transport = TransportBuilder::create()
    ->setHosts(['localhost:9200'])
    ->build();
```

This example shows how to set the transport to communicate with one node located at `localhost:9200`
(e.g. Elasticsearch default port).

By default, `TransportBuilder` will use the default values: [GuzzleClient]() as `ClientInterface`,
[SimpleConnectionPool](src/ConnectionPool/SimpleConnectionPool.php) as `ConnectionPoolInterface` and
[NullLogger](https://github.com/php-fig/log/blob/master/Psr/Log/NullLogger.php) as `LoggerInterface`.

The `Tranport` class implements the [PSR-18](https://www.php-fig.org/psr/psr-18/) interface, 
that means you can use it to send HTTP request using the `Tranport::sendRequest()` function as follows:

```php
$request = new \GuzzleHttp\Psr7\Request('GET', '/info'); // PSR-7 request
$response = $transport->sendRequest($request);
var_dump($response); // PSR-7 response
```

We created a `PSR-7` request specifing the path to use for the web API (i.e. you can use any PSR-7
library here, we choosen Guzzle in the example).

The `sendRequest` function will use `$request` to send the HTTP request to the `localhost:9200`
node specified in the previous example code. This behaviour can be used to specify only the URL path
in the HTTP request, the host is selected at runtime using the `ConnectionPool` implementation.

**NOTE**: if you send a `$request` that contains a not empty host the `Transport` will use it.
The HTTP request will be send to the host specified in `$request`. In this case, `Transport` does not
use the `ConnectionPool` to select a node.

For instance, the following example will send the `/info` request to `domain` and not `localhost`.

```php
use Elastic\Transport\TransportBuilder;

$transport = TransportBuilder::create()
    ->setHosts(['localhost:9200'])
    ->build();

$request = new Request('GET', 'https://domain.com/info');
$response = $transport->sendRequest($request); // the HTTP request will be sent to domain.com

echo $transport->lastRequest()->getUri()->getHost(); // domain.com
```

## Connection Pool

The `SimpleConnectionPool` is the default connection pool algorithm used by `Tranposrt`.
It uses the following default values: [RoundRobin](src/ConnectionPool/Selector/RoundRobin.php) as
`SelectorInterface` and [NoResurrect](src/ConnectionPool/Resurrect/FalseResurrect.php) as `ResurrectInterface`.

The [Round-robin](https://en.wikipedia.org/wiki/Round-robin_scheduling) algorithm select the nodes in
order*, from the first node in the array to the latest. When arrivedto the latest nodes, it will start again from the first. 

\* **NOTE**: the order of the nodes is randomized at runtime to maximize the usage of all the hosts.

## Use a custom Selector

You can specify a `SelectorInterface` implementation when you create a `ConnectionPoolInterface` instance.
For instance, imagine you implemented a `CustomSelector`, you can use it as follows:

```php
use Elastic\Transport\ConnectionPool\SimpleConnectionPool;
use Elastic\Transport\TransportBuilder;

$connectionPool = new SimpleConnectionPool(new CustomSelector());

$transport = TransportBuilder::create()
    ->setHosts(['localhost:9200'])
    ->setConnectionPool($connectionPool)
    ->build();
```

## Use a custom PSR-3 loggers

You can specify a PSR-3 `LoggerInterface` implementation using the `TransportBuilder`.
For instance, if you want to use [monolog](https://github.com/Seldaek/monolog) library
you can use the following configuration:

```php
use Elastic\Transport\TransportBuilder;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('name');
$logger->pushHandler(new StreamHandler('debug.log', Logger::DEBUG));

$transport = TransportBuilder::create()
    ->setHosts(['localhost:9200'])
    ->setLogger($logger)
    ->build();
```
## Use a custom PSR-18 clients

You can specify a PSR-18 `ClientInterface` implementation using the `TransportBuilder`.
For instance, if you want to use [Symfony HTTP Client](https://symfony.com/doc/current/http_client.html)
you can use the following configuration:

```php
use Elastic\Transport\TransportBuilder;
use Symfony\Component\HttpClient\Psr18Client;

$transport = TransportBuilder::create()
    ->setHosts(['localhost:9200'])
    ->setClient(new Psr18Client)
    ->build();
```

## Copyright and License

Copyright (c) [Elasticsearch B.V](https://www.elastic.co).

This software is licensed under the Apache License, Version 2.0.
Read the [LICENSE](LICENSE) file for more information.
