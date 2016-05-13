<?php
/**
 * This file is part of Rebilly.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @see http://rebilly.com
 */

namespace Rebilly\OpenAPI;

use JsonSchema\RefResolver;
use JsonSchema\Uri\UriResolver;
use JsonSchema\Uri\UriRetriever;

/**
 * Schema representation factory.
 */
final class SchemaFactory
{
    /**
     * @var RefResolver
     */
    private $resolver;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->resolver = new RefResolver(new UriRetriever(), new UriResolver());
    }

    /**
     * @param string $uri
     *
     * @return Schema
     */
    public function create($uri)
    {
        return new Schema(
            $this->resolver->resolve(
                strpos('//', $uri) === false
                    ? "file://{$uri}"
                    : $uri
            )
        );
    }
}
