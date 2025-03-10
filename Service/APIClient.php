<?php

namespace Auto1\ServiceAPIClientBundle\Service;

use Auto1\ServiceAPIClientBundle\Service\Request\RequestFactoryInterface;
use Auto1\ServiceAPIClientBundle\Service\Response\ResponseTransformerInterface;
use Auto1\ServiceAPIRequest\ServiceRequestInterface;
use Http\Client\Common\BatchClient;
use Http\Client\HttpClient;

/**
 * Class APIClient.
 */
class APIClient implements APIClientInterface
{
    /**
     * @var RequestTimer
     */
    private $requestTimer;

    /**
     * @var ClientLoggerRegistry
     */
    private $clientLoggerRegistry;

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
     * APIClient constructor.
     *
     * @param RequestTimer                 $requestTimer
     * @param ClientLoggerRegistry         $clientLoggerRegistry
     * @param RequestFactoryInterface      $requestFactory
     * @param ResponseTransformerInterface $responseTransformer
     * @param HttpClient                   $client
     */
    public function __construct(
        RequestTimer $requestTimer,
        ClientLoggerRegistry $clientLoggerRegistry,
        RequestFactoryInterface $requestFactory,
        ResponseTransformerInterface $responseTransformer,
        HttpClient $client
    ) {
        $this->requestTimer = $requestTimer;
        $this->clientLoggerRegistry = $clientLoggerRegistry;
        $this->requestFactory = $requestFactory;
        $this->responseTransformer = $responseTransformer;
        $this->client = $client;
    }

    /**
     * {@inheritdoc}
     */
    public function send(ServiceRequestInterface $serviceRequest)
    {
        $request = $this->requestFactory->create($serviceRequest);

        $this->requestTimer->from($request);
        $this->clientLoggerRegistry->logRequest($serviceRequest, $request);

        $response = $this->client->sendRequest($request);

        $duration = $this->requestTimer->to($request);
        $this->clientLoggerRegistry->logResponse($serviceRequest, $request, $response, $duration);

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
        foreach ($serviceRequests as $key => $serviceRequest) {
            $httpRequests[$key] = $request = $this->requestFactory->create($serviceRequest);
            $this->requestTimer->from($request);
            $this->clientLoggerRegistry->logRequest($serviceRequest, $request);
        }

        $responses = $batchClient->sendRequests($httpRequests)->getResponses();

        $objects = [];
        foreach ($responses as $key => $response) {
            $serviceRequest = $serviceRequests[$key];
            $httpRequest = $httpRequests[$key];

            $duration = $this->requestTimer->to($httpRequest);
            $this->clientLoggerRegistry->logResponse($serviceRequest, $httpRequest, $response, $duration);
            $objects[] = $this->responseTransformer->transform($response, $serviceRequest);
        }

        return $objects;
    }
}
