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

namespace Auto1\ServiceAPIClientBundle\Service\Response;

use Auto1\ServiceAPIClientBundle\Service\ResponseTransformerStrategyInterface;
use Auto1\ServiceAPIComponentsBundle\Exception\Core\ConfigurationException;
use Auto1\ServiceAPIComponentsBundle\Service\Endpoint\EndpointRegistryInterface;
use Auto1\ServiceAPIRequest\ServiceRequestInterface;
use Psr\Http\Message\ResponseInterface;

class ResponseStrategyTransformer implements ResponseTransformerInterface
{
    private $endpointRegistry;
    private $responseTransformerStrategies;

    /**
     * @param ResponseTransformerStrategyInterface[] $responseTransformerStrategies
     */
    public function __construct(
        EndpointRegistryInterface $endpointRegistry,
        iterable $responseTransformerStrategies
    ) {
        $this->responseTransformerStrategies = $responseTransformerStrategies;
        $this->endpointRegistry = $endpointRegistry;
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
