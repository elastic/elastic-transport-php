# Changelog

## 8.0.0-RC1 (2021-02-17)

This the first release candidate for 8.0.0 it contains some new
features and changes with the previous 7.x Elastic client.

### Changes

- the `ConnectionPool` namespace has been renamed in `NodePool`,
  consequently all the `Connection` classes has been renamed in `Node`
- the previous Apache 2.0 LICENSE has been changed in [MIT](https://opensource.org/licenses/MIT)

### New features

- added the usage of [HTTPlug](http://httplug.io/) library to
  autodiscovery [PSR-18](https://www.php-fig.org/psr/psr-18/) client
  and `HttpAsyncClient` interface using [Promise](https://docs.php-http.org/en/latest/components/promise.html).
- added the `Trasnport::sendAsyncRequest(RequestInterface $request): Promise`
  to send a PSR-7 request using asynchronous request
- added the `Transport::setAsyncClient(HttpAsyncClient $asyncClient)`
  and `Transport::getAsyncClient()` functions. If the [PSR-18](https://www.php-fig.org/psr/psr-18/)
  client already implements the `HttpAsyncClient` interface you
  don't need to use the `setAsyncClient()` function, it will discovered
  automatically
- added the `Transport::setRetries()` function to specify the number
  of HTTP request retries to apply. If the HTTP failures exceed the
  number of retries the client generates a `NoNodeAvailableException`

## 7.14.0 (2021-08-03)

Release created to be compatible with 7.14 Elastic clients
## 7.13.0 (2021-05-25)

Release created to be compatible with 7.13 Elastic clients
