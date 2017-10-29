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
use Rebilly\OpenAPI\JsonSchema\Validator;
use Rebilly\OpenAPI\UnexpectedValueException;

/**
 * Constraint that asserts that the headers list matches the expected defined schemas.
 */
final class HeadersConstraint extends Constraint
{
    /**
     * @var array
     */
    private $errors = [];

    /**
     * @var array
     */
    private $expectedHeadersSchemas;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @param array $expectedHeadersSchemas
     */
    public function __construct(array $expectedHeadersSchemas)
    {
        parent::__construct();

        $this->expectedHeadersSchemas = array_map([$this, 'normalizeJsonSchema'], $expectedHeadersSchemas);
        $this->validator = new Validator('undefined');
    }

    /**
     * {@inheritdoc}
     */
    protected function matches($actualHeaders)
    {
        if (!is_array($actualHeaders)) {
            throw new UnexpectedValueException('Array expected');
        }

        foreach ($this->expectedHeadersSchemas as $name => $expectedSchema) {
            if (isset($actualHeaders[$name])) {
                $errors = $this->validator->validate(
                    $this->normalizeHeaderValue($actualHeaders[$name], $expectedSchema->type),
                    $expectedSchema,
                    $name
                );
                $this->errors = array_merge($this->errors, $errors);
            } elseif (isset($expectedSchema->required) && $expectedSchema->required) {
                $this->errors[] = [
                    'property' => $name,
                    'message' => "Missing required header ({$name})",
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
        return json_encode($other) . ' ' . $this->toString();
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
        return 'matches an specified headers schemas';
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
     * The PSR-7 says that the header values MUST be an array of strings,
     * but OpenAPI allow scalar values. So if scalar value expected,
     * we try to cast array to scalar, using first element.
     *
     * @param array $value
     * @param string $type
     *
     * @return array
     */
    private static function normalizeHeaderValue(array $value, $type)
    {
        if ($type !== 'array') {
            $value = empty($value) ? null : reset($value);

            if (is_numeric($value)) {
                $value += 0;
            }
        }

        return $value;
    }
}
