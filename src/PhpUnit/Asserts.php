<?php
/**
 * This file is part of Rebilly.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @see http://rebilly.com
 */

namespace Rebilly\OpenAPI\PhpUnit;

use PHPUnit\Framework\Assert;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Rebilly\OpenAPI\Schema;

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
     *
     * @param Schema $spec
     * @param string $template
     * @param RequestInterface $request
     * @param string $msg
     */
    final protected static function assertRequest(Schema $spec, $template, RequestInterface $request, $msg = '')
    {
        self::assertMethodAllowed($spec, $template, $request->getMethod(), $msg);
        self::assertUri($spec, $template, $request->getMethod(), $request->getUri(), $msg);
        self::assertRequestHeaders($spec, $template, $request->getMethod(), $request->getHeaders(), $msg);
        self::assertRequestBody($spec, $template, $request->getMethod(), $request->getBody(), $msg);
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
     *
     * @param Schema $spec
     * @param string $template
     * @param string $method
     * @param ResponseInterface $response
     * @param string $msg
     */
    final protected static function assertResponse(Schema $spec, $template, $method, ResponseInterface $response, $msg = '')
    {
        self::assertResponseDefined($spec, $template, $method, $response->getStatusCode(), $msg);
        self::assertResponseHeaders(
            $spec,
            $template,
            $method,
            $response->getStatusCode(),
            $response->getHeaders(),
            $msg
        );
        self::assertResponseBody(
            $spec,
            $template,
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
     *
     * @param Schema $spec
     * @param string $template
     * @param string $method
     * @param UriInterface $uri
     * @param string $msg
     */
    final protected static function assertUri(Schema $spec, $template, $method, UriInterface $uri, $msg = '')
    {
        Assert::assertThat(
            $uri,
            new UriConstraint(
                $spec->getSupportedSchemes($template, $method),
                $spec->getHost(),
                $spec->getBasePath(),
                $template,
                $spec->getRequestPathParameters($template, $method),
                $spec->getRequestQueryParameters($template, $method)
            ),
            $msg
        );
    }

    /**
     * Assert the endpoint supports given operation.
     *
     * @param Schema $spec
     * @param string $template
     * @param string $method
     * @param string $msg
     */
    final protected static function assertMethodAllowed(Schema $spec, $template, $method, $msg = '')
    {
        Assert::assertThat(
            $method,
            new MethodsAllowedConstraint($spec->getAllowedMethods($template)),
            $msg
        );
    }

    /**
     * Assert the response status code defined.
     *
     * @param Schema $spec
     * @param string $template
     * @param string $method
     * @param string $status
     * @param string $msg
     */
    final protected static function assertResponseDefined(Schema $spec, $template, $method, $status, $msg = '')
    {
        Assert::assertTrue(
            in_array((int) $status, $spec->getResponseCodes($template, strtolower($method)), true),
            $msg ?: "Operation \"{$method} {$template}\" does not support response code \"{$status}\""
        );
    }

    /**
     * Assert the endpoint supports given operation.
     *
     * @param Schema $spec
     * @param string $template
     * @param string $method
     * @param string $contentType
     * @param string $msg
     */
    final protected static function assertRequestContentType(Schema $spec, $template, $method, $contentType, $msg = '')
    {
        Assert::assertThat(
            $contentType,
            new ContentTypeConstraint($spec->getRequestContentTypes($template, $method)),
            $msg
        );
    }

    /**
     * Assert the endpoint supports given operation.
     *
     * @param Schema $spec
     * @param string $template
     * @param string $method
     * @param string $contentType
     * @param string $msg
     */
    final protected static function assertResponseContentType(Schema $spec, $template, $method, $contentType, $msg = '')
    {
        Assert::assertThat(
            $contentType,
            new ContentTypeConstraint($spec->getResponseContentTypes($template, $method)),
            $msg
        );
    }

    /**
     * @param Schema $spec
     * @param string $template
     * @param string $method
     * @param array $headers
     * @param string $msg
     */
    final protected static function assertRequestHeaders(Schema $spec, $template, $method, array $headers, $msg = '')
    {
        Assert::assertThat(
            $headers,
            new HeadersConstraint($spec->getRequestHeaderSchemas($template, strtolower($method))),
            $msg
        );

        if (isset($headers['Content-Type'][0])) {
            self::assertRequestContentType(
                $spec,
                $template,
                strtolower($method),
                $headers['Content-Type'][0],
                $msg
            );
        }
    }

    /**
     * @param Schema $spec
     * @param string $template
     * @param string $method
     * @param string $status
     * @param array $headers
     * @param string $msg
     */
    final protected static function assertResponseHeaders(Schema $spec, $template, $method, $status, array $headers, $msg = '')
    {
        Assert::assertThat(
            $headers,
            new HeadersConstraint(
                $spec->getResponseHeaderSchemas($template, strtolower($method), $status)
            ),
            $msg
        );

        if (isset($headers['Content-Type'][0])) {
            self::assertResponseContentType(
                $spec,
                $template,
                $method,
                $headers['Content-Type'][0],
                $msg
            );
        }

        if (isset($headers['Allow'])) {
            if (isset($headers['Allow'][0]) && strpos($headers['Allow'][0], ',') !== false) {
                $headers['Allow'] = preg_split('#\s*,\s*#', $headers['Allow'][0], -1, PREG_SPLIT_NO_EMPTY);
            }

            Assert::assertThat(
                $headers['Allow'],
                new MethodsAllowedConstraint($spec->getAllowedMethods($template)),
                $msg
            );
        }
    }

    /**
     * @param Schema $spec
     * @param string $template
     * @param string $method
     * @param StreamInterface|null $body
     * @param string $msg
     */
    final protected static function assertRequestBody(Schema $spec, $template, $method, StreamInterface $body = null, $msg = '')
    {
        $schema = $spec->getRequestBodySchema($template, strtolower($method));

        if ($schema) {
            Assert::assertThat(
                json_decode($body),
                new JsonSchemaConstraint($schema, 'request body'),
                $msg
            );
        } else {
            Assert::assertEmpty(json_decode($body), $msg);
        }
    }

    /**
     * @param Schema $spec
     * @param string $template
     * @param string $method
     * @param string $status
     * @param StreamInterface|null $body
     * @param string $msg
     */
    final protected static function assertResponseBody(Schema $spec, $template, $method, $status, StreamInterface $body = null, $msg = '')
    {
        $schema = $spec->getResponseBodySchema($template, strtolower($method), $status);

        if ($schema) {
            Assert::assertThat(
                json_decode($body),
                new JsonSchemaConstraint($schema, 'response body'),
                $msg
            );
        } else {
            Assert::assertEmpty(json_decode($body), $msg);
        }
    }

    /**
     * @param Schema $spec
     * @param string $class
     * @param mixed $actual
     * @param string $msg
     */
    final protected static function assertDefinitionSchema(Schema $spec, $class, $actual, $msg = '')
    {
        Assert::assertThat(
            $actual,
            new JsonSchemaConstraint($spec->getDefinition($class)),
            $msg
        );
    }
}
