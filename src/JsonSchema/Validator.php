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
use JsonSchema\Entity\JsonPointer;
use stdClass;

/**
 * JSON Schema validator facade.
 */
final class Validator
{
    private $validator;

    private $context;

    public function __construct(string $context)
    {
        $factory = new Factory(null, null, Constraint::CHECK_MODE_VALIDATE_SCHEMA);
        $factory->setConstraintClass('request body', SchemaConstraint::class);
        $factory->setConstraintClass('response body', SchemaConstraint::class);
        $this->validator = $factory->createInstanceFor($context);
        $this->context = $context;
    }

    /**
     * @param mixed $value
     * @param stdClass $schema
     * @param JsonPointer|null $path
     *
     * @return array
     */
    public function validate($value, stdClass $schema, JsonPointer $path = null): array
    {
        $this->validator->check($value, $schema, $path);

        return $this->validator->getErrors();
    }

    public static function serializeErrors(array $errors): string
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
