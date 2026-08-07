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

namespace Auto1\ServiceAPIClientBundle\Tests\Integration;

use Auto1\ServiceAPIClientBundle\Service\Request\RequestFactoryInterface;
use Auto1\ServiceAPIClientBundle\Tests\Integration\Fixtures\JsonRequestStub;
use Auto1\ServiceAPIClientBundle\Tests\Integration\Kernel\MultipartTestKernel;
use Auto1\ServiceAPIClientBundle\Tests\Service\Request\Multipart\Fixtures\ExtendedMultipartRequestStub;
use Auto1\ServiceAPIClientBundle\Tests\Service\Request\Multipart\Fixtures\MultipartRequestStub;
use Auto1\ServiceAPIClientBundle\Tests\Service\Request\Multipart\Fixtures\NestedObjectStub;
use Auto1\ServiceAPIComponentsBundle\Multipart\MetadataStream;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;

/**
 * Integration test: boots a real kernel (FrameworkBundle + components + client
 * bundle) and builds a multipart request through the fully-wired services. Booting
 * the kernel also verifies the bundle loads and the container compiles cleanly.
 */
class RequestBuildingKernelTest extends TestCase
{
    private const TARGET_CONTENT_TYPE_PREFIX = 'multipart/form-data; boundary=';
    private const TARGET_BOUNDARY_PLACEHOLDER = 'BOUNDARY';

