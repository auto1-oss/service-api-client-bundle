<?php

namespace Auto1\ServiceAPIClientBundle\Tests\Service;

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
    protected function setUp()
    {
        $this->endpointRegistryProphecy = $this->prophesize(EndpointRegistryInterface::class);
        $this->serializerProphecy = $this->prophesize(SerializerInterface::class);
    }

    /**
     * @return void
     */
    public function testTransformSuccess()
    {
        $responseBodyContent = '{$responseBodyContent:$responseBodyContent}';
        $successStatusCode = Response::HTTP_CREATED;
        $object = new \stdClass();
        $objectClass = 'TransformedClass';
        $format = EndpointInterface::FORMAT_JSON;
        $dateFormat = 'Y-m-d';

        $responseBodyProphecy = $this->prophesize(StreamInterface::class);
        $responseBodyProphecy->getContents()
            ->willReturn($responseBodyContent)
            ->shouldBeCalled()
        ;
        /** @var StreamInterface $responseBody */
        $responseBody = $responseBodyProphecy->reveal();

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()
            ->willReturn($responseBody)
            ->shouldBeCalled()
        ;
        $responseProphecy->getStatusCode()
            ->willReturn($successStatusCode)
            ->shouldBeCalled()
        ;
        /** @var ResponseInterface $response */
        $response = $responseProphecy->reveal();

        $endpointProphecy = $this->prophesize(EndpointInterface::class);
        $endpointProphecy->getResponseClass()
            ->willReturn($objectClass)
            ->shouldBeCalled()
        ;
        $endpointProphecy->getResponseFormat()
            ->willReturn($format)
            ->shouldBeCalled()
        ;
        $endpointProphecy->getDateTimeFormat()
            ->willReturn($dateFormat)
            ->shouldBeCalled()
        ;

        /** @var ServiceRequestInterface $serviceRequest */
        $serviceRequest = $this->prophesize(ServiceRequestInterface::class)->reveal();

        $this->endpointRegistryProphecy
            ->getEndpoint($serviceRequest)
            ->willReturn($endpointProphecy->reveal())
            ->shouldBeCalled()
        ;

        $this->serializerProphecy
            ->deserialize($responseBodyContent, $objectClass, $format, [DateTimeNormalizer::FORMAT_KEY => $dateFormat])
            ->willReturn($object)
            ->shouldBeCalled()
        ;

        $responseTransformer = new ResponseTransformer(
            $this->endpointRegistryProphecy->reveal(),
            $this->serializerProphecy->reveal()
        );

        $responseTransformer->transform($response, $serviceRequest);
    }

    /**
     * @return void
     */
    public function testTransformFails()
    {
        $this->expectException(ResponseException::class);

        $responseBodyContent = '{$responseBodyContent:$responseBodyContent}';
        $failedStatusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        $objectClass = 'TransformedClass';
        $format = EndpointInterface::FORMAT_JSON;
        $dateFormat = 'Y-m-d';

        $responseBodyProphecy = $this->prophesize(StreamInterface::class);
        $responseBodyProphecy->getContents()
            ->willReturn($responseBodyContent)
            ->shouldBeCalled()
        ;
        /** @var StreamInterface $responseBody */
        $responseBody = $responseBodyProphecy->reveal();

        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()
            ->willReturn($responseBody)
            ->shouldBeCalled()
        ;
        $responseProphecy->getStatusCode()
            ->willReturn($failedStatusCode)
            ->shouldBeCalled()
        ;
        $responseProphecy->getReasonPhrase()
            ->willReturn('somePhrase')
            ->shouldBeCalled()
        ;
        /** @var ResponseInterface $response */
        $response = $responseProphecy->reveal();

        $endpointProphecy = $this->prophesize(EndpointInterface::class);
        $endpointProphecy->getResponseClass()
            ->willReturn($objectClass)
            ->shouldBeCalled()
        ;
        $endpointProphecy->getResponseFormat()
            ->willReturn($format)
            ->shouldBeCalled()
        ;
        $endpointProphecy->getDateTimeFormat()
            ->willReturn($dateFormat)
            ->shouldBeCalled()
        ;
        $endpoint = $endpointProphecy->reveal();

        $this->endpointRegistryProphecy
            ->getEndpoint(Argument::type(ServiceRequestInterface::class))
            ->willReturn($endpoint)
            ->shouldBeCalled()
        ;

        /** @var ServiceRequestInterface $serviceRequest */
        $serviceRequest = $this->prophesize(ServiceRequestInterface::class)->reveal();

        $this->serializerProphecy
            ->deserialize(Argument::any(), Argument::any(), Argument::any(), Argument::any())
            ->willThrow(new UnexpectedValueException());

        $responseTransformer = new ResponseTransformer(
            $this->endpointRegistryProphecy->reveal(),
            $this->serializerProphecy->reveal()
        );

        $responseTransformer->transform($response, $serviceRequest);
    }
}
