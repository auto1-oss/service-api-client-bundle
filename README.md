## Usage
Bundle uses *php-http/httplug* client abstraction.
So you'll need to install some psr7-compatible client into your project to be used by this bundle.
For more details: [php-http/httplug clients and adapters](http://docs.php-http.org/en/latest/clients.html).

This bundle builds requests via [PSR-17](https://www.php-fig.org/psr/psr-17/) HTTP factories, discovered
with `Http\Discovery\Psr17FactoryDiscovery`. Your project therefore also needs a discoverable PSR-17
factory implementation (for example `nyholm/psr7`, or `guzzlehttp/psr7` **>= 2.0** — the PSR-17 factory
was introduced in Guzzle PSR-7 2.0, so `1.x` is not enough).

> **Upgrade note:** `Service\Request\RequestFactory` no longer depends on the abandoned
> `php-http/message-factory`. Its constructor now takes PSR-17 factories
> (`Psr\Http\Message\UriFactoryInterface`, `RequestFactoryInterface`, `StreamFactoryInterface`)
> instead of `Http\Message\UriFactory` + `Http\Message\MessageFactory`. If you instantiate or
> re-register this service yourself, update the wiring — the container service
> `auto1.api.message_factory` was replaced by `auto1.api.request_factory` and `auto1.api.stream_factory`.

> **Upgrade note:** `Service\Request\RequestFactory` now builds request bodies through a
> `RequestBodyFactoryRegistryInterface` — its constructor's second argument replaced the
> `Symfony\Component\Serializer\SerializerInterface`. If you instantiate or re-register this
> service yourself, pass `auto1.api.request.body_factory.registry` (or your own registry)
> instead of `auto1.api.request.serializer`.


## config.yml
```yaml
auto1_service_api_client:
    request_visitors:
        - '@visitor1'
        - '@visitor2'
    strict_mode: false
```
- **request_visitors** - Request visitors (RequestVisitorInterface) are aimed to modify your Request - like adding custom headers.
You can also TAG services with '**auto1.api.request_visitor**' to make them visitors.
**Warning!** By setting this configuration you will override default values!
- **strict_mode** - boolean, ```false``` by default. If it is ```true``` the request factory ignores any request body for GET, HEAD, OPTIONS and TRACE HTTP methods. 
In other words, client will always send such requests without body.

## Request body factories
The request body is built by services tagged with `auto1.api.request_body_factory`
(implementing `RequestBodyFactoryInterface`). They are resolved first-match by
descending tag `priority`, so a new request format can be supported by adding a
tagged service without changing the request factory. The bundle ships factories
for raw PSR-7 streams (except on `multipart` endpoints, where the declared
format wins), `multipart/form-data`, and a default that serializes by the
endpoint's `requestFormat` (`json`, `url`, ...).

## Logging
The bundle logs through the `psr/log` abstraction and uses the application's
`logger` service when present (e.g. MonologBundle). If no `logger` service is
registered, it falls back to a `Psr\Log\NullLogger`, so logging is optional.

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

## Multipart / file uploads
Set `requestFormat: multipart` on the endpoint and declare file fields on the
request DTO as `\Psr\Http\Message\StreamInterface`. Stream values — whether a
top-level property or an element of an array/collection (a `files[]` field) — are
sent as file parts; the filename and `Content-Type` of each file part are taken
from the stream metadata (`filename` / `mime-type`), falling back to the field
name and `application/octet-stream`. Other fields are serialized through the
request normalizer (so dates and value objects are formatted the same way as for
other formats) and nested objects/arrays are flattened into `name[child]` field
names. Fields are enumerated through the serializer's class metadata, so private
properties inherited from a parent class are included. A stream nested inside an
object is not detected as a file part. The body is buffered into a temporary
stream when the request is built, not streamed lazily from the source streams.

This requires a PSR-7 implementation (e.g. `nyholm/psr7` or `guzzlehttp/psr7`) to
be installed; the bundle discovers its PSR-17 factories automatically.

```yaml
uploadDocument:
    method:        'POST'
    baseUrl:       'http://google.com'
    path:          '/v1/documents'
    requestFormat: 'multipart'
    requestClass:  'Auto1\ServiceDTOCollection\Documents\Request\UploadDocument'
    responseClass: 'Auto1\ServiceDTOCollection\Documents\Response\Document'
```

```php
class UploadDocument implements ServiceRequestInterface
{
    private $file;        // \Psr\Http\Message\StreamInterface -> file part
    private $description; // string -> form field

    public function setFile(\Psr\Http\Message\StreamInterface $file): self
    {
        $this->file = $file;

        return $this;
    }

    public function getFile(): ?\Psr\Http\Message\StreamInterface
    {
        return $this->file;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
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
