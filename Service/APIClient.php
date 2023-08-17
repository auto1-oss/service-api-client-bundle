<?php

namespace Auto1\ServiceAPIClientBundle\Service;

use Auto1\ServiceAPIComponentsBundle\Service\Logger\LoggerAwareTrait;
use Http\Client\Common\BatchClient;
use Http\Client\HttpClient;
use Auto1\ServiceAPIClientBundle\Service\Request\RequestFactoryInterface;
use Auto1\ServiceAPIClientBundle\Service\Response\ResponseTransformerInterface;
use Auto1\ServiceAPIRequest\ServiceRequestInterface;
use Psr\Log\LogLevel;

/**
 * Class APIClient.
 */
class APIClient implements APIClientInterface
{
    use LoggerAwareTrait;

    /**
     * @var RequestFactoryInterface
     */
    private $requestFactory;

    /**
     * @var ResponseTransformerInterface
     */
    private $responseTransformer;

    /**
     * @var HttpClient
     */
    private $client;

    /**
     * @var string
     */
    private $requestTimeLogLevel;

    /**
     * APIClient constructor.
     *
     * @param RequestFactoryInterface      $requestFactory
     * @param ResponseTransformerInterface $responseTransformer
     * @param HttpClient                   $client
     * @param string                       $requestTimeLogLevel
     */
    public function __construct(
        RequestFactoryInterface $requestFactory,
        ResponseTransformerInterface $responseTransformer,
        HttpClient $client,
        string $requestTimeLogLevel = LogLevel::DEBUG
    ) {
        $this->requestFactory = $requestFactory;
        $this->responseTransformer = $responseTransformer;
        $this->client = $client;
        $this->requestTimeLogLevel = $requestTimeLogLevel;
    }

    /**
     * {@inheritdoc}
     */
    public function send(ServiceRequestInterface $serviceRequest)
    {
        $request = $this->requestFactory->create($serviceRequest);

        $startTime = \microtime(true);
        $response = $this->client->sendRequest($request);
        $this->getLogger()->log(
            $this->requestTimeLogLevel,
            'HttpClient request time (ms)',
            [
                'requestPath' => $request->getUri()->getPath(),
                'requestTime' => \round(\microtime(true) - $startTime, 3) * 1000,
            ]
        );

        return $this->responseTransformer->transform($response, $serviceRequest);
    }

    /**
     * @experimental method.
     * not covered with tests
     *
     * @param ServiceRequestInterface[] $serviceRequests
     *
     * @return object[]
     */
    public function sendBatch(array $serviceRequests): array
    {
        $batchClient = new BatchClient($this->client);

        $httpRequests = [];
        foreach ($serviceRequests as $serviceRequest) {
            $httpRequests[] = $this->requestFactory->create($serviceRequest);
        }

        $responses = $batchClient->sendRequests($httpRequests)->getResponses();

        $objects = [];
        foreach ($responses as $key => $response) {
            $serviceRequest = $serviceRequests[$key];
            $objects[] = $this->responseTransformer->transform($response, $serviceRequest);
        }

        return $objects;
    }
}
