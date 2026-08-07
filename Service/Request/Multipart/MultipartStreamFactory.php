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

use Auto1\ServiceAPIComponentsBundle\Exception\Request\MalformedRequestException;
use Auto1\ServiceAPIComponentsBundle\Multipart\MetadataStream;
use Auto1\ServiceAPIComponentsBundle\Service\Endpoint\EndpointInterface;
use Auto1\ServiceAPIRequest\ServiceRequestInterface;
use Http\Message\MultipartStream\MultipartStreamBuilder;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Builds a multipart/form-data body from a request DTO.
 *
 * `StreamInterface` values become file parts (filename / Content-Type taken from the
 * stream's `filename` / `mime-type` metadata, mirroring the incoming side's
 * UploadedFileStream), including streams nested in a collection such as a `files[]`
 * property, which are emitted as `name[0]`, `name[1]`, ... file parts. Every other
 * property value is run through the request serializer's normalizer, so dates, value
 * objects and nested objects are stringified exactly as they are for the other request
 * formats; nested arrays are flattened into `name[child]` field names. Fields are
 * enumerated through the serializer's class metadata and named through its name
 * converter, so `#[SerializedName]` / `#[Ignore]` and private parent-class properties
 * are honored consistently with the JSON request format; without such metadata the
 * field name is the property name verbatim (camelCase).
 *
 * The DTO is read property-by-property rather than normalized as a whole because a
 * live `StreamInterface` cannot survive `Serializer::normalize()` (on Symfony 7 a
 * normalizer may not return an object).
 *
 * Note: `MultipartStreamBuilder::build()` copies every part into a `php://temp`
 * buffer (spilling to disk beyond 2MB) when the body is built — the returned body is
 * a regular PSR-7 stream, not a lazy view over the source streams.
 */
class MultipartStreamFactory implements MultipartStreamFactoryInterface
{
    const DEFAULT_CONTENT_TYPE = 'application/octet-stream';

    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    /**
     * @var NormalizerInterface
     */
    private $normalizer;

    /**
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;

    /**
     * @var ClassMetadataFactoryInterface
     */
    private $classMetadataFactory;

    /**
     * @var NameConverterInterface
     */
    private $nameConverter;

