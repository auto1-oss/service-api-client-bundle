<?php

namespace Auto1\ServiceAPIClientBundle\Service\Response;

use Auto1\ServiceAPIClientBundle\Exception\Response\NotAuthorizedException;
use Auto1\ServiceAPIComponentsBundle\Service\Endpoint\EndpointRegistryInterface;
use Auto1\ServiceAPIComponentsBundle\Service\Logger\LoggerAwareTrait;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Auto1\ServiceAPIClientBundle\DTO\ErrorResponse;
use Auto1\ServiceAPIClientBundle\Exception\Response\MalformedResponseException;
use Auto1\ServiceAPIClientBundle\Exception\Response\NotFoundException;
use Auto1\ServiceAPIClientBundle\Exception\ResponseException;
use Auto1\ServiceAPIRequest\ServiceRequestInterface;

/**
 * Class ResponseTransformer
 */
class ResponseTransformer implements ResponseTransformerInterface
{
    use LoggerAwareTrait;

    /**
     * @var EndpointRegistryInterface
     */
    private $endpointRegistry;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * ResponseTransformer constructor.
     * @param EndpointRegistryInterface $endpointRegistry
     * @param SerializerInterface $serializer
     */
    public function __construct(
        EndpointRegistryInterface $endpointRegistry,
        SerializerInterface $serializer
    ) {
        $this->endpointRegistry = $endpointRegistry;
        $this->serializer = $serializer;
    }

    /**
     * @param ResponseInterface       $response
     * @param ServiceRequestInterface $serviceRequest
     *
     * @return object|object[]|string
     * @throws ResponseException
     */
    public function transform(ResponseInterface $response, ServiceRequestInterface $serviceRequest)
    {
        $endpoint = $this->endpointRegistry->getEndpoint($serviceRequest);

        $responseBody = $response->getBody()->getContents();
        $responseClass = $endpoint->getResponseClass();
        $responseFormat = $endpoint->getResponseFormat();
        $dateTimeFormat = $endpoint->getDateTimeFormat();

        try {
            /* in case the Response does not need to be transformed */
            if (null === $responseClass && $response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                return $responseBody;
            }

            /* Normal successful deserialized response */
            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                return $this->serializer->deserialize(
                    $responseBody,
                    $responseClass,
                    $responseFormat,
                    [
                        DateTimeNormalizer::FORMAT_KEY => $dateTimeFormat
                    ]
                );
            }

            /* throw NotFound for 404 */
            if ($response->getStatusCode() === Response::HTTP_NOT_FOUND) {
                $classParts = explode('\\', $responseClass);
                $message = sprintf('%s not found', end($classParts));
                $errorDTO = new ErrorResponse();
                $errorDTO->setStatus($response->getStatusCode());
                $errorDTO->setMessage($message);
                throw new NotFoundException($errorDTO, $errorDTO->getStatus(), $errorDTO->getMessage());
            }

            if ($response->getStatusCode() === Response::HTTP_UNAUTHORIZED) {
                $errorDTO = new ErrorResponse();
                $errorDTO->setStatus($response->getStatusCode());
                $errorDTO->setMessage($response->getReasonPhrase());
                throw new NotAuthorizedException($errorDTO, $errorDTO->getStatus(), $errorDTO->getMessage());
            }

            /* All other errors */
            $errorDTO = new ErrorResponse();
            $errorDTO->setStatus($response->getStatusCode());
            $errorDTO->setMessage($response->getReasonPhrase());
            throw new ResponseException($errorDTO, $errorDTO->getStatus(), $errorDTO->getMessage());
        } catch (UnexpectedValueException $e) {
            /* Deserialization failure */
            $message = sprintf('Error response %s cannot be deserialized', $response->getStatusCode());
            $this->getLogger()->error($message, [
                'responseCode' => $response->getStatusCode(),
                'responseBody' => $responseBody,
                'exception' => $e,
            ]);
            $errorDTO = new ErrorResponse();
            $errorDTO->setStatus(500);
            $errorDTO->setMessage($message);
            throw new MalformedResponseException($errorDTO, $errorDTO->getStatus(), $errorDTO->getMessage(), $e);
        }
    }
}
