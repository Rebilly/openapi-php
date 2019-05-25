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

namespace Rebilly\OpenAPI;

use InvalidArgumentException;
use JsonSchema\Exception\UnresolvableJsonPointerException;
use JsonSchema\SchemaStorage;
use JsonSchema\Uri\UriResolver;
use JsonSchema\Uri\UriRetriever;
use stdClass;

final class Schema
{
    private $schemaStorage;

    private $uri;

    public function __construct(string $uri)
    {
        if (strpos($uri, '//') === false) {
            $uri = "file://{$uri}";
        }

        $schemaStorage = new SchemaStorage(new UriRetriever(), new UriResolver());
        $schemaStorage->addSchema($uri);

        $this->schemaStorage = $schemaStorage;
        $this->uri = $uri;

        if (!preg_match('|3\.0(\.\d+)?|', $this->getVersion())) {
            throw new UnexpectedValueException('Unsupported OpenAPI Specification schema');
        }
    }

    public function getVersion(): string
    {
        return $this->fetch('#/openapi');
    }

    public function getServers(): array
    {
        return array_column($this->fetch('#/servers'), 'url');
    }

    public function getDefinition(string $name): stdClass
    {
        return $this->fetch("#/components/schemas/{$name}");
    }

    public function getDefinitionNames(): array
    {
        return array_keys((array) $this->fetch('#/components/schemas'));
    }

    public function getPathSchema(string $path): stdClass
    {
        return $this->fetch("#/paths/{$this->encodePath($path)}");
    }

    public function getAvailablePaths(): array
    {
        return array_keys((array) $this->fetch('#/paths'));
    }

    public function getAllowedMethods(string $path): array
    {
        $schema = $this->getPathSchema($path);
        $methods = [
            'OPTIONS' => true,
            'HEAD' => isset($schema->get),
            'GET' => isset($schema->get),
            'POST' => isset($schema->post),
            'PUT' => isset($schema->put),
            'DELETE' => isset($schema->delete),
            'PATCH' => isset($schema->patch),
        ];

        return array_keys(array_filter($methods));
    }

    public function getRequestHeaderSchemas(string $path, string $method): array
    {
        $parameters = $this->getRequestParameters($path, $method, 'header');
        $headers = [];

        foreach ($parameters as $parameter) {
            $headers[] = $parameter->schema;
        }

        return $headers;
    }

    public function isRequestBodyDefined(string $path, string $method): bool
    {
        return $this->fetch("#/paths/{$this->encodePath($path)}/{$this->normalizeMethod($method)}/requestBody/content") !== null;
    }

    public function getRequestBodySchema(string $path, string $method, string $contentType = null): ?stdClass
    {
        $schemas = $this->fetch("#/paths/{$this->encodePath($path)}/{$this->normalizeMethod($method)}/requestBody/content");

        if (empty($schemas)) {
            return null;
        }

        if (!$contentType) {
            return reset($schemas)->schema;
        }

        if (!isset($schemas->{$contentType})) {
            throw new InvalidArgumentException('Unsupported request content type');
        }

        return $schemas->{$contentType}->schema;
    }

    public function getRequestPathParameters(string $path, string $method): array
    {
        return $this->getRequestParameters($path, $method, 'path');
    }

    public function getRequestQueryParameters(string $path, string $method): array
    {
        return $this->getRequestParameters($path, $method, 'query');
    }

    public function getRequestContentTypes(string $path, string $method): array
    {
        return array_keys((array) $this->fetch("#/paths/{$this->encodePath($path)}/{$this->normalizeMethod($method)}/requestBody/content"));
    }

    public function isResponseDefined(string $path, string $method, string $status): bool
    {
        return $this->fetch("#/paths/{$this->encodePath($path)}/{$this->normalizeMethod($method)}/responses/{$status}") !== null;
    }

    public function isResponseBodyDefined(string $path, string $method, string $status): bool
    {
        return $this->fetch("#/paths/{$this->encodePath($path)}/{$this->normalizeMethod($method)}/responses/{$status}/content") !== null;
    }

    public function getResponseContentTypes(string $path, string $method, string $status): array
    {
        return array_keys((array) $this->fetch("#/paths/{$this->encodePath($path)}/{$this->normalizeMethod($method)}/responses/{$status}/content"));
    }

    public function getResponseHeaderSchemas(string $path, string $method, string $status): array
    {
        $headers = (array) $this->fetch("#/paths/{$this->encodePath($path)}/{$this->normalizeMethod($method)}/responses/{$status}/headers");

        foreach ($headers as &$header) {
            if (isset($header->{'$ref'})) {
                $header = $this->schemaStorage->resolveRef($header->{'$ref'});
            }

            if (isset($header->schema->{'$ref'})) {
                $header->schema = $this->schemaStorage->resolveRef($header->schema->{'$ref'});
            }

            $header = $header->schema;
        }

        return $headers;
    }

    public function getResponseBodySchema(string $path, string $method, string $status, string $contentType = null): ?stdClass
    {
        $schemas = $this->fetch("#/paths/{$this->encodePath($path)}/{$this->normalizeMethod($method)}/responses/{$status}/content");

        if (empty($schemas)) {
            return null;
        }

        if (!$contentType) {
            return reset($schemas)->schema;
        }

        if (!isset($schemas->{$contentType})) {
            throw new InvalidArgumentException('Unsupported response content type');
        }

        return $schemas->{$contentType}->schema;
    }

    private function fetch(string $path)
    {
        try {
            return $this->schemaStorage->resolveRef(sprintf('%s%s', $this->uri, $path));
        } catch (UnresolvableJsonPointerException $e) {
            return null;
        }
    }

    private function encodePath(string $path): string
    {
        return strtr($path, ['/' => '~1', '~' => '~0', '%' => '%25']);
    }

    private function normalizeMethod(string $method): string
    {
        return strtolower($method);
    }

    private function getRequestParameters(string $path, string $method, string $location): array
    {
        $operationParameters = $this->normalizeRequestParameters(
            (array) $this->fetch("#/paths/{$this->encodePath($path)}/{$this->normalizeMethod($method)}/parameters"),
            $location
        );

        $pathParameters = $this->normalizeRequestParameters(
            (array) $this->fetch("#/paths/{$this->encodePath($path)}/parameters"),
            $location
        );

        return $operationParameters + $pathParameters;
    }

    private function normalizeRequestParameters(array $parameters, string $location): array
    {
        $schemas = [];

        foreach ($parameters as &$parameter) {
            if (isset($parameter->{'$ref'})) {
                $parameter = $this->schemaStorage->resolveRef($parameter->{'$ref'});
            }

            if ($parameter->in !== $location) {
                continue;
            }

            if (isset($parameter->schema->{'$ref'})) {
                $parameter->schema = $this->schemaStorage->resolveRef($parameter->schema->{'$ref'});
            }

            $schemas[$parameter->name] = clone $parameter;
            unset($schemas[$parameter->name]->name, $schemas[$parameter->name]->in);
        }

        return $schemas;
    }
}
