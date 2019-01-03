<?php

namespace Auto1\ServiceAPIClientBundle\Service\Request;

use Auto1\ServiceAPIComponentsBundle\Exception\Request\InvalidArgumentException;
use Auto1\ServiceAPIComponentsBundle\Exception\Request\MalformedRequestException;
use Auto1\ServiceAPIComponentsBundle\Service\Endpoint\EndpointRegistryInterface;
use Auto1\ServiceAPIComponentsBundle\Service\Logger\LoggerAwareTrait;
use Http\Message\MessageFactory;
use Http\Message\UriFactory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Auto1\ServiceAPIRequest\ServiceRequestInterface;

/**
 * Class RequestFactory.
 */
class RequestFactory implements RequestFactoryInterface
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
     * @var RequestVisitorRegistryInterface
     */
    private $requestVisitorRegistry;

    /**
     * @var UriFactory
     */
    private $uriFactory;

    /**
     * @var MessageFactory
     */
    private $messageFactory;

    /**
     * RequestFactory constructor.
     *
     * @param EndpointRegistryInterface       $endpointRegistry
     * @param SerializerInterface             $serializer
     * @param RequestVisitorRegistryInterface $requestVisitorRegistry
     * @param UriFactory                      $uriFactory
     * @param MessageFactory                  $messageFactory
     */
    public function __construct(
        EndpointRegistryInterface $endpointRegistry,
        SerializerInterface $serializer,
        RequestVisitorRegistryInterface $requestVisitorRegistry,
        UriFactory $uriFactory,
        MessageFactory $messageFactory
    ) {
        $this->endpointRegistry = $endpointRegistry;
        $this->serializer = $serializer;
        $this->requestVisitorRegistry = $requestVisitorRegistry;
        $this->uriFactory = $uriFactory;
        $this->messageFactory = $messageFactory;
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException
     */
    public function create(ServiceRequestInterface $serviceRequest): RequestInterface
    {
        $endpoint = $this->endpointRegistry->getEndpoint($serviceRequest);
        $uri = $this->getRequestUri($serviceRequest);

        if ($serviceRequest instanceof StreamInterface) {
            $requestBody = $serviceRequest;
        } else {
            $requestBody = $this->serializer->serialize($serviceRequest, $endpoint->getRequestFormat());
        }

        $httpRequest = $this->messageFactory->createRequest(
            $endpoint->getMethod(),
            $uri,
            [],
            $requestBody
        );

        $httpRequest = $this->visitRequest($httpRequest, $endpoint->getRequestFormat());

        $this->getLogger()->debug(
            'API HTTP request',
            [
                'URI' => (string)$httpRequest->getUri(),
                'Method' => $httpRequest->getMethod(),
                'RequestBody' => (string)$httpRequest->getBody(),
                'RequestHeaders' => $httpRequest->getHeaders(),
            ]
        );

        return $httpRequest;
    }

    /**
     * @param RequestInterface $request
     * @param string           $requestFormat
     *
     * @return RequestInterface
     */
    private function visitRequest(RequestInterface $request, string $requestFormat): RequestInterface
    {
        foreach ($this->requestVisitorRegistry->getRegisteredRequestVisitors($requestFormat) as $visitor) {
            $request = $visitor->visit($request);
        }

        return $request;
    }

    /**
     * @param ServiceRequestInterface $serviceRequest
     *
     * @return UriInterface
     */
    private function getRequestUri(ServiceRequestInterface $serviceRequest)
    {
        $endpoint = $this->endpointRegistry->getEndpoint($serviceRequest);
        $baseUrl = $endpoint->getBaseUrl();
        $path = $endpoint->getPath();

        //check for placeholders
        preg_match_all('/{(\w*)}/', $path, $matches);
        foreach ($matches[0] as $index => $placeholder) {
            $getterMethod = 'get'.ucfirst($matches[1][$index]);
            if (!method_exists($serviceRequest, $getterMethod)) {
                $message = 'Invalid request path argumentAlias';
                $errorCode = Response::HTTP_BAD_REQUEST;
                $this->getLogger()->error($message, ['argumentAlias' => $matches[1][$index]]);
                throw new InvalidArgumentException($message, $errorCode);
            }
            $value = $serviceRequest->$getterMethod();
            $path = str_replace($placeholder, $value, $path);
        }

        if (!$this->validateEndpointPath($path)) {
            $message = 'Invalid request path';
            $errorCode = Response::HTTP_BAD_REQUEST;
            $this->getLogger()->error($message, ['requestPath' => $path]);
            throw new MalformedRequestException($message, $errorCode);
        }

        $uri = $this->uriFactory->createUri($baseUrl.$path);

        return $uri;
    }

    /**
     * @param string $path
     *
     * @return bool
     */
    private function validateEndpointPath(string $path): bool
    {
        $pathContainsUnmappedArguments = preg_match('/[{}]/', $path);
        $pathContainsEmptyFolders = preg_match('/\/\//', $path);

        return !$pathContainsUnmappedArguments && !$pathContainsEmptyFolders;
    }
}
