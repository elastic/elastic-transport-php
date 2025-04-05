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

namespace Elastic\Transport\Test;

use Elastic\Transport\Exception\InvalidArgumentException;
use Elastic\Transport\OpenTelemetry;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Trace\TracerInterface;
use PHPUnit\Framework\TestCase;

final class OpenTelemetryTest extends TestCase
{
    public function tearDown(): void
    {
        // Remove env variables
        putenv(OpenTelemetry::ENV_VARIABLE_BODY_STRATEGY);
    }

    public function testRedactBodyWithDefault()
    {
        $body = '{"password":"supersecret"}';
        // Default is omit, it should returns an empty string
        $this->assertEmpty(OpenTelemetry::redactBody($body));
    }

    public function testRedactBodyWithRaw()
    {
        $body = '{"password":"supersecret"}';
        putenv(OpenTelemetry::ENV_VARIABLE_BODY_STRATEGY . '=raw');
        // Raw strategy, it should returns the original body
        $this->assertEquals($body, OpenTelemetry::redactBody($body));
    }

    public function testRedactBodyWithSanitize()
    {
        $body = '{"password":"supersecret"}';
        $sanitizeBody = sprintf('{"password":"%s"}', OpenTelemetry::REDACTED_STRING);
        putenv(OpenTelemetry::ENV_VARIABLE_BODY_STRATEGY . '=sanitize');
        // Sanitize strategy, it should returns the redacted body
        $this->assertEquals($sanitizeBody, OpenTelemetry::redactBody($body));
    }

    public function testRedactBodyWithSanitizeUsingPartOfKey()
    {
        $body = '{"secret_key":"supersecret"}';
        $sanitizeBody = sprintf('{"secret_key":"%s"}', OpenTelemetry::REDACTED_STRING);
        putenv(OpenTelemetry::ENV_VARIABLE_BODY_STRATEGY . '=sanitize');
        // Sanitize strategy, it should returns the redacted body
        $this->assertEquals($sanitizeBody, OpenTelemetry::redactBody($body));
    }

    public function testRedactBodyWithSanitizeUsingCustomKey()
    {
        $body = '{"foo_bar":"supersecret"}';
        $sanitizeBody = sprintf('{"foo_bar":"%s"}', OpenTelemetry::REDACTED_STRING);
        putenv(OpenTelemetry::ENV_VARIABLE_BODY_SANITIZE_KEYS . '=bar');
        putenv(OpenTelemetry::ENV_VARIABLE_BODY_STRATEGY . '=sanitize');
        // Sanitize strategy, it should returns the redacted body
        $this->assertEquals($sanitizeBody, OpenTelemetry::redactBody($body));
    }

    public function testRedactBodyWithSanitizeUsingCustomKeys()
    {
        $body = '{"foo_bar":"supersecret","baz_foo":"test_password"}';
        $sanitizeBody = sprintf('{"foo_bar":"%s","baz_foo":"%s"}', OpenTelemetry::REDACTED_STRING, OpenTelemetry::REDACTED_STRING);
        putenv(OpenTelemetry::ENV_VARIABLE_BODY_SANITIZE_KEYS . '=bar,baz');
        putenv(OpenTelemetry::ENV_VARIABLE_BODY_STRATEGY . '=sanitize');
        // Sanitize strategy, it should returns the redacted body
        $this->assertEquals($sanitizeBody, OpenTelemetry::redactBody($body));
    }

    public function testGetTracer()
    {
        $tracer = OpenTelemetry::getTracer(
            Globals::tracerProvider()
        );
        $this->assertInstanceOf(TracerInterface::class, $tracer);
    }
}