<?php
/*
* This file is part of the auto1-oss/service-api-client-bundle.
*
* (c) AUTO1 Group SE https://www.auto1-group.com
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace Auto1\ServiceAPIClientBundle\Service;

use Auto1\ServiceAPIClientBundle\Service\Request\RequestFactoryInterface;
use Auto1\ServiceAPIClientBundle\Service\Response\ResponseTransformerInterface;
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
     * @var HttpAsyncClient
     */
    private $client;

    /**
     * APIAsyncClient constructor.
     *
     * @param RequestTimer                 $requestTimer
     * @param ClientLoggerRegistry         $clientLoggerRegistry
     * @param RequestFactoryInterface      $requestFactory
     * @param ResponseTransformerInterface $responseTransformer
     * @param HttpAsyncClient              $client
     */
    public function __construct(
        RequestTimer $requestTimer,
        ClientLoggerRegistry $clientLoggerRegistry,
        RequestFactoryInterface $requestFactory,
        ResponseTransformerInterface $responseTransformer,
        HttpAsyncClient $client
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
    public function sendAsync(ServiceRequestInterface $serviceRequest)
    {
        $request = $this->requestFactory->create($serviceRequest);

        $this->requestTimer->from($request);
        $this->clientLoggerRegistry->logRequest($serviceRequest, $request);

        return $this->client->sendAsyncRequest($request)->then(
            function (ResponseInterface $response) use ($serviceRequest, $request) {
                $duration = $this->requestTimer->to($request);
                $this->clientLoggerRegistry->logResponse($serviceRequest, $request, $response, $duration);

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
