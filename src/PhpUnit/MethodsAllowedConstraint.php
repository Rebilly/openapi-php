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

namespace Rebilly\OpenAPI\PhpUnit;

use PHPUnit\Framework\Constraint\Constraint;

/**
 * Constraint that asserts that the HTTP method matches the expected allowed methods.
 */
final class MethodsAllowedConstraint extends Constraint
{
    private $allowedMethods;

    public function __construct(array $allowedMethods)
    {
        parent::__construct();
        $this->allowedMethods = array_map('mb_strtoupper', $allowedMethods);
    }

    public function toString(): string
    {
        return 'matches an allowed methods (' . implode(', ', $this->allowedMethods) . ')';
    }

    protected function matches($other): bool
    {
        if (is_string($other)) {
            return in_array(mb_strtoupper($other), $this->allowedMethods, true);
        }

        return empty(array_diff($this->allowedMethods, array_map('mb_strtoupper', $other)));
    }
}
