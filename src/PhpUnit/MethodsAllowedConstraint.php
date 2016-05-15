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

use PHPUnit_Framework_Constraint as Constraint;

/**
 * Constraint that asserts that the HTTP method matches the expected allowed methods.
 */
final class MethodsAllowedConstraint extends Constraint
{
    /**
     * @var array
     */
    private $allowedMethods;

    /**
     * @param array $allowedMethods
     */
    public function __construct(array $allowedMethods)
    {
        parent::__construct();

        $this->allowedMethods = array_map('strtoupper', $allowedMethods);
    }

    /**
     * {@inheritdoc}
     */
    protected function matches($other)
    {
        if (is_string($other)) {
            return in_array(strtoupper($other), $this->allowedMethods);
        } else {
            return empty(array_diff($this->allowedMethods, array_map('strtoupper', $other)));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function toString()
    {
        return 'matches an allowed methods (' . implode(', ', $this->allowedMethods) . ')';
    }
}