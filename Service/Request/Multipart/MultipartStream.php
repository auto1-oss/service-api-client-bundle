<?php
/*
* This file is part of the auto1-oss/service-api-client-bundle.
*
* (c) AUTO1 Group SE https://www.auto1-group.com
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
declare(strict_types=1);

namespace Auto1\ServiceAPIClientBundle\Service\Request\Multipart;

use Auto1\ServiceAPIComponentsBundle\Multipart\MetadataStream;
use Psr\Http\Message\StreamInterface;

/**
 * PSR-7 stream decorator carrying the multipart boundary alongside the body, so
 * consumers (e.g. the Content-Type visitor) never have to read or parse the body
 * bytes — the body does not even need to be seekable.
 */
class MultipartStream extends MetadataStream
{
    public const METADATA_BOUNDARY = 'boundary';

    private string $boundary;

    public function __construct(StreamInterface $inner, string $boundary)
    {
        parent::__construct($inner);

        $this->boundary = $boundary;
    }

    public function getBoundary(): string
    {
        return $this->boundary;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata(?string $key = null)
    {
        if (self::METADATA_BOUNDARY === $key) {
            return $this->boundary;
        }

        $metadata = parent::getMetadata($key);
        if (null === $key) {
            $metadata[self::METADATA_BOUNDARY] = $this->boundary;
        }

        return $metadata;
    }
}