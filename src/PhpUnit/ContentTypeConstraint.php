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
 * Constraint that asserts that the content-type matches the expected types.
 *
 * TODO: Checking params, instead of skipping
 */
final class ContentTypeConstraint extends Constraint
{
    /**
     * @var array
     */
    private $allowedTypes;

    /**
     * @param array $allowedTypes
     */
    public function __construct(array $allowedTypes)
    {
        parent::__construct();

        $this->allowedTypes = array_map([$this, 'stripParams'], $allowedTypes);
    }

    /**
     * {@inheritdoc}
     */
    protected function matches($other)
    {
        return in_array($this->stripParams($other), $this->allowedTypes, true);
    }

    /**
     * {@inheritdoc}
     */
    public function toString()
    {
        return 'is an allowed content-type (' . implode(', ', $this->allowedTypes) . ')';
    }

    /**
     * @param string $type
     *
     * @return string
     */
    private static function stripParams($type)
    {
        return strstr($type, ';', true) ?: $type;
    }
}
