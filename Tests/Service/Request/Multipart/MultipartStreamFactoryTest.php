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

namespace Auto1\ServiceAPIClientBundle\Tests\Service\Request\Multipart;

use Auto1\ServiceAPIClientBundle\Service\Request\Multipart\MultipartStream;
use Auto1\ServiceAPIClientBundle\Service\Request\Multipart\MultipartStreamFactory;
use Auto1\ServiceAPIClientBundle\Tests\Service\Request\Multipart\Fixtures\ExtendedMultipartRequestStub;
use Auto1\ServiceAPIClientBundle\Tests\Service\Request\Multipart\Fixtures\MultipartRequestStub;
use Auto1\ServiceAPIClientBundle\Tests\Service\Request\Multipart\Fixtures\NestedObjectStub;
use Auto1\ServiceAPIComponentsBundle\Exception\Request\MalformedRequestException;
use Auto1\ServiceAPIComponentsBundle\Multipart\MetadataStream;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Serializer\Mapping\AttributeMetadata;
use Symfony\Component\Serializer\Mapping\ClassMetadata;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class MultipartStreamFactoryTest extends TestCase
{
    private const TARGET_FILE_CONTENT = 'PNG-CONTENT';

    private const TARGET_FIELDS_BY_CLASS = [
        MultipartRequestStub::class => [
            'file',
            'coverImage',
            'description',
            'version',
            'enabled',
            'createdAt',
            'tags',
            'owner',
            'documents',
            'attachments',
        ],
        ExtendedMultipartRequestStub::class => [
            'ownField',
            'parentField',
        ],
    ];

    /**
     * @var Psr17Factory
     */
    private $streamFactory;

    protected function setUp(): void
    {
        $this->streamFactory = new Psr17Factory();
    }

    public function testCreateBuildsFilePartsAndFieldParts(): void
    {
        $fileStream = $this->streamFactory->createStream(self::TARGET_FILE_CONTENT);
        $file = new MetadataStream($fileStream, 'photo.png', 'image/png');
        $coverImage = $this->streamFactory->createStream('COVER-CONTENT');
        $request = (new MultipartRequestStub())
            ->setFile($file)
            ->setCoverImage($coverImage)
            ->setDescription('Hello world')
            ->setVersion(42)
            ->setEnabled(true);
        $target = $this->getCut();

        $stream = $target->create($request);

        $body = (string) $stream;

        // file part: filename + content-type taken from stream metadata
        self::assertStringContainsString('name="file"; filename="photo.png"', $body);
        self::assertStringContainsString('Content-Type: image/png', $body);
        self::assertStringContainsString(self::TARGET_FILE_CONTENT, $body);

        // file part without metadata: filename falls back to the field name, octet-stream
        self::assertStringContainsString('name="coverImage"; filename="coverImage"', $body);
        self::assertStringContainsString('Content-Type: application/octet-stream', $body);
        self::assertStringContainsString('COVER-CONTENT', $body);

        // scalar fields
        self::assertStringContainsString('name="description"', $body);
        self::assertStringContainsString('Hello world', $body);
        self::assertStringContainsString('name="version"', $body);
        self::assertStringContainsString('42', $body);
        self::assertStringContainsString('name="enabled"', $body);
    }

    /**
     * @dataProvider booleanWireValueProvider
     */
    public function testCreateStringifiesBooleansAsFormFriendlyDigits(bool $enabled, string $wireValue): void
    {
        $request = (new MultipartRequestStub())
            ->setEnabled($enabled);
        $target = $this->getCut();

        $stream = $target->create($request);

        $body = (string) $stream;
        $pattern = sprintf('/name="enabled".*?\r\n\r\n%s\r\n/s', $wireValue);
        self::assertRegExp($pattern, $body);
    }

    public function booleanWireValueProvider(): array
    {
        return [
            'true is sent as 1' => [true, '1'],
            'false is sent as 0' => [false, '0'],
        ];
    }

    public function testCreateStringifiesValuesThroughTheNormalizer(): void
    {
        $createdAt = new \DateTimeImmutable('2024-01-02 03:04:05');
        $request = (new MultipartRequestStub())
            ->setCreatedAt($createdAt);
        $target = $this->getCut();

        $stream = $target->create($request);

        $body = (string) $stream;
        self::assertStringContainsString('name="createdAt"', $body);
        self::assertStringContainsString('2024-01-02', $body);
    }

    public function testCreateFlattensAnArrayOfScalars(): void
    {
        $request = (new MultipartRequestStub())
            ->setTags(['alpha', 'beta']);
        $target = $this->getCut();

        $stream = $target->create($request);

        $body = (string) $stream;
        self::assertStringContainsString('name="tags[0]"', $body);
        self::assertStringContainsString('alpha', $body);
        self::assertStringContainsString('name="tags[1]"', $body);
        self::assertStringContainsString('beta', $body);
    }

    public function testCreateFlattensANestedObject(): void
    {
        $owner = new NestedObjectStub('Alice', 'A-1', 'Alice A.');
        $request = (new MultipartRequestStub())
            ->setOwner($owner);
        $target = $this->getCut();

        $stream = $target->create($request);

        $body = (string) $stream;
        self::assertStringContainsString('name="owner[label]"', $body);
        self::assertStringContainsString('Alice', $body);
        self::assertStringContainsString('name="owner[code]"', $body);
        self::assertStringContainsString('A-1', $body);
        self::assertStringContainsString('name="owner[displayName]"', $body);
        self::assertStringContainsString('Alice A.', $body);
    }

    public function testCreateFlattensAnArrayOfObjects(): void
    {
        $firstDocument = new NestedObjectStub('first', 'D-1');
        $secondDocument = new NestedObjectStub('second', 'D-2');
        $request = (new MultipartRequestStub())
            ->setDocuments([$firstDocument, $secondDocument]);
        $target = $this->getCut();

        $stream = $target->create($request);

        $body = (string) $stream;
        self::assertStringContainsString('name="documents[0][label]"', $body);
        self::assertStringContainsString('first', $body);
        self::assertStringContainsString('name="documents[1][code]"', $body);
        self::assertStringContainsString('D-2', $body);
    }

    public function testCreateBuildsFilePartsForACollectionOfStreams(): void
    {
        $firstStream = $this->streamFactory->createStream('FIRST');
        $firstAttachment = new MetadataStream($firstStream, 'a.txt', 'text/plain');
        $secondStream = $this->streamFactory->createStream('SECOND');
        $secondAttachment = new MetadataStream($secondStream, 'b.txt', 'text/plain');
        $request = (new MultipartRequestStub())
            ->setAttachments([$firstAttachment, $secondAttachment]);
        $target = $this->getCut();

        $stream = $target->create($request);

        $body = (string) $stream;
        self::assertStringContainsString('name="attachments[0]"; filename="a.txt"', $body);
        self::assertStringContainsString('FIRST', $body);
        self::assertStringContainsString('name="attachments[1]"; filename="b.txt"', $body);
        self::assertStringContainsString('SECOND', $body);
        self::assertStringContainsString('Content-Type: text/plain', $body);
    }

    public function testCreateSkipsNullProperties(): void
    {
        $file = $this->streamFactory->createStream(self::TARGET_FILE_CONTENT);
        $request = (new MultipartRequestStub())
            ->setFile($file);
        $target = $this->getCut();

        $stream = $target->create($request);

        $body = (string) $stream;
        self::assertStringContainsString('name="file"', $body);
        self::assertStringNotContainsString('name="description"', $body);
        self::assertStringNotContainsString('name="version"', $body);
        self::assertStringNotContainsString('name="enabled"', $body);
        self::assertStringNotContainsString('name="coverImage"', $body);
    }

    public function testCreateIncludesPrivatePropertiesOfParentClasses(): void
    {
        $request = (new ExtendedMultipartRequestStub())
            ->setParentField('from-parent')
            ->setOwnField('own-value');
        $target = $this->getCut();

        $stream = $target->create($request);

        $body = (string) $stream;
        self::assertStringContainsString('name="parentField"', $body);
        self::assertStringContainsString('from-parent', $body);
        self::assertStringContainsString('name="ownField"', $body);
        self::assertStringContainsString('own-value', $body);
    }

    public function testCreateSkipsFieldsIgnoredInTheSerializerMetadata(): void
    {
        $ignoredFields = ['description'];
        $classMetadataFactory = $this->classMetadataFactoryStub($ignoredFields);
        $request = (new MultipartRequestStub())
            ->setDescription('Hello world')
            ->setVersion(42);
        $target = $this->getCut(null, $classMetadataFactory);

        $stream = $target->create($request);

        $body = (string) $stream;
        self::assertStringNotContainsString('name="description"', $body);
        self::assertStringContainsString('name="version"', $body);
    }

    public function testCreateNamesFieldsThroughTheNameConverter(): void
    {
        $nameConverter = $this->createMock(NameConverterInterface::class);
        $nameConverter->method('normalize')->willReturnCallback(
            static function (string $propertyName): string {
                return 'converted_' . $propertyName;
            }
        );
        $request = (new MultipartRequestStub())
            ->setDescription('Hello world');
        $target = $this->getCut(null, null, $nameConverter);

        $stream = $target->create($request);

        $body = (string) $stream;
        self::assertStringContainsString('name="converted_description"', $body);
        self::assertStringNotContainsString('name="description"', $body);
    }

    public function testCreateExposesTheBoundaryAsStreamMetadata(): void
    {
        $request = (new MultipartRequestStub())
            ->setDescription('x');
        $target = $this->getCut();

        $stream = $target->create($request);

        $boundary = $stream->getBoundary();
        $metadataBoundary = $stream->getMetadata(MultipartStream::METADATA_BOUNDARY);
        $body = (string) $stream;
        self::assertNotSame('', $boundary);
        self::assertSame($boundary, $metadataBoundary);
        self::assertStringStartsWith('--' . $boundary . "\r\n", $body);
        self::assertStringEndsWith('--' . $boundary . "--\r\n", $body);
    }

    public function testCreateExposesTheCorrectBoundaryForABodyWithoutParts(): void
    {
        $request = new MultipartRequestStub();
        $target = $this->getCut();

        $stream = $target->create($request);

        $boundary = $stream->getBoundary();
        $body = (string) $stream;
        $expectedBody = sprintf("--%s--\r\n", $boundary);
        self::assertSame($expectedBody, $body);
    }

    public function testCreateThrowsWhenTheNormalizerReturnsAnObject(): void
    {
        $objectResult = new \stdClass();
        $normalizer = $this->createMock(NormalizerInterface::class);
        $normalizer->method('normalize')->willReturn($objectResult);
        $owner = new NestedObjectStub('Alice');
        $request = (new MultipartRequestStub())
            ->setOwner($owner);
        $target = $this->getCut($normalizer);

        $this->expectException(MalformedRequestException::class);

        $target->create($request);
    }

    /**
     * @dataProvider unsafeFilenameProvider
     */
    public function testCreateSanitizesTheFilenameMetadata(string $unsafeFilename, string $expectedFilename): void
    {
        $fileStream = $this->streamFactory->createStream('FILE-CONTENT');
        $file = new MetadataStream($fileStream, $unsafeFilename, 'text/plain');
        $request = (new MultipartRequestStub())
            ->setFile($file);
        $target = $this->getCut();

        $stream = $target->create($request);

        $body = (string) $stream;
        $expectedDisposition = sprintf('name="file"; filename="%s"', $expectedFilename);
        self::assertStringContainsString($expectedDisposition, $body);
    }

    public function unsafeFilenameProvider(): array
    {
        return [
            'double quotes are stripped' => ['x"; filename="y.png', 'x; filename=y.png'],
            'CR/LF are stripped' => ["a\r\nContent-Disposition: forged\r\n.png", 'aContent-Disposition: forged.png'],
            'path segments are stripped' => ['../../etc/passwd', 'passwd'],
        ];
    }

    public function testCreateSanitizesMapKeysInterpolatedIntoPartNames(): void
    {
        $unsafeKey = 'a"; filename="x';
        $request = (new MultipartRequestStub())
            ->setTags([$unsafeKey => 'value']);
        $target = $this->getCut();

        $stream = $target->create($request);

        $body = (string) $stream;
        self::assertStringContainsString('name="tags[a; filename=x]"', $body);
    }

    private function getCut(
        ?NormalizerInterface $normalizer = null,
        ?ClassMetadataFactoryInterface $classMetadataFactory = null,
        ?NameConverterInterface $nameConverter = null
    ): MultipartStreamFactory {
        $normalizer = $normalizer ?? $this->normalizerStub();
        $classMetadataFactory = $classMetadataFactory ?? $this->classMetadataFactoryStub();
        $nameConverter = $nameConverter ?? $this->identityNameConverterStub();
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        return new MultipartStreamFactory(
            $this->streamFactory,
            $normalizer,
            $propertyAccessor,
            $classMetadataFactory,
            $nameConverter
        );
    }

    /**
     * @param string[] $ignoredFields
     */
    private function classMetadataFactoryStub(array $ignoredFields = []): ClassMetadataFactoryInterface
    {
        $classMetadataFactory = $this->createMock(ClassMetadataFactoryInterface::class);
        $classMetadataFactory->method('getMetadataFor')->willReturnCallback(
            static function (string $class) use ($ignoredFields): ClassMetadata {
                $classMetadata = new ClassMetadata($class);
                foreach (self::TARGET_FIELDS_BY_CLASS[$class] ?? [] as $field) {
                    $attributeMetadata = new AttributeMetadata($field);
                    $attributeMetadata->setIgnore(in_array($field, $ignoredFields, true));
                    $classMetadata->addAttributeMetadata($attributeMetadata);
                }

                return $classMetadata;
            }
        );

        return $classMetadataFactory;
    }

    private function identityNameConverterStub(): NameConverterInterface
    {
        $nameConverter = $this->createMock(NameConverterInterface::class);
        $nameConverter->method('normalize')->willReturnArgument(0);

        return $nameConverter;
    }

    /**
     * Stand-in for the request serializer: returns a deep-normalized representation
     * (scalars/arrays as-is, dates formatted, objects turned into arrays) so the
     * factory has something to flatten.
     */
    private function normalizerStub(): NormalizerInterface
    {
        $normalize = static function ($value) use (&$normalize) {
            if (null === $value || is_scalar($value)) {
                return $value;
            }

            if ($value instanceof \DateTimeInterface) {
                return $value->format('Y-m-d');
            }

            if ($value instanceof NestedObjectStub) {
                return [
                    'label' => $value->getLabel(),
                    'code' => $value->getCode(),
                    'displayName' => $value->getDisplayName(),
                ];
            }

            if (is_iterable($value)) {
                $normalized = [];
                foreach ($value as $key => $item) {
                    $normalized[$key] = $normalize($item);
                }

                return $normalized;
            }

            return (string) $value;
        };

        $normalizer = $this->createMock(NormalizerInterface::class);
        $normalizer->method('normalize')->willReturnCallback(
            static function ($value) use ($normalize) {
                return $normalize($value);
            }
        );

        return $normalizer;
    }
}