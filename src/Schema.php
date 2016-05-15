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

/**
 * Schema representation.
 */
final class Schema
{
    /**
     * Schema definition.
     *
     * @var object
     */
    private $schema;

    /**
     * @param object $schema
     */
    public function __construct($schema)
    {
        if (!(isset($schema->swagger) && $schema->swagger === '2.0')) {
            throw new UnexpectedValueException('Unsupported OpenAPI Specification schema');
        }

        $this->schema = $schema;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->fetch($this->schema, 'host');
    }

    /**
     * @return string
     */
    public function getBasePath()
    {
        return $this->fetch($this->schema, 'basePath');
    }

    /**
     * @param string $name
     *
     * @return object
     */
    public function getDefinition($name)
    {
        return $this->fetch($this->schema, 'definitions', $name);
    }

    /**
     * @return string[]
     */
    public function getDefinitionNames()
    {
        return array_keys((array) $this->fetch($this->schema, 'definitions'));
    }

    /**
     * @param string $template
     *
     * @return object
     */
    public function getPathSchema($template)
    {
        return $this->fetch($this->schema, 'paths', $template);
    }

    /**
     * @return string[]
     */
    public function getAvailablePaths()
    {
        return array_keys((array) $this->fetch($this->schema, 'paths'));
    }

    /**
     * @param string $template Schema path template.
     *
     * @return string[]
     */
    public function getAllowedMethods($template)
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
    public function getSupportedSchemes($template, $method)
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
    public function getRequestHeaderSchemas($template, $method)
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
     * @return string[]
     */
    public function getResponseCodes($template, $method)
    {
        return array_filter(
            array_keys((array) $this->fetch($this->schema, 'paths', $template, $method, 'responses')),
            'is_numeric'
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

        $parameters = $this->fetch($path, $method, 'parameters') ?: $this->fetch($path, 'parameters');
        $schemas = [];

        foreach ((array) $parameters as $parameter) {
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
