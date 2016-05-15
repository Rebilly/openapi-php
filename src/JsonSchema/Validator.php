<?php
/**
 * This file is part of Rebilly.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @see http://rebilly.com
 */

namespace Rebilly\OpenAPI\JsonSchema;

use JsonSchema\Constraints\Constraint;
use JsonSchema\Constraints\Factory;
use JsonSchema\Constraints\SchemaConstraint;

/**
 * JSON Schema validator facade.
 */
final class Validator
{
    /**
     * @var Constraint
     */
    private $validator;

    /**
     * @var string
     */
    private $context;

    /**
     * Constructor.
     *
     * @param string $context
     */
    public function __construct($context)
    {
        $factory = new Factory();
        $factory->setConstraintClass('request body', SchemaConstraint::class);
        $factory->setConstraintClass('response body', SchemaConstraint::class);
        $this->validator = $factory->createInstanceFor($context);
        $this->context = $context;
    }

    /**
     * @param mixed $value
     * @param object $schema
     * @param string|null $path
     *
     * @return array
     */
    public function validate($value, $schema, $path = null)
    {
        $this->validator->check($value, $schema, $path);

        return $this->validator->getErrors();
    }

    /**
     * @param array $errors
     *
     * @return string
     */
    public static function serializeErrors(array $errors)
    {
        return "\n" . implode(
            "\n",
            array_map(
                function ($error) {
                    return "[{$error['property']}] {$error['message']}";
                },
                $errors
            )
        );
    }
}
