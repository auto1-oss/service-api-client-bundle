<?php
/*
* This file is part of the auto1-oss/service-api-client-bundle.
*
* (c) AUTO1 Group SE https://www.auto1-group.com
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace Auto1\ServiceAPIClientBundle\Exception;

use Auto1\ServiceAPIComponentsBundle\Exception\AbstractException;
use Auto1\ServiceAPIClientBundle\DTO\ErrorResponse;
use Throwable;

/**
 * Class ResponseException
 */
class ResponseException extends AbstractException
{
    /**
     * ResponseException constructor.
     *
     * @param ErrorResponse|null $errorDto
     * @param int $code
     * @param string|null $message
     * @param Throwable|null $previous
     */
    public function __construct(?ErrorResponse $errorDto = null, $code = 0, $message = null, ?Throwable $previous = null)
    {
        if ($message === null) {
            $message = $errorDto
                ? $errorDto->getMessage() ?? ''
                : '';
        }

        parent::__construct($message, $code, $previous);
    }
}
