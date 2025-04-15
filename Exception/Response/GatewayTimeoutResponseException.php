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

namespace Auto1\ServiceAPIClientBundle\Exception\Response;

use Auto1\ServiceAPIClientBundle\DTO\ResponseTransformer\ErrorResponse;
use Auto1\ServiceAPIClientBundle\Exception\ResponseException;

class GatewayTimeoutResponseException extends ResponseException
{
    private $errorResponse;

    public function __construct(
        ErrorResponse $errorResponse,
        string $message = '',
        \Throwable $previous = null
    ) {
        $this->errorResponse = $errorResponse;
        parent::__construct(
            null,
            $this->errorResponse->status,
            $message,
            $previous
        );
    }
}
