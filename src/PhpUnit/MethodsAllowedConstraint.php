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

/**
 * Constraint that asserts that the HTTP method matches the expected allowed methods.
 */
final class MethodsAllowedConstraint extends Constraint
{
    private $allowedMethods;

    public function __construct(array $allowedMethods)
    {
        parent::__construct();
        $this->allowedMethods = array_map('strtoupper', $allowedMethods);
    }

    protected function matches($other): bool
    {
        if (is_string($other)) {
            return in_array(strtoupper($other), $this->allowedMethods);
        } else {
            return empty(array_diff($this->allowedMethods, array_map('strtoupper', $other)));
        }
    }

    public function toString(): string
    {
        return 'matches an allowed methods (' . implode(', ', $this->allowedMethods) . ')';
    }
}
