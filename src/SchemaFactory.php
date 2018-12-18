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

use JsonSchema\SchemaStorage;
use JsonSchema\Uri\UriResolver;
use JsonSchema\Uri\UriRetriever;

final class SchemaFactory
{
    public function create(string $uri)
    {
        if (strpos($uri, '//') === false) {
            $uri = "file://{$uri}";
        }

        $schemaStorage = new SchemaStorage(new UriRetriever(), new UriResolver());
        $schemaStorage->addSchema($uri);

        return new Schema($schemaStorage->getSchema($uri));
    }
}
