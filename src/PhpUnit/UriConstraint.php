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

use PHPUnit\Framework\Constraint\Constraint;
use Psr\Http\Message\UriInterface;
use Rebilly\OpenAPI\JsonSchema\Validator;
use Rebilly\OpenAPI\UnexpectedValueException;

/**
 * Constraint that asserts that the URI matches the expected
 * allowed schemes, base URI, URI paths and query-params.
 */
final class UriConstraint extends Constraint
{
    /**
     * @var array
     */
    private $allowedSchemes;

    /**
     * @var string
     */
    private $basePath;

    /**
     * @var array
     */
    private $templateParams;

    /**
     * @var array
     */
    private $queryParams;

    /**
     * @var string
     */
    private $host;

    /**
     * @var array
     */
    private $errors = [];

    /**
     * @var string
     */
    private $template;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @param array $expectedSchemes
     * @param string $host
     * @param string $basePath
     * @param string $template
     * @param array $pathParams
     * @param array $queryParams
     */
    public function __construct(
        array $expectedSchemes,
        $host,
        $basePath,
        $template,
        array $pathParams,
        array $queryParams
    ) {
        parent::__construct();

        $this->allowedSchemes = array_map('strtolower', $expectedSchemes);
        $this->host = $host;
        $this->basePath = $basePath;
        $this->template = $template;
        $this->templateParams = array_map([$this, 'normalizeJsonSchema'], $pathParams);
        $this->queryParams = array_map([$this, 'normalizeJsonSchema'], $queryParams);
        $this->validator = new Validator('undefined');
    }

    /**
     * {@inheritdoc}
     */
    protected function matches($uri)
    {
        if (!$uri instanceof UriInterface) {
            throw new UnexpectedValueException('The object should implements UriInterface');
        }

        if (!in_array(strtolower($uri->getScheme()), $this->allowedSchemes, true)) {
            $this->errors[] = [
                'property' => 'scheme',
                'message' => 'Unsupported scheme (' . implode(', ', $this->allowedSchemes) . ')',
            ];
        }

        if ($uri->getHost() !== $this->host) {
            $this->errors[] = [
                'property' => 'host',
                'message' => "Unexpected host ({$this->host})",
            ];
        }

        if (strpos($uri->getPath(), "{$this->basePath}/") !== 0) {
            $this->errors[] = [
                'property' => 'basePath',
                'message' => "Unexpected base path ({$this->basePath})",
            ];
        } else {
            $path = substr($uri->getPath(), strlen($this->basePath) + 1);
            $actualSegments = $this->splitString('#\/#', $path);
            $expectedSegments = $this->splitString('#\/#', $this->template);

            if (count($actualSegments) !== count($expectedSegments)) {
                $this->errors[] = [
                    'property' => 'path',
                    'message' => "Unexpected URI path, does not match the template ({$this->template})",
                ];
            } else {
                foreach ($expectedSegments as $i => $expectedSegment) {
                    $actualSegment = $actualSegments[$i];

                    if (strpos($expectedSegment, '{') === false) {
                        // Assert path segment
                        if ($actualSegment !== $expectedSegment) {
                            $this->errors[] = [
                                'property' => 'path',
                                'message' => "Missing path segment ({$expectedSegment})",
                            ];
                        }
                    } else {
                        // Assert path param
                        $pathParamSchema = $this->templateParams[substr($expectedSegment, 1, -1)];

                        // TODO: Consider to disallow non-string params in path, that make no sense
                        $actualSegment = $this->normalizeNumericString($actualSegment);

                        $this->errors = array_merge(
                            $this->errors,
                            $this->validator->validate($actualSegment, $pathParamSchema, 'path')
                        );
                    }
                }
            }
        }

        parse_str($uri->getQuery(), $actualQueryParams);

        // TODO: Assert query params
        foreach ($this->queryParams as $name => $queryParamSchema) {
            if (isset($actualQueryParams[$name])) {
                $actualQueryParam = $actualQueryParams[$name];

                // TODO: Consider to disallow non-string params in query, that make no sense
                $actualQueryParam = $this->normalizeNumericString($actualQueryParam);

                $this->errors = array_merge(
                    $this->errors,
                    $this->validator->validate($actualQueryParam, $queryParamSchema, 'query')
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

    /**
     * {@inheritdoc}
     */
    protected function failureDescription($other)
    {
        return json_encode((object) $this->normalizeUri($other)) . ' ' . $this->toString();
    }

    /**
     * {@inheritdoc}
     */
    protected function additionalFailureDescription($other)
    {
        return $this->validator->serializeErrors($this->errors);
    }

    /**
     * {@inheritdoc}
     */
    public function toString()
    {
        return 'matches an specified URI parts';
    }

    /**
     * @param UriInterface $uri
     *
     * @return array
     */
    private static function normalizeUri(UriInterface $uri)
    {
        return [
            'schema' => $uri->getScheme(),
            'host' => $uri->getHost(),
            'path' => $uri->getPath(),
        ];
    }

    /**
     * Ensure schema is object.
     *
     * @param object|array $schema
     *
     * @return object
     */
    private static function normalizeJsonSchema($schema)
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

    /**
     * Split string to segments by regexp.
     *
     * @param string $pattern
     * @param string $subject
     *
     * @return array
     */
    private static function splitString($pattern, $subject)
    {
        return preg_split($pattern, $subject, -1, PREG_SPLIT_NO_EMPTY);
    }
}
