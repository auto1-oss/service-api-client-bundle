<?php

namespace Auto1\ServiceAPIClientBundle\Service;

use Auto1\ServiceAPIClientBundle\Service\Request\RequestFactoryInterface;
use Auto1\ServiceAPIClientBundle\Service\Response\ResponseTransformerInterface;
use Auto1\ServiceAPIComponentsBundle\Service\Logger\LoggerAwareTrait;
use Auto1\ServiceAPIRequest\ServiceRequestInterface;
use Http\Client\Exception\HttpException;
use Http\Client\HttpAsyncClient;
use Http\Promise\RejectedPromise;
use Psr\Http\Message\ResponseInterface;

/**
 * Class APIAsyncClient.
 */
class APIAsyncClient implements APIAsyncClientInterface
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
     * @var HttpAsyncClient
     */
    private $client;

    /**
     * @var string
     */
    private $requestTimeLogLevel;

    /**
     * APIAsyncClient constructor.
     *
     * @param RequestFactoryInterface      $requestFactory
     * @param ResponseTransformerInterface $responseTransformer
     * @param HttpAsyncClient              $client
     * @param string                       $requestTimeLogLevel
     */
    public function __construct(
        RequestFactoryInterface $requestFactory,
        ResponseTransformerInterface $responseTransformer,
        HttpAsyncClient $client,
        string $requestTimeLogLevel = 'DEBUG'
    ) {
        $this->requestFactory = $requestFactory;
        $this->responseTransformer = $responseTransformer;
        $this->client = $client;
        $this->requestTimeLogLevel = $requestTimeLogLevel;
    }

    /**
     * {@inheritdoc}
     */
    public function sendAsync(ServiceRequestInterface $serviceRequest)
    {
        $request = $this->requestFactory->create($serviceRequest);
        $startTime = \microtime(true);

        return $this->client->sendAsyncRequest($request)->then(
            function (ResponseInterface $response) use ($serviceRequest, $request, $startTime) {
                $this->getLogger()->log(
                    $this->requestTimeLogLevel,
                    'HttpClient request time (ms)',
                    [
                        'requestPath' => $request->getUri()->getPath(),
                        'requestTime' => \round(\microtime(true) - $startTime, 3) * 1000,
                        'requestHeaders' => $request->getHeaders(),
                    ]
                );
                try {
                    return $this->responseTransformer->transform($response, $serviceRequest);
                } catch (\Throwable $throwable) {
                    return new RejectedPromise(
                        new HttpException(
                            $throwable->getMessage(),
                            $request,
                            $response,
                            $throwable
                        )
                    );
                }
            }
        );
    }
}
