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

namespace Auto1\ServiceAPIClientBundle\Tests\Service\Request\Visitor;

use Auto1\ServiceAPIClientBundle\Service\Request\Multipart\MultipartStream;
use Auto1\ServiceAPIClientBundle\Service\Request\Visitor\MultipartContentTypeRequestVisitor;
use Auto1\ServiceAPIComponentsBundle\Exception\Request\MalformedRequestException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;

class MultipartContentTypeRequestVisitorTest extends TestCase
{
    private const TARGET_BOUNDARY = 'a1b2c3d4e5';
    private const TARGET_HEADER_NAME = 'Content-Type';
    private const TARGET_HEADER_VALUE = 'multipart/form-data; boundary=a1b2c3d4e5';

    public function testVisitSetsContentTypeFromTheBoundaryStreamMetadata(): void
    {
        $inner = $this->createMock(StreamInterface::class);
        $body = new MultipartStream($inner, self::TARGET_BOUNDARY);
        $request = $this->createMock(RequestInterface::class);
        $request->method('getBody')->willReturn($body);
        $request
            ->expects(self::once())
            ->method('withHeader')
            ->with(self::TARGET_HEADER_NAME, self::TARGET_HEADER_VALUE)
            ->willReturnSelf();
        $target = $this->getCut();

        $visited = $target->visit($request);

        self::assertSame($request, $visited);
    }

    public function testVisitNeverReadsOrSeeksTheBody(): void
    {
        $inner = $this->createMock(StreamInterface::class);
        $inner->method('isSeekable')->willReturn(false);
        $inner->expects(self::never())->method('read');
        $inner->expects(self::never())->method('rewind');
        $inner->expects(self::never())->method('seek');
        $inner->expects(self::never())->method('getContents');
        $inner->expects(self::never())->method('__toString');
        $body = new MultipartStream($inner, self::TARGET_BOUNDARY);
        $request = $this->createMock(RequestInterface::class);
        $request->method('getBody')->willReturn($body);
        $request
            ->expects(self::once())
            ->method('withHeader')
            ->with(self::TARGET_HEADER_NAME, self::TARGET_HEADER_VALUE)
            ->willReturnSelf();
        $target = $this->getCut();

        $target->visit($request);
    }

    public function testVisitLeavesABodylessRequestUntouched(): void
    {
        $body = $this->createMock(StreamInterface::class);
        $body->method('getMetadata')->willReturn(null);
        $body->method('getSize')->willReturn(0);
        $request = $this->createMock(RequestInterface::class);
        $request->method('getBody')->willReturn($body);
        $request->expects(self::never())->method('withHeader');
        $target = $this->getCut();

        $visited = $target->visit($request);

        self::assertSame($request, $visited);
    }

    /**
     * @dataProvider boundarylessBodySizeProvider
     */
    public function testVisitThrowsWhenANonEmptyBodyExposesNoBoundary(?int $bodySize): void
    {
        $body = $this->createMock(StreamInterface::class);
        $body->method('getMetadata')->willReturn(null);
        $body->method('getSize')->willReturn($bodySize);
        $request = $this->createMock(RequestInterface::class);
        $request->method('getBody')->willReturn($body);
        $request->expects(self::never())->method('withHeader');
        $target = $this->getCut();

        $this->expectException(MalformedRequestException::class);

        $target->visit($request);
    }

    public function boundarylessBodySizeProvider(): array
    {
        return [
            'non-empty body' => [16],
            'body of unknown size' => [null],
        ];
    }

    private function getCut(): MultipartContentTypeRequestVisitor
    {
        return new MultipartContentTypeRequestVisitor();
    }
}