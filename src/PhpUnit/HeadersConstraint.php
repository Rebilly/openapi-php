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

use JsonSchema\Entity\JsonPointer;
use PHPUnit\Framework\Constraint\Constraint;
use Rebilly\OpenAPI\JsonSchema\Validator;
use Rebilly\OpenAPI\UnexpectedValueException;
use stdClass;

/**
 * Constraint that asserts that the headers list matches the expected defined schemas.
 */
final class HeadersConstraint extends Constraint
{
    private $errors = [];

    private $expectedHeadersSchemas;

    private $validator;

    public function __construct(array $expectedHeadersSchemas)
    {
        parent::__construct();
        $this->expectedHeadersSchemas = array_map([$this, 'normalizeJsonSchema'], $expectedHeadersSchemas);
        $this->validator = new Validator('undefined');
    }

    protected function matches($actualHeaders): bool
    {
        if (!is_array($actualHeaders)) {
            throw new UnexpectedValueException('Array expected');
        }

        foreach ($this->expectedHeadersSchemas as $name => $definition) {
            $expectedSchema = $definition->schema;

            if (isset($actualHeaders[$name])) {
                $errors = $this->validator->validate(
                    $this->normalizeHeaderValue($actualHeaders[$name], $expectedSchema->type),
                    $expectedSchema,
                    new JsonPointer("#/{$name}")
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

    protected function failureDescription($other): string
    {
        return json_encode($other) . ' ' . $this->toString();
    }

    protected function additionalFailureDescription($other): string
    {
        return $this->validator->serializeErrors($this->errors);
    }

    public function toString(): string
    {
        return 'matches an specified headers schemas';
    }

    private static function normalizeJsonSchema($schema): stdClass
    {
        return json_decode(json_encode($schema));
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
