## Usage
Bundle uses *php-http/httplug* client abstraction.
So you'll need to install some psr7-compatible client into your project to be used by this bundle.
For more details: [php-http/httplug clients and adapters](http://docs.php-http.org/en/latest/clients.html).

## Composer
You will need to add this to your composer.json:
```json
    "repositories": [
        {
            "type": "git",
            "url": "git@github.com:auto1-oss/service-api-request.git"
        },
        {
            "type": "git",
            "url": "git@github.com:auto1-oss/service-api-components-bundle.git"
        },
        {
            "type": "git",
            "url": "git@github.com:auto1-oss/service-api-client-bundle.git"
        }
    ]
```

## config.yml
```json
auto1_service_api_client:
    request_visitors:
        - '@visitor1'
        - '@visitor2'
```
Request visitors (RequestVisitorInterface) are aimed to modify your Request - like adding custom headers.
You can also TAG services with '**auto1.api.request_visitor**' to make them visitors.
**Warning!** By setting this configuration you will override default values!

## Example of EP definition (yaml): 
```yaml
postUnicorn:
    method:        'POST'
    baseUrl:       'http://google.com'
    path:          '/v1/unicorn'
    requestFormat: 'url'
    requestClass:  'Auto1\ServiceDTOCollection\Unicorns\Request\PostUnicorn'
    responseClass: 'Auto1\ServiceDTOCollection\Unicorns\Response\Unicorn'

listUnicorns:
    method:        'GET'
    baseUrl:       'http://google.com'
    path:          '/v1/unicorns'
    requestFormat: 'json'
    requestClass:  'Auto1\ServiceDTOCollection\Unicorns\Request\SearchUnicorns'
    responseClass: 'Auto1\ServiceDTOCollection\Unicorns\Response\Unicorn[]'
```

## Example of ServiceRequest implementation:
```php
class PostUnicorn implements ServiceRequestInterface
{
    private $horn;

    public function setHorn(string $horn): self
    {
        $this->horn = $horn;

        return $this;
    }

    public function getHorn()
    {
        return $this->horn;
    }
}

```

## Example of Repository implementation:
```php
class UnicornRepository
{
    public function __construct(APIClientInterface $apiClient,)
    {
        $this->apiClient = $apiClient;
    }

    /**
     * @param string $horn
     *
     * @return Unicorn[]
     */
    public function getListByHorn(string $horn): array
    {
        $serviceRequest = (new GetUnicornsByHornRequest())->setHorn($horn);

        return $this->apiClient->send($serviceRequest);
    }
}
```

For more info - have a look at [service-api-components-bundle](https://github.com/auto1-oss/service-api-components-bundle) usage:
