<?php

namespace Auto1\ServiceAPIClientBundle\Tests\Service;

use Auto1\ServiceAPIClientBundle\Exception\Response\NotAuthorizedException;
use Auto1\ServiceAPIClientBundle\Exception\Response\NotFoundException;
use Auto1\ServiceAPIComponentsBundle\Service\Endpoint\EndpointInterface;
use Auto1\ServiceAPIComponentsBundle\Service\Endpoint\EndpointRegistryInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Auto1\ServiceAPIClientBundle\Exception\ResponseException;
use Auto1\ServiceAPIClientBundle\Service\Response\ResponseTransformer;
use Auto1\ServiceAPIRequest\ServiceRequestInterface;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

/**
 * Class ResponseTransformerTest.
 */
class ResponseTransformerTest extends TestCase
{
    private const RESPONSE_CLASS = 'Auto1ResponseClass';
    private const DATE_FORMAT = 'Y-m-d';
    private const RESPONSE_BODY_CONTENT = '{$responseBodyContent:$responseBodyContent}';

    /**
     * @var EndpointRegistryInterface|ObjectProphecy
     */
    private $endpointRegistryProphecy;

    /**
     * @var SerializerInterface|ObjectProphecy
     */
    private $serializerProphecy;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->endpointRegistryProphecy = $this->prophesize(EndpointRegistryInterface::class);
        $this->serializerProphecy = $this->prophesize(SerializerInterface::class);
    }

    public function testTransformSuccess(): void
    {
        $endpointProphecy = $this->createEndpointProphecy();
        $responseProphecy = $this->createResponseProphecy(Response::HTTP_CREATED);

        $serviceRequest = $this->prophesize(ServiceRequestInterface::class);
        $this->endpointRegistryProphecy
            ->getEndpoint($serviceRequest)
            ->willReturn($endpointProphecy)
            ->shouldBeCalled()
        ;

        $this->serializerProphecy
            ->deserialize(
                self::RESPONSE_BODY_CONTENT,
                self::RESPONSE_CLASS,
                EndpointInterface::FORMAT_JSON,
                [DateTimeNormalizer::FORMAT_KEY => self::DATE_FORMAT]
            )
            ->willReturn(new \stdClass())
            ->shouldBeCalled()
        ;

        $responseTransformer = new ResponseTransformer(
            $this->endpointRegistryProphecy->reveal(),
            $this->serializerProphecy->reveal()
        );

        $responseTransformer->transform($responseProphecy->reveal(), $serviceRequest->reveal());
    }

    public function testDeserializationFails(): void
    {
        $this->expectException(ResponseException::class);

        $endpointProphecy = $this->createEndpointProphecy();
        $responseProphecy = $this->createResponseProphecy(Response::HTTP_CREATED);

        $serviceRequest = $this->prophesize(ServiceRequestInterface::class);
        $this->endpointRegistryProphecy
            ->getEndpoint($serviceRequest)
            ->willReturn($endpointProphecy)
            ->shouldBeCalled()
        ;

        $this->serializerProphecy
            ->deserialize(Argument::any(), Argument::any(), Argument::any(), Argument::any())
            ->willThrow(new UnexpectedValueException());
        ;

        $responseTransformer = new ResponseTransformer(
            $this->endpointRegistryProphecy->reveal(),
            $this->serializerProphecy->reveal()
        );

        $responseTransformer->transform($responseProphecy->reveal(), $serviceRequest->reveal());
    }

    /**
     * @param ObjectProphecy|ResponseInterface $response
     * @dataProvider transformFailsDataProvider
     */
    public function testTransformFails($response, string $expectedException): void
    {
        $this->expectException($expectedException);

        $endpoint = $this->createEndpointProphecy();
        $serviceRequest = $this->prophesize(ServiceRequestInterface::class);
        $this->endpointRegistryProphecy
            ->getEndpoint($serviceRequest)
            ->willReturn($endpoint)
            ->shouldBeCalled()
        ;

        $this->serializerProphecy
            ->deserialize(Argument::any(), Argument::any(), Argument::any(), Argument::any())
            ->shouldNotBeCalled()
        ;

        $responseTransformer = new ResponseTransformer(
            $this->endpointRegistryProphecy->reveal(),
            $this->serializerProphecy->reveal()
        );

        $responseTransformer->transform($response->reveal(), $serviceRequest->reveal());
    }

    private function transformFailsDataProvider(): \Generator
    {
        $reasonPhrase = Response::$statusTexts[Response::HTTP_INTERNAL_SERVER_ERROR];
        yield Response::HTTP_INTERNAL_SERVER_ERROR => [
            $this->createResponseProphecy(Response::HTTP_INTERNAL_SERVER_ERROR, $reasonPhrase),
            ResponseException::class
        ];

        yield Response::HTTP_NOT_FOUND => [
            $this->createResponseProphecy(Response::HTTP_NOT_FOUND),
            NotFoundException::class
        ];

        $reasonPhrase = Response::$statusTexts[Response::HTTP_UNAUTHORIZED];
        yield Response::HTTP_UNAUTHORIZED => [
            $this->createResponseProphecy(Response::HTTP_UNAUTHORIZED, $reasonPhrase),
            NotAuthorizedException::class
        ];
    }

    /**
     * @return EndpointInterface|ObjectProphecy
     */
    private function createEndpointProphecy()
    {
        $endpointProphecy = $this->prophesize(EndpointInterface::class);
        $endpointProphecy->getResponseClass()
            ->willReturn(self::RESPONSE_CLASS)
            ->shouldBeCalled()
        ;
        $endpointProphecy->getResponseFormat()
            ->willReturn(EndpointInterface::FORMAT_JSON)
            ->shouldBeCalled()
        ;
        $endpointProphecy->getDateTimeFormat()
            ->willReturn(self::DATE_FORMAT)
            ->shouldBeCalled()
        ;

        return $endpointProphecy;
    }

    /**
     * @return ObjectProphecy|ResponseInterface
     */
    private function createResponseProphecy(int $statusCode, ?string $reasonPhrase = null)
    {
        $responseBodyProphecy = $this->prophesize(StreamInterface::class);
        $responseBodyProphecy->getContents()
            ->willReturn(self::RESPONSE_BODY_CONTENT)
            ->shouldBeCalled()
        ;

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()
            ->willReturn($responseBodyProphecy)
            ->shouldBeCalled()
        ;
        $responseProphecy->getStatusCode()
            ->willReturn($statusCode)
            ->shouldBeCalled()
        ;

        if ($reasonPhrase !== null) {
            $responseProphecy->getReasonPhrase()
                ->willReturn($reasonPhrase)
                ->shouldBeCalled()
            ;
        }

        return $responseProphecy;
    }
}
