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
use stdClass;

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
        parent::__construct();
        $this->schema = $schema;
        $this->context = $context ?: 'schema';
        $this->validator = new Validator($this->context);
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

    public function toString(): string
    {
        return "matches defined {$this->context}";
    }
}
