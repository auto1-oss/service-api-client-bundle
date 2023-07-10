<?php

declare(strict_types=1);

namespace Auto1\ServiceAPIClientBundle\Service;

use Auto1\ServiceAPIClientBundle\DTO\ErrorResponse;
use Auto1\ServiceAPIClientBundle\Exception\Response\MalformedResponseException;
use Auto1\ServiceAPIComponentsBundle\Service\Endpoint\EndpointInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class SymfonyDeserializer implements LoggerAwareInterface, DeserializerInterface
{
    use LoggerAwareTrait;

    private $serializer;

    public function __construct(
        SerializerInterface $serializer
    ) {
        $this->serializer = $serializer;
        $this->setLogger(new NullLogger());
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $className
     *
     * @return T
     *
     * @throws MalformedResponseException
     */
    public function deserialize(EndpointInterface $endpoint, string $className, string $data): object
    {
        /** @var T $object */
        $object = $this->doDeserialize($endpoint, $className, $data, false);

        return $object;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $className
     *
     * @return T[]
     *
     * @throws MalformedResponseException
     */
    public function deserializeAsArray(EndpointInterface $endpoint, string $className, string $data): array
    {
        /** @var T[] $array */
        $array = $this->doDeserialize($endpoint, $className, $data, true);

        return $array;
    }

    private function doDeserialize(EndpointInterface $endpoint, string $type, string $data, bool $isArray)
    {
        $startTime = \microtime(true);

        if ($isArray) {
            $type .= '[]';
        }

        try {
            $deserialized = $this->serializer->deserialize(
                $data,
                $type,
                $endpoint->getResponseFormat(),
                [
                    DateTimeNormalizer::FORMAT_KEY => $endpoint->getDateTimeFormat(),
                ]
            );
        } catch (UnexpectedValueException $e) {
            $message = sprintf('Error - response cannot be deserialized as %s', $type);
            $this->logger->error($message, [
                'responseBody' => $data,
                'exception' => $e,
                'requestClass' => $endpoint->getRequestClass(),
                'responseClass' => $type,
            ]);

            $errorDTO = new ErrorResponse();
            $errorDTO->setStatus(500);
            $errorDTO->setMessage($message);

            throw new MalformedResponseException($errorDTO, $errorDTO->getStatus(), $errorDTO->getMessage(), $e);
        }

        $this->logger->debug(
            'Response deserialize time (ms)',
            [
                'responseClass' => $type,
                'deserializeTime' => \round(\microtime(true) - $startTime, 3) * 1000,
            ]
        );

        return $deserialized;
    }
}
