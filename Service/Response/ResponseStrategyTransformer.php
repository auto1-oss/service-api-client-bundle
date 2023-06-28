<?php

declare(strict_types=1);

namespace Auto1\ServiceAPIClientBundle\Service\Response;

use Auto1\ServiceAPIClientBundle\Service\ResponseTransformerStrategy;
use Auto1\ServiceAPIComponentsBundle\Exception\Core\ConfigurationException;
use Auto1\ServiceAPIComponentsBundle\Service\Endpoint\EndpointRegistryInterface;
use Auto1\ServiceAPIRequest\ServiceRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class ResponseStrategyTransformer implements LoggerAwareInterface, ResponseTransformerInterface
{
    use LoggerAwareTrait;

    private $endpointRegistry;
    private $responseTransformerStrategies;

    /**
     * @param ResponseTransformerStrategy[] $responseTransformerStrategies
     */
    public function __construct(
        EndpointRegistryInterface $endpointRegistry,
        array $responseTransformerStrategies
    ) {
        $this->responseTransformerStrategies = $responseTransformerStrategies;
        $this->endpointRegistry = $endpointRegistry;
        $this->setLogger(new NullLogger());
    }

    /**
     * @return object|object[]|string
     */
    public function transform(ResponseInterface $response, ServiceRequestInterface $serviceRequest)
    {
        $endpoint = $this->endpointRegistry->getEndpoint($serviceRequest);
        $responseBody = $response->getBody()->getContents();

        foreach ($this->responseTransformerStrategies as $strategy) {
            if ($strategy->supports($response)) {
                return $strategy->handle(
                    $endpoint,
                    $response,
                    $responseBody,
                );
            }
        }

        throw new ConfigurationException('There is no strategy that can handle the response');
    }
}
