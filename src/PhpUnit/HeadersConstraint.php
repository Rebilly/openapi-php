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

use JsonSchema\Entity\JsonPointer;
use PHPUnit\Framework\Constraint\Constraint;
use Rebilly\OpenAPI\JsonSchema\Validator;
use Rebilly\OpenAPI\UnexpectedValueException;
use stdClass;
use function array_map;
use function array_merge;
use function is_array;
use function is_numeric;
use function json_decode;
use function json_encode;
use function reset;

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
        $this->expectedHeadersSchemas = array_map([$this, 'normalizeJsonSchema'], $expectedHeadersSchemas);
        $this->validator = new Validator('undefined');
    }

    public function toString(): string
    {
        return 'matches an specified headers schemas';
    }

    protected function matches($actualHeaders): bool
    {
        if (!is_array($actualHeaders)) {
            throw new UnexpectedValueException('Array expected');
        }

        foreach ($this->expectedHeadersSchemas as $name => $expectedSchema) {
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

    private static function normalizeJsonSchema($schema): stdClass
    {
        return json_decode(json_encode($schema));
    }

    /**
     * The PSR-7 says that the header values MUST be an array of strings,
     * but OpenAPI allow scalar values. So if scalar value expected,
     * we try to cast array to scalar, using first element.
     *
     * @return mixed
     */
    private static function normalizeHeaderValue(array $value, string $type)
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
