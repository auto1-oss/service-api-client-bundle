<?php
/*
* This file is part of the auto1-oss/service-api-client-bundle.
*
* (c) AUTO1 Group SE https://www.auto1-group.com
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Auto1\ServiceAPIClientBundle\Service\Request;

use Auto1\ServiceAPIComponentsBundle\Exception\Request\InvalidArgumentException;
use Auto1\ServiceAPIComponentsBundle\Exception\Request\MalformedRequestException;
use Auto1\ServiceAPIComponentsBundle\Service\Endpoint\EndpointRegistryInterface;
use Auto1\ServiceAPIComponentsBundle\Service\Endpoint\EndpointInterface;
use Auto1\ServiceAPIComponentsBundle\Service\Logger\LoggerAwareTrait;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\RequestFactoryInterface as PsrRequestFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\StreamFactoryInterface as PsrStreamFactoryInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\UriFactoryInterface as PsrUriFactoryInterface;
use Psr\Log\LoggerAwareInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Auto1\ServiceAPIRequest\ServiceRequestInterface;

/**
 * Class RequestFactory.
 */
class RequestFactory implements RequestFactoryInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private const METHODS_WITHOUT_BODY = ['GET', 'HEAD', 'OPTIONS', 'TRACE'];

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
     * @var PsrUriFactoryInterface
     */
    private $uriFactory;

    /**
     * @var PsrRequestFactoryInterface
     */
    private $requestFactory;

    /**
     * @var PsrStreamFactoryInterface
     */
    private $streamFactory;

    /**
     * @var bool
     */
    private $strictModeEnabled;

    /**
     * RequestFactory constructor.
     *
     * @param EndpointRegistryInterface       $endpointRegistry
     * @param SerializerInterface             $serializer
     * @param RequestVisitorRegistryInterface $requestVisitorRegistry
     * @param PsrUriFactoryInterface          $uriFactory
     * @param PsrRequestFactoryInterface      $requestFactory
     * @param PsrStreamFactoryInterface       $streamFactory
     * @param bool                            $strictModeEnabled
     */
    public function __construct(
        EndpointRegistryInterface $endpointRegistry,
        SerializerInterface $serializer,
        RequestVisitorRegistryInterface $requestVisitorRegistry,
        PsrUriFactoryInterface $uriFactory,
        PsrRequestFactoryInterface $requestFactory,
        PsrStreamFactoryInterface $streamFactory,
        bool $strictModeEnabled = false
    ) {
        $this->endpointRegistry = $endpointRegistry;
        $this->serializer = $serializer;
        $this->requestVisitorRegistry = $requestVisitorRegistry;
        $this->uriFactory = $uriFactory;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
        $this->strictModeEnabled = $strictModeEnabled;
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
        $requestBody = $this->getRequestBody($serviceRequest, $endpoint);

        $httpRequest = $this->requestFactory->createRequest($endpoint->getMethod(), $uri);

        if (null !== $requestBody) {
            $body = $requestBody instanceof StreamInterface
                ? $requestBody
                : $this->streamFactory->createStream((string) $requestBody);

            $httpRequest = $httpRequest->withBody($body);
        }

        return $this->visitRequest($httpRequest, $endpoint->getRequestFormat());
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
    private function getRequestUri(ServiceRequestInterface $serviceRequest): UriInterface
    {
        $endpoint = $this->endpointRegistry->getEndpoint($serviceRequest);
        $baseUrl = $endpoint->getBaseUrl();
        $path = $endpoint->getPath();
        $queryParams = $this->parseQueryParams($path);

        //check for placeholders
        preg_match_all('/{([\w-]*)}/', $path, $matches);
        foreach ($matches[0] as $index => $placeholder) {
            $property = $matches[1][$index];
            $getterMethod = $this->getGetterMethodName($property);
            if (!method_exists($serviceRequest, $getterMethod)) {
                $message = 'Invalid request path argumentAlias';
                $errorCode = Response::HTTP_BAD_REQUEST;
                $this->getLogger()->error($message, ['argumentAlias' => $matches[1][$index]]);
                throw new InvalidArgumentException($message, $errorCode);
            }
            $value = $serviceRequest->$getterMethod();
            if ($value instanceof \DateTimeInterface) {
                $value = $value->format($endpoint->getDateTimeFormat() ?: \DATE_ATOM);
            }
            if (array_key_exists($property, $queryParams)) {
                if (null === $value) {
                    $path = str_replace($queryParams[$property].'='.$placeholder, '', $path);

                    continue;
                }
                $value = urlencode((string)$value);
            }
            $path = str_replace($placeholder, $value, $path);
        }

        $path = $this->normalizeQueryString($path);

        if (!$this->validateEndpointPath($path)) {
            $message = 'Invalid request path';
            $errorCode = Response::HTTP_BAD_REQUEST;
            $this->getLogger()->error($message, ['requestPath' => $path]);
            throw new MalformedRequestException($message, $errorCode);
        }

        return $this->uriFactory->createUri($baseUrl.$path);
    }

    private function getGetterMethodName(string $property): string
    {
        return 'get'.str_replace(['-', '_'], '', ucwords($property, '-_'));
    }

    /**
     * @param ServiceRequestInterface $serviceRequest
     * @param EndpointInterface       $endpoint
     * @return mixed
     */
    private function getRequestBody(ServiceRequestInterface $serviceRequest, EndpointInterface $endpoint)
    {
        if ($this->isMethodWithoutBody($endpoint->getMethod()) && $this->strictModeEnabled) {
            return null;
        }

        if ($serviceRequest instanceof StreamInterface) {
            return $serviceRequest;
        }

        return $this->serializer->serialize($serviceRequest, $endpoint->getRequestFormat());
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

    /**
     * Removes query string separator artifacts left behind when a parameter is dropped
     *
     * @param string $path
     *
     * @return string
     */
    private function normalizeQueryString(string $path): string
    {
        $path = (string)preg_replace('/&{2,}/', '&', $path);
        $path = str_replace('?&', '?', $path);

        return rtrim($path, '?&');
    }

    /**
     * Convert URI string to array with query parameters (filtered out with predefined values and in reverse order)
     * Input: '/route-string?first-param={firstParam}&second-param=secondValue'
     * Output: ['firstParam' => 'first-param']
     *
     * @param string $path
     *
     * @return array
     */
    private function parseQueryParams(string $path): array
    {
        $queryParamsArray = [];
        $queryParamsString = parse_url($path, PHP_URL_QUERY);

        if (null !== $queryParamsString) {
            parse_str($queryParamsString, $queryParamsArray);
            $queryParamsArray = array_filter($queryParamsArray, [$this, 'filterQueryParamConstant']);
            $queryParamsArray = array_map([$this, 'trimCurlyBrackets'], $queryParamsArray);

            return array_flip($queryParamsArray);
        }

        return $queryParamsArray;
    }

    /**
     * @param $queryValue
     *
     * @return bool
     */
    private function filterQueryParamConstant($queryValue)
    {
        return $queryValue !== $this->trimCurlyBrackets($queryValue);
    }

    /**
     * @param string $str
     *
     * @return string
     */
    private function trimCurlyBrackets($str)
    {
        return trim($str, '{}');
    }

    private function isMethodWithoutBody(string $method): bool
    {
        return in_array($method, self::METHODS_WITHOUT_BODY, true);
    }
}
