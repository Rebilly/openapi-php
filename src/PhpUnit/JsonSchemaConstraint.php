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

use PHPUnit_Framework_Constraint as BaseConstraint;
use Rebilly\OpenAPI\JsonSchema\Validator;

/**
 * Constraint that asserts that the object matches the expected JSON Schema.
 */
final class JsonSchemaConstraint extends BaseConstraint
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
    protected function matches($other)
    {
        $this->errors = $this->validator->validate($other, $this->schema);

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
        return "matches defined {$this->context}";
    }
}
