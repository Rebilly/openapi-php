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

namespace Rebilly\OpenAPI\PhpUnit;

use JsonSchema\Entity\JsonPointer;
use PHPUnit\Framework\Constraint\Constraint;
use Psr\Http\Message\UriInterface;
use Rebilly\OpenAPI\JsonSchema\Validator;
use Rebilly\OpenAPI\UnexpectedValueException;
use stdClass;

/**
 * Constraint that asserts that the URI matches the expected
 * allowed schemes, base URI, URI paths and query-params.
 */
final class UriConstraint extends Constraint
{
    private $servers;

    private $path;

    private $pathParameters;

    private $queryParameters;

    private $validator;

    private $errors = [];

    public function __construct(
        array $servers,
        string $path,
        array $pathParameters,
        array $queryParameters
    ) {
        $this->servers = array_map('strtolower', $servers);
        $this->path = $path;
        $this->pathParameters = array_map([$this, 'normalizeJsonSchema'], $pathParameters);
        $this->queryParameters = array_map([$this, 'normalizeJsonSchema'], $queryParameters);
        $this->validator = new Validator('undefined');
    }

    public function toString(): string
    {
        return 'matches an specified URI parts';
    }

    protected function matches($uri): bool
    {
        if (!$uri instanceof UriInterface) {
            throw new UnexpectedValueException('The object should implements UriInterface');
        }

        $baseUrl = null;

        foreach ($this->servers as $serverUrl) {
            if (mb_strpos((string) $uri, $serverUrl) === 0) {
                $baseUrl = $serverUrl;

                continue;
            }
        }

        if ($baseUrl === null) {
            $this->errors[] = [
                'property' => 'baseUrl',
                'message' => sprintf('Unexpected URL, does not found in defined servers (%s)', implode(', ', $this->servers)),
            ];

            return false;
        }

        $pathStart = mb_strlen($baseUrl) - mb_strpos($baseUrl, '/', mb_strpos($baseUrl, '://') + 3);
        $path = mb_substr($uri->getPath(), $pathStart + 1);
        $actualSegments = $this->splitString('#\/#', $path);
        $expectedSegments = $this->splitString('#\/#', $this->path);

        if (count($actualSegments) !== count($expectedSegments)) {
            $this->errors[] = [
                'property' => 'path',
                'message' => "Unexpected URI path, does not match the template ({$this->path})",
            ];

            return false;
        }

        foreach ($expectedSegments as $i => $expectedSegment) {
            $actualSegment = $actualSegments[$i];
            mb_strpos($expectedSegment, '{') === false
                ? $this->assertPathSegment($expectedSegment, $actualSegment)
                : $this->assertPathParam($expectedSegment, $actualSegment);
        }

        if (!empty($this->errors)) {
            return false;
        }

        parse_str($uri->getQuery(), $actualQueryParams);

        // TODO: Assert query params
        foreach ($this->queryParameters as $name => $queryParamSchema) {
            if (isset($actualQueryParams[$name])) {
                $actualQueryParam = $actualQueryParams[$name];

                // TODO: Consider to disallow non-string params in query, that make no sense
                $actualQueryParam = $this->normalizeNumericString($actualQueryParam);

                $this->errors = array_merge(
                    $this->errors,
                    $this->validator->validate($actualQueryParam, $queryParamSchema, new JsonPointer('#/query'))
                );
            } elseif (isset($queryParamSchema->required) && $queryParamSchema->required) {
                $this->errors[] = [
                    'property' => 'query',
                    'message' => "Missing required query param ({$name})",
                ];
            }
        }

        return empty($this->errors);
    }

    protected function failureDescription($other): string
    {
        return json_encode((object) $this->normalizeUri($other)) . ' ' . $this->toString();
    }

    protected function additionalFailureDescription($other): string
    {
        return $this->validator->serializeErrors($this->errors);
    }

    private function assertPathSegment(string $expectedSegment, string $actualSegment): void
    {
        if ($actualSegment !== $expectedSegment) {
            $this->errors[] = [
                'property' => 'path',
                'message' => "Missing path segment ({$expectedSegment})",
            ];
        }
    }

    private function assertPathParam(string $expectedSegment, string $actualSegment): void
    {
        $pathParamSchema = $this->pathParameters[mb_substr($expectedSegment, 1, -1)];

        // TODO: Consider to disallow non-string params in path, that make no sense
        $actualSegment = $this->normalizeNumericString($actualSegment);

        $this->errors = array_merge(
            $this->errors,
            $this->validator->validate($actualSegment, $pathParamSchema, new JsonPointer('#/path'))
        );
    }

    private static function normalizeUri(UriInterface $uri): array
    {
        return [
            'schema' => $uri->getScheme(),
            'host' => $uri->getHost(),
            'path' => $uri->getPath(),
        ];
    }

    private static function normalizeJsonSchema($schema): stdClass
    {
        return (object) $schema;
    }

    /**
     * Cast numeric values, JSON validator does not do it.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    private static function normalizeNumericString($value)
    {
        if (is_numeric($value)) {
            $value += 0;
        }

        return $value;
    }

    private static function splitString(string $pattern, string $subject): array
    {
        return preg_split($pattern, $subject, -1, PREG_SPLIT_NO_EMPTY);
    }
}
