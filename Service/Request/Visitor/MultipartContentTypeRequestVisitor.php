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

namespace Auto1\ServiceAPIClientBundle\Service\Request\Visitor;

use Auto1\ServiceAPIClientBundle\Service\Request\Multipart\MultipartStream;
use Auto1\ServiceAPIComponentsBundle\Exception\Request\MalformedRequestException;
use Psr\Http\Message\RequestInterface;

/**
 * Sets `Content-Type: multipart/form-data; boundary=<boundary>`.
 *
 * The boundary is taken from the body's `boundary` stream metadata (set by the
 * multipart stream factory via {@see MultipartStream}), so the body is never read
 * and does not need to be seekable. A body-less request (e.g. strict mode with a
 * GET endpoint) is passed through untouched; a non-empty body without a boundary
 * is an error — sending a multipart request without a Content-Type boundary would
 * only produce an opaque 4xx on the server side.
 */
class MultipartContentTypeRequestVisitor implements RequestVisitorInterface
{
    private const HEADER_NAME = 'Content-Type';
    private const MEDIA_TYPE = 'multipart/form-data';

    /**
     * {@inheritdoc}
     */
    public function visit(RequestInterface $request): RequestInterface
    {
        $body = $request->getBody();
        $boundary = $body->getMetadata(MultipartStream::METADATA_BOUNDARY);

        if (!is_string($boundary) || '' === $boundary) {
            if (0 === $body->getSize()) {
                return $request;
            }

            throw new MalformedRequestException(
                'multipart/form-data request body does not expose its boundary as stream metadata;'
                . ' expected a body built by the multipart stream factory.'
            );
        }

        return $request->withHeader(
            self::HEADER_NAME,
            sprintf('%s; boundary=%s', self::MEDIA_TYPE, $boundary)
        );
    }
}