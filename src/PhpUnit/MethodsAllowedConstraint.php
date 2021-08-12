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

use PHPUnit\Framework\Constraint\Constraint;
use function array_diff;
use function array_map;
use function implode;
use function in_array;
use function is_string;
use function mb_strtoupper;

/**
 * Constraint that asserts that the HTTP method matches the expected allowed methods.
 */
final class MethodsAllowedConstraint extends Constraint
{
    private $allowedMethods;

    public function __construct(array $allowedMethods)
    {
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
