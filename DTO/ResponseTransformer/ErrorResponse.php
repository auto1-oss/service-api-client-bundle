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

namespace Auto1\ServiceAPIClientBundle\DTO\ResponseTransformer;

use Auto1\ServiceAPIComponentsBundle\Service\Endpoint\EndpointInterface;

class ErrorResponse
{
    public $endpoint;
    public $message;
    public $status;
    public $body;

    public function __construct(
        EndpointInterface $endpoint,
        string $message,
        int $status,
        string $body
    ) {
        $this->body = $body;
        $this->status = $status;
        $this->message = $message;
        $this->endpoint = $endpoint;
    }
}
