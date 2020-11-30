# HTTP transport layer for Elastic PHP clients

This is a HTTP transport PHP library for communicate with [Elastic](https://www.elastic.co/)
products, like [Elasticsearch](https://github.com/elastic/elasticsearch).

It implements [PSR-7](https://www.php-fig.org/psr/psr-7/) standard for managing
HTTP messages and [PSR-18](https://www.php-fig.org/psr/psr-18/) as HTTP client. 

The architecture of the Transport is flexible and customizable, you can configure it 
using a [PSR-18](https://www.php-fig.org/psr/psr-18/) client, a [PSR-3](https://www.php-fig.org/psr/psr-3/)
logger and a custom Connection Pool, to manage a cluster of nodes.

By default, we use [Guzzle](https://github.com/guzzle/guzzle) as HTTP client, a 
[NullLogger](https://github.com/php-fig/log/blob/master/Psr/Log/NullLogger.php) logger and
a *SimpleConnectionPool* using a [Round-robin](https://en.wikipedia.org/wiki/Round-robin_scheduling) 
scheduling algorithm.

## Quick start

## Connection Pool

## Selector

## Logging

## Using others PSR-18 client

## Copyright and License

Copyright (c) [Elasticsearch B.V](https://www.elastic.co).

This software is licensed under the Apache License, Version 2.0.
Read the [LICENSE](LICENSE) file for more information.
