<?php
/**
 * This source file is proprietary and part of Rebilly.
 *
 * (c) Rebilly SRL
 *     Rebilly Ltd.
 *     Rebilly Inc.
 *
 * @see https://www.rebilly.com
 */

declare(strict_types=1);

namespace Rebilly\OpenAPI\PhpUnit;

use PHPUnit\Framework\Assert;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Rebilly\OpenAPI\Schema;
use stdClass;
use function json_decode;
use function mb_strpos;
use function mb_strtolower;
use function preg_split;

/**
 * Asserts data against OpenAPI specification.
 */
trait Asserts
{
    /**
     * Assert request matches against declared specification.
     *
     * The list of constraints:
     *
     * - Assert request method defined
     * - Assert request URI declared by host, basePath, schemes and parameters (path, query)
     * - Assert content-type declared by consumes
     * - Assert headers declared by parameters (header)
     * - Assert body declared by parameters (body)
     */
    final protected static function assertRequest(Schema $schema, string $path, RequestInterface $request, string $msg = ''): void
    {
        self::assertMethodAllowed($schema, $path, $request->getMethod(), $msg);
        self::assertUri($schema, $path, $request->getMethod(), $request->getUri(), $msg);
        self::assertRequestHeaders($schema, $path, $request->getMethod(), $request->getHeaders(), $msg);
        self::assertRequestBody($schema, $path, $request->getMethod(), $request->getBody(), $msg);
    }

    /**
     * Assert response matches against declared specification.
     *
     * The list of constraints:
     *
     * - Assert response status code or default is defined
     * - Assert content-type declared by produces from operation
     * - Assert headers
     * - Assert body
     */
    final protected static function assertResponse(Schema $schema, string $path, string $method, ResponseInterface $response, string $msg = ''): void
    {
        self::assertResponseDefined($schema, $path, $method, $response->getStatusCode(), $msg);
        self::assertResponseHeaders(
            $schema,
            $path,
            $method,
            $response->getStatusCode(),
            $response->getHeaders(),
            $msg
        );
        self::assertResponseBody(
            $schema,
            $path,
            $method,
            $response->getStatusCode(),
            $response->getBody(),
            $msg
        );
    }

    /**
     * Assert URI matches against declared host, basePath, schemes and parameters (path, query).
     *
     * The list of constraints:
     *
     * - Assert URI scheme matches allowed schemes
     * - Assert URI host matches defined
     * - Assert URI path starts with defined base path
     * - Assert URI path matches defined template and path parameters
     * - Assert URI path matches defined query parameters
     */
    final protected static function assertUri(Schema $schema, string $path, string $method, UriInterface $uri, string $msg = ''): void
    {
        Assert::assertThat(
            $uri,
            new UriConstraint(
                $schema->getServers(),
                $path,
                $schema->getRequestPathParameters($path, $method),
                $schema->getRequestQueryParameters($path, $method)
            ),
            $msg
        );
    }

    /**
     * Assert the endpoint supports given operation.
     */
    final protected static function assertMethodAllowed(Schema $schema, string $path, string $method, string $msg = ''): void
    {
        Assert::assertThat(
            $method,
            new MethodsAllowedConstraint($schema->getAllowedMethods($path)),
            $msg
        );
    }

    /**
     * Assert the response status code defined.
     */
    final protected static function assertResponseDefined(Schema $schema, string $template, string $method, string $status, string $msg = ''): void
    {
        Assert::assertTrue(
            $schema->isResponseDefined($template, mb_strtolower($method), $status),
            $msg ?: "Operation \"{$method} {$template}\" does not support response code \"{$status}\""
        );
    }

    /**
     * Assert the endpoint supports given operation.
     */
    final protected static function assertRequestContentType(Schema $schema, string $path, string $method, string $contentType, string $msg = ''): void
    {
        Assert::assertThat(
            $contentType,
            new ContentTypeConstraint($schema->getRequestContentTypes($path, $method)),
            $msg
        );
    }

    /**
     * Assert the endpoint supports given operation.
     */
    final protected static function assertResponseContentType(Schema $schema, string $path, string $method, string $status, string $contentType, string $msg = ''): void
    {
        Assert::assertThat(
            $contentType,
            new ContentTypeConstraint($schema->getResponseContentTypes($path, $method, $status)),
            $msg
        );
    }

    final protected static function assertRequestHeaders(Schema $schema, string $path, string $method, array $headers, string $msg = ''): void
    {
        Assert::assertThat(
            $headers,
            new HeadersConstraint($schema->getRequestHeaderSchemas($path, mb_strtolower($method))),
            $msg
        );

        if ($schema->isRequestBodyDefined($path, $method) && isset($headers['Content-Type'][0])) {
            self::assertRequestContentType(
                $schema,
                $path,
                mb_strtolower($method),
                $headers['Content-Type'][0],
                $msg
            );
        }
    }

    final protected static function assertResponseHeaders(Schema $schema, string $path, string $method, string $status, array $headers, string $msg = ''): void
    {
        Assert::assertThat(
            $headers,
            new HeadersConstraint(
                $schema->getResponseHeaderSchemas($path, mb_strtolower($method), $status)
            ),
            $msg
        );

        if ($schema->isResponseBodyDefined($path, $method, $status) && isset($headers['Content-Type'][0])) {
            self::assertResponseContentType(
                $schema,
                $path,
                $method,
                $status,
                $headers['Content-Type'][0],
                $msg
            );
        }

        if (isset($headers['Allow'])) {
            if (isset($headers['Allow'][0]) && mb_strpos($headers['Allow'][0], ',') !== false) {
                $headers['Allow'] = preg_split('#\s*,\s*#', $headers['Allow'][0], -1, PREG_SPLIT_NO_EMPTY);
            }

            Assert::assertThat(
                $headers['Allow'],
                new MethodsAllowedConstraint($schema->getAllowedMethods($path)),
                $msg
            );
        }
    }

    final protected static function assertRequestBody(Schema $schema, string $path, string $method, StreamInterface $body = null, string $msg = ''): void
    {
        $bodySchema = $schema->getRequestBodySchema($path, mb_strtolower($method));

        if ($bodySchema) {
            Assert::assertThat(
                json_decode($body),
                new JsonSchemaConstraint($bodySchema, 'request body'),
                $msg
            );
        } else {
            Assert::assertEmpty(json_decode($body), $msg);
        }
    }

    final protected static function assertResponseBody(Schema $schema, string $path, string $method, string $status, StreamInterface $body = null, string $msg = ''): void
    {
        $bodySchema = $schema->getResponseBodySchema($path, mb_strtolower($method), $status);

        if ($bodySchema) {
            Assert::assertThat(
                json_decode($body),
                new JsonSchemaConstraint($bodySchema, 'response body'),
                $msg
            );
        } else {
            Assert::assertEmpty(json_decode($body), $msg);
        }
    }

    final protected static function assertDefinitionSchema(Schema $schema, string $class, stdClass $actual, string $msg = ''): void
    {
        Assert::assertThat(
            $actual,
            new JsonSchemaConstraint($schema->getDefinition($class)),
            $msg
        );
    }
}