    /**
     * @var MultipartTestKernel
     */
    private $kernel;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->kernel = new MultipartTestKernel('test', true);
        $this->kernel->boot();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        $this->kernel->shutdown();
    }

    /**
     * @return void
     */
    public function testTheContainerCompilesAndExposesTheRequestFactory(): void
    {
        $requestFactory = $this->kernel->getContainer()->get('test.request_factory');

        self::assertInstanceOf(RequestFactoryInterface::class, $requestFactory);
    }

    /**
     * @return void
     */
    public function testTheContainerCompilesWithoutALoggerService(): void
    {
        $kernel = new MultipartTestKernel('test', true, false);
        $kernel->boot();

        $requestFactory = $kernel->getContainer()->get('test.request_factory');
        $kernel->shutdown();

        self::assertInstanceOf(RequestFactoryInterface::class, $requestFactory);
    }

    public function testCreateBuildsAMultipartRequestThroughTheWiredServices(): void
    {
        $streamFactory = new Psr17Factory();
        $createdAt = new \DateTimeImmutable('2024-01-02 03:04:05', new \DateTimeZone('UTC'));
        $fileStream = $streamFactory->createStream('PNG-CONTENT');
        $file = new MetadataStream($fileStream, 'photo.png', 'image/png');

        $serviceRequest = (new MultipartRequestStub())
            ->setFile($file)
            ->setDescription('Hello world')
            ->setCreatedAt($createdAt)
            ->setTags(['alpha', 'beta'])
            ->setOwner(new NestedObjectStub('Alice', 'A-1', 'Alice A.'))
            ->setDocuments([new NestedObjectStub('first', 'D-1'), new NestedObjectStub('second', 'D-2')]);

        /** @var RequestFactoryInterface $requestFactory */
        $requestFactory = $this->kernel->getContainer()->get('test.request_factory');

        $request = $requestFactory->create($serviceRequest);

        self::assertSame('POST', $request->getMethod());
        self::assertSame('/v1/documents', $request->getUri()->getPath());

        $contentType = $request->getHeaderLine('Content-Type');
        self::assertStringStartsWith(self::TARGET_CONTENT_TYPE_PREFIX, $contentType);

        $prefixLength = strlen(self::TARGET_CONTENT_TYPE_PREFIX);
        $boundary = substr($contentType, $prefixLength);
        $body = (string) $request->getBody();
        $actual = str_replace($boundary, self::TARGET_BOUNDARY_PLACEHOLDER, $body);

        $b = self::TARGET_BOUNDARY_PLACEHOLDER;
        $expected = "--{$b}\r\n"
            . "Content-Type: image/png\r\n"
            . "Content-Disposition: form-data; name=\"file\"; filename=\"photo.png\"\r\n"
            . "\r\n"
            . "PNG-CONTENT\r\n"
            . "--{$b}\r\n"
            . "Content-Disposition: form-data; name=\"description\"\r\n"
            . "\r\n"
            . "Hello world\r\n"
            . "--{$b}\r\n"
            . "Content-Disposition: form-data; name=\"createdAt\"\r\n"
            . "\r\n"
            . "2024-01-02T03:04:05+0000\r\n"
            . "--{$b}\r\n"
            . "Content-Disposition: form-data; name=\"tags[0]\"\r\n"
            . "\r\n"
            . "alpha\r\n"
            . "--{$b}\r\n"
            . "Content-Disposition: form-data; name=\"tags[1]\"\r\n"
            . "\r\n"
            . "beta\r\n"
            . "--{$b}\r\n"
            . "Content-Disposition: form-data; name=\"owner[label]\"\r\n"
            . "\r\n"
            . "Alice\r\n"
            . "--{$b}\r\n"
            . "Content-Disposition: form-data; name=\"owner[code]\"\r\n"
            . "\r\n"
            . "A-1\r\n"
            . "--{$b}\r\n"
            . "Content-Disposition: form-data; name=\"owner[displayName]\"\r\n"
            . "\r\n"
            . "Alice A.\r\n"
            . "--{$b}\r\n"
            . "Content-Disposition: form-data; name=\"documents[0][label]\"\r\n"
            . "\r\n"
            . "first\r\n"
            . "--{$b}\r\n"
            . "Content-Disposition: form-data; name=\"documents[0][code]\"\r\n"
            . "\r\n"
            . "D-1\r\n"
            . "--{$b}\r\n"
            . "Content-Disposition: form-data; name=\"documents[1][label]\"\r\n"
            . "\r\n"
            . "second\r\n"
            . "--{$b}\r\n"
            . "Content-Disposition: form-data; name=\"documents[1][code]\"\r\n"
            . "\r\n"
            . "D-2\r\n"
            . "--{$b}--\r\n";

        self::assertSame($expected, $actual);
    }

    public function testCreateIncludesPrivateParentClassPropertiesThroughTheWiredServices(): void
    {
        $serviceRequest = (new ExtendedMultipartRequestStub())
            ->setParentField('from-parent')
            ->setOwnField('own-value');

        /** @var RequestFactoryInterface $requestFactory */
        $requestFactory = $this->kernel->getContainer()->get('test.request_factory');

        $request = $requestFactory->create($serviceRequest);

        $contentType = $request->getHeaderLine('Content-Type');
        self::assertStringStartsWith(self::TARGET_CONTENT_TYPE_PREFIX, $contentType);

        $prefixLength = strlen(self::TARGET_CONTENT_TYPE_PREFIX);
        $boundary = substr($contentType, $prefixLength);
        $body = (string) $request->getBody();
        $actual = str_replace($boundary, self::TARGET_BOUNDARY_PLACEHOLDER, $body);

        $b = self::TARGET_BOUNDARY_PLACEHOLDER;
        $expected = "--{$b}\r\n"
            . "Content-Disposition: form-data; name=\"ownField\"\r\n"
            . "\r\n"
            . "own-value\r\n"
            . "--{$b}\r\n"
            . "Content-Disposition: form-data; name=\"parentField\"\r\n"
            . "\r\n"
            . "from-parent\r\n"
            . "--{$b}--\r\n";

        self::assertSame($expected, $actual);
    }

    public function testCreateBuildsAJsonRequestThroughTheWiredServices(): void
    {
        $createdAt = new \DateTimeImmutable('2024-01-02 03:04:05', new \DateTimeZone('UTC'));

        $serviceRequest = (new JsonRequestStub())
            ->setName('Widget')
            ->setQuantity(3)
            ->setCreatedAt($createdAt);

        /** @var RequestFactoryInterface $requestFactory */
        $requestFactory = $this->kernel->getContainer()->get('test.request_factory');

        $request = $requestFactory->create($serviceRequest);

        self::assertSame('POST', $request->getMethod());
        self::assertSame('/v1/widgets', $request->getUri()->getPath());
        self::assertSame('application/json', $request->getHeaderLine('Content-Type'));

        $body = (string) $request->getBody();

        self::assertSame('{"name":"Widget","quantity":3,"createdAt":"2024-01-02T03:04:05+0000"}', $body);
    }
}