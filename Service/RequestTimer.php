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

namespace Auto1\ServiceAPIClientBundle\Service;

use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class RequestTimer implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var \SplObjectStorage
     */
    private $ongoingRequests;

    public function __construct()
    {
        $this->ongoingRequests = new \SplObjectStorage();

        $this->setLogger(new NullLogger());
    }

    public function from(RequestInterface $request): void
    {
        $this->ongoingRequests[$request] = \microtime(true);
    }

    /**
     * @return int Time im ms
     */
    public function to(RequestInterface $request): int
    {
        $endTime = \microtime(true);

        try {
            $startTime = $this->ongoingRequests[$request];
            $this->ongoingRequests->detach($request);
        } catch (\UnexpectedValueException $e) {
            $startTime = $endTime;

            $this->logger->warning('No start time found for request, assuming request time is 0ms. This may be due to a missing logRequest call.');
        }

        return (int) (\round($endTime - $startTime, 3) * 1000);
    }
}
