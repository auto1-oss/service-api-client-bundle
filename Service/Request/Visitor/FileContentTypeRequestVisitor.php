<?php

namespace Auto1\ServiceAPIClientBundle\Service\Request\Visitor;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Class FileContentTypeRequestVisitor
 */
class FileContentTypeRequestVisitor implements RequestVisitorInterface
{
    const HEADER_NAME = 'Content-Type';
    const DEFAULT_CONTENT_TYPE = 'application/octet-stream';
    
    /**
     * {@inheritdoc}
     */
    public function visit(RequestInterface $request): RequestInterface
    {
        $body = $request->getBody();
        if ($body instanceof StreamInterface) {
            $mimeType = $body->getMetadata('mime-type');
            $headerValue = self::DEFAULT_CONTENT_TYPE;
            if ($mimeType !== null) {
                $headerValue = $mimeType;
            }

            return $request->withHeader(self::HEADER_NAME, $headerValue);
        }

        return $request;
    }
}
