<?php

declare(strict_types=1);
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

use PHPUnit\Framework\Constraint\Constraint;
use Rebilly\OpenAPI\JsonSchema\Validator;
use stdClass;
use function json_encode;

/**
 * Constraint that asserts that the object matches the expected JSON Schema.
 */
final class JsonSchemaConstraint extends Constraint
{
    private $schema;

    private $context;

    private $validator;

    private $errors = [];

    public function __construct(stdClass $schema, string $context = null)
    {
        $this->schema = $schema;
        $this->context = $context ?: 'schema';
        $this->validator = new Validator($this->context);
    }

    public function toString(): string
    {
        return "matches defined {$this->context}";
    }

    protected function matches($other): bool
    {
        $this->errors = $this->validator->validate($other, $this->schema);

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
}
