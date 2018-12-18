<?php
/**
 * This file is part of Rebilly.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @see http://rebilly.com
 */

namespace Rebilly\OpenAPI;

use stdClass;

final class Schema
{
    private $schema;

    public function __construct(stdClass $schema)
    {
        if (!(isset($schema->swagger) && $schema->swagger === '2.0')) {
            throw new UnexpectedValueException('Unsupported OpenAPI Specification schema');
        }

        $this->schema = $schema;
    }

    public function getHost(): string
    {
        return $this->fetch($this->schema, 'host');
    }

    public function getBasePath(): string
    {
        return $this->fetch($this->schema, 'basePath');
    }

    public function getDefinition(string $name): stdClass
    {
        return $this->fetch($this->schema, 'definitions', $name);
    }

    public function getDefinitionNames(): array
    {
        return array_keys((array) $this->fetch($this->schema, 'definitions'));
    }

    public function getPathSchema(string $template): stdClass
    {
        return $this->fetch($this->schema, 'paths', $template);
    }

    public function getAvailablePaths(): array
    {
        return array_keys((array) $this->fetch($this->schema, 'paths'));
    }

    public function getAllowedMethods(string $template): array
    {
        $schema = $this->getPathSchema($template);
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

    /**
     * The transfer protocol for the operation.
     * The value overrides the top-level schemes definition.
     *
     * @see https://github.com/OAI/OpenAPI-Specification/blob/master/versions/2.0.md#operationObject
     *
     * @param string $template
     * @param string $method
     *
     * @return string[]
     */
    public function getSupportedSchemes($template, $method): array
    {
        $schemes = $this->fetch(
            $this->schema,
            'paths',
            $template,
            $method,
            'schemes'
        );

        if (!$schemes) {
            $schemes = $this->fetch($this->schema, 'schemes');
        }

        return (array) $schemes;
    }

    /**
     * @param string $template
     * @param string $method
     *
     * @return object[]
     */
    public function getRequestHeaderSchemas(string $template, string $method): array
    {
        return $this->getRequestParameters($template, $method, 'header');
    }

    /**
     * @param string $template
     * @param string $method
     *
     * @return object|null
     */
    public function getRequestBodySchema($template, $method)
    {
        $parameters = $this->getRequestParameters($template, $method, 'body');
        $count = count($parameters);

        if ($count === 0) {
            return null;
        }

        if ($count > 1) {
            throw new UnexpectedValueException('Multiple body parameters found');
        }

        $body = reset($parameters);

        if (!isset($body->schema)) {
            throw new UnexpectedValueException('Invalid body parameter definition');
        }

        return $body->schema;
    }

    /**
     * @param string $template
     * @param string $method
     *
     * @return object[]
     */
    public function getRequestPathParameters($template, $method)
    {
        return $this->getRequestParameters($template, $method, 'path');
    }

    /**
     * @param string $template
     * @param string $method
     *
     * @return object[]
     */
    public function getRequestQueryParameters($template, $method)
    {
        return $this->getRequestParameters($template, $method, 'query');
    }

    /**
     * @param string $template
     * @param string $method
     *
     * @return string[]
     */
    public function getRequestContentTypes($template, $method)
    {
        $items = $this->fetch($this->schema, 'paths', $template, $method, 'consumes');

        if (!$items) {
            $items = $this->fetch($this->schema, 'consumes');
        }

        return (array) $items;
    }

    /**
     * @param string $template
     * @param string $method
     *
     * @return int[]
     */
    public function getResponseCodes($template, $method)
    {
        return array_map(
            'intval',
            array_filter(
                array_keys((array) $this->fetch($this->schema, 'paths', $template, $method, 'responses')),
                'is_numeric'
            )
        );
    }

    /**
     * @param string $template
     * @param string $method
     *
     * @return string[]
     */
    public function getResponseContentTypes($template, $method)
    {
        $items = $this->fetch($this->schema, 'paths', $template, $method, 'produces');

        if (!$items) {
            $items = $this->fetch($this->schema, 'produces');
        }

        return (array) $items;
    }

    /**
     * @param string $template
     * @param string $method
     * @param string $status
     *
     * TODO: Normalize headers list to JSON schema (seems it is validator deals)
     * TODO: If status does not defined, check default response declaration
     *
     * @return object[]
     */
    public function getResponseHeaderSchemas($template, $method, $status)
    {
        return (array) $this->fetch(
            $this->schema,
            'paths',
            $template,
            $method,
            'responses',
            $status,
            'headers'
        );
    }

    /**
     * Returns body schema.
     *
     * @param string $template
     * @param string $method
     * @param string $status
     *
     * @return object|null
     */
    public function getResponseBodySchema($template, $method, $status)
    {
        return $this->fetch(
            $this->schema,
            'paths',
            $template,
            $method,
            'responses',
            $status,
            'schema'
        );
    }

    /**
     * @param $schema
     * @param array ...$paths
     *
     * @return mixed
     */
    private static function fetch($schema, ...$paths)
    {
        foreach ($paths as $path) {
            if (!isset($schema->{$path})) {
                return null;
            }

            $schema = $schema->{$path};
        }

        return $schema;
    }

    /**
     * A list of parameters that are applicable for this operation.
     *
     * If a parameter is already defined at the Path Item,
     * the new definition will override it, but can never remove it.
     *
     * @param string $template
     * @param string $method
     * @param string $location
     *
     * @return object[]
     */
    private function getRequestParameters($template, $method, $location)
    {
        $path = $this->fetch($this->schema, 'paths', $template);

        $operationParameters = $this->normalizeRequestParameters(
            (array) $this->fetch($path, $method, 'parameters'),
            $location
        );

        $pathParameters = $this->normalizeRequestParameters(
            (array) $this->fetch($path, 'parameters'),
            $location
        );

        return $operationParameters + $pathParameters;
    }

    /**
     * Normalizes parameters definitions.
     *
     * Filter parameters by location, and use name as list index.
     *
     * @param array $parameters
     * @param string $location
     *
     * @return object[]
     */
    private function normalizeRequestParameters(array $parameters, $location)
    {
        $schemas = [];

        foreach ($parameters as $parameter) {
            if ($parameter->in !== $location) {
                continue;
            }

            if (isset($schemas[$parameter->name])) {
                throw new UnexpectedValueException('Multiple parameters found');
            }

            $schemas[$parameter->name] = clone $parameter;
            unset($schemas[$parameter->name]->name, $schemas[$parameter->name]->in);
        }

        return $schemas;
    }
}