    /**
     * @param StreamFactoryInterface        $streamFactory        PSR-17 factory used by the multipart builder.
     * @param NormalizerInterface           $normalizer           Stringifies non-file field values
     *                                                            (dates, value objects, nested objects).
     * @param PropertyAccessorInterface     $propertyAccessor     Resolves get/is/has/public-property access.
     * @param ClassMetadataFactoryInterface $classMetadataFactory Enumerates the DTO fields (incl. private
     *                                                            parent-class properties, minus `#[Ignore]`d ones).
     * @param NameConverterInterface        $nameConverter        Maps property names to wire field names
     *                                                            (`#[SerializedName]`, configured converter).
     */
    public function __construct(
        StreamFactoryInterface $streamFactory,
        NormalizerInterface $normalizer,
        PropertyAccessorInterface $propertyAccessor,
        ClassMetadataFactoryInterface $classMetadataFactory,
        NameConverterInterface $nameConverter
    ) {
        $this->streamFactory = $streamFactory;
        $this->normalizer = $normalizer;
        $this->propertyAccessor = $propertyAccessor;
        $this->classMetadataFactory = $classMetadataFactory;
        $this->nameConverter = $nameConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function create(ServiceRequestInterface $serviceRequest): MultipartStream
    {
        $builder = new MultipartStreamBuilder($this->streamFactory);

        foreach ($this->readProperties($serviceRequest) as $name => $value) {
            $this->appendValue($builder, $name, $value);
        }

        return new MultipartStream($builder->build(), $builder->getBoundary());
    }

    /**
     * @param MultipartStreamBuilder $builder
     * @param string                 $name
     * @param mixed                  $value
     *
     * @return void
     */
    private function appendValue(MultipartStreamBuilder $builder, string $name, $value): void
    {
        if (null === $value) {
            return;
        }

        if ($value instanceof StreamInterface) {
            $builder->addResource($name, $value, $this->fileOptions($value, $name));

            return;
        }

        // Recurse into collections before normalizing so that StreamInterface elements
        // (e.g. a `files[]` property) become file parts rather than being handed to the
        // serializer, which cannot normalize a live stream.
        if (is_iterable($value)) {
            foreach ($value as $key => $item) {
                $this->appendValue($builder, sprintf('%s[%s]', $name, $this->sanitizeName((string) $key)), $item);
            }

            return;
        }

        // Reuse the serializer so dates/value objects/nested objects are stringified
        // the same way as for the other request formats.
        $normalized = $this->normalizer->normalize($value, EndpointInterface::FORMAT_MULTIPART);

        $this->appendNormalized($builder, $name, $normalized);
    }

    /**
     * @param MultipartStreamBuilder $builder
     * @param string                 $name
     * @param mixed                  $value Already-normalized scalar or (nested) iterable.
     *
     * @return void
     */
    private function appendNormalized(MultipartStreamBuilder $builder, string $name, $value): void
    {
        if (null === $value) {
            return;
        }

        if (is_iterable($value)) {
            foreach ($value as $key => $item) {
                $this->appendNormalized($builder, sprintf('%s[%s]', $name, $this->sanitizeName((string) $key)), $item);
            }

            return;
        }

        if (is_object($value)) {
            throw new MalformedRequestException(sprintf(
                'Cannot append "%s" as a "%s" multipart form field: the request normalizer returned'
                . ' an object; normalizers must return scalars or (nested) arrays for multipart bodies.',
                get_class($value),
                $name
            ));
        }

        $builder->addResource($name, $this->stringify($value));
    }

    /**
     * @param StreamInterface $stream
     * @param string          $name
     *
     * @return array
     */
    private function fileOptions(StreamInterface $stream, string $name): array
    {
        $filename = $stream->getMetadata(MetadataStream::METADATA_FILENAME);
        $filename = is_string($filename) ? $this->sanitizeFilename($filename) : '';
        $mimeType = $stream->getMetadata(MetadataStream::METADATA_MIME_TYPE);
        $mimeType = is_string($mimeType) ? $this->sanitizeName($mimeType) : '';

        return [
            'filename' => '' !== $filename ? $filename : $name,
            'headers' => [
                'Content-Type' => '' !== $mimeType ? $mimeType : self::DEFAULT_CONTENT_TYPE,
            ],
        ];
    }

    /**
     * Strips the characters that would break out of a `name="..."` / `filename="..."`
     * Content-Disposition parameter or terminate the header line: the stream builder
     * interpolates these values into part headers without any escaping, so a
     * client-controlled key or filename could otherwise forge header parameters or
     * inject a whole extra part (CR/LF).
     *
     * @param string $name
     *
     * @return string
     */
    private function sanitizeName(string $name): string
    {
        return str_replace(["\r", "\n", '"'], '', $name);
    }

    /**
     * @param string $filename
     *
     * @return string
     */
    private function sanitizeFilename(string $filename): string
    {
        // basename() here rather than in the builder: sanitize first, and do not rely
        // on the builder's internals for a security-relevant step.
        return basename($this->sanitizeName($filename));
    }

    /**
     * Maps readable DTO fields to their values, keyed by wire field name. Fields come
     * from the serializer's class metadata and name converter, matching how the same
     * DTO would serialize for the other request formats and how nested objects are
     * normalized within this body.
     *
     * @param ServiceRequestInterface $serviceRequest
     *
     * @return array
     */
    private function readProperties(ServiceRequestInterface $serviceRequest): array
    {
        $fields = [];
        $class = get_class($serviceRequest);
        $metadata = $this->classMetadataFactory->getMetadataFor($class);

        foreach ($metadata->getAttributesMetadata() as $attributeMetadata) {
            if ($attributeMetadata->isIgnored()) {
                continue;
            }

            $name = $attributeMetadata->getName();
            if (!$this->propertyAccessor->isReadable($serviceRequest, $name)) {
                continue;
            }

            $wireName = $this->nameConverter->normalize($name, $class, EndpointInterface::FORMAT_MULTIPART);
            $fields[$this->sanitizeName($wireName)] = $this->propertyAccessor->getValue($serviceRequest, $name);
        }

        return $fields;
    }

    /**
     * @param int|float|string|bool $value
     *
     * @return string
     */
    private function stringify($value): string
    {
        // '1'/'0' (not 'true'/'false') so booleans survive loosely-typed
        // denormalization on the receiving side ('false' would coerce to true).
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }
}
