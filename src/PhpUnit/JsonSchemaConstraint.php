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

/**
 * Constraint that asserts that the object matches the expected JSON Schema.
 */
final class JsonSchemaConstraint extends Constraint
{
    /**
     * @var object
     */
    private $schema;

    /**
     * @var string
     */
    private $context;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var array
     */
    private $errors = [];

    /**
     * @param object $schema
     * @param string|null $context
     */
    public function __construct($schema, $context = null)
    {
        parent::__construct();

        $this->schema = $schema;
        $this->context = $context ?: 'schema';
        $this->validator = new Validator($this->context);
    }

    /**
     * {@inheritdoc}
     */
    protected function matches($other): bool
    {
        $this->errors = $this->validator->validate($other, $this->schema);

        return empty($this->errors);
    }

    /**
     * {@inheritdoc}
     */
    protected function failureDescription($other): string
    {
        return json_encode($other) . ' ' . $this->toString();
    }

    /**
     * {@inheritdoc}
     */
    protected function additionalFailureDescription($other): string
    {
        return $this->validator->serializeErrors($this->errors);
    }

    /**
     * {@inheritdoc}
     */
    public function toString(): string
    {
        return "matches defined {$this->context}";
    }
}
