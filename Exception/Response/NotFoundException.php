<?php

namespace Auto1\ServiceAPIClientBundle\Exception\Response;

use Auto1\ServiceAPIClientBundle\Exception\ResponseException;

/**
 * Class NotFoundException
 */
class NotFoundException extends ResponseException
{
    /**
     * @var int
     */
    protected $code = 404;
}
