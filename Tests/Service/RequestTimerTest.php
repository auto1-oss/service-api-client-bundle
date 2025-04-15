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

namespace Auto1\ServiceAPIClientBundle\Tests\Service;

use Auto1\ServiceAPIClientBundle\Service\RequestTimer;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument\Token\AnyValuesToken;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;

class RequestTimerTest extends TestCase
{
    /**
     * @var LoggerInterface|ObjectProphecy
     */
    private $logger;

    protected function setUp(): void
    {
        $this->logger = $this->prophesize(LoggerInterface::class);
    }

    public function testCalculateRequestDuration(): void
    {
        $this->logger
            ->warning(new AnyValuesToken())
            ->shouldNotBeCalled();

        $calculator = new RequestTimer();
        $calculator->setLogger($this->logger->reveal());

        $calculator->from($request = $this->createRequest());
        usleep(3 * 1000);
        $calculatedDuration = $calculator->to($request);

        self::assertTrue($calculatedDuration > 0);
    }

    public function testGetRequestDurationForRequestThatWasNotMarkedAsStarted(): void
    {
        $this->logger
            ->warning(new AnyValuesToken())
            ->shouldBeCalled();

        $calculator = new RequestTimer();
        $calculator->setLogger($this->logger->reveal());

        usleep(2 * 1000);
        $calculatedDuration = $calculator->to($this->createRequest());

        self::assertEquals(0, $calculatedDuration);
    }

    public function testCalculatesRequestDurationOnlyASingleTime(): void
    {
        $this->logger
            ->warning(new AnyValuesToken())
            ->shouldBeCalledOnce();

        $calculator = new RequestTimer();
        $calculator->setLogger($this->logger->reveal());

        $calculator->from($request = $this->createRequest());
        usleep(3 * 1000);
        $firstCalculatedDuration = $calculator->to($request);
        usleep(3 * 1000);
        $secondCalculatedDuration = $calculator->to($request);

        self::assertTrue($firstCalculatedDuration > 0);
        self::assertEquals(0, $secondCalculatedDuration);
    }

    public function testCanTrackMultipleRequestsRunningInParallel(): void
    {
        $this->logger
            ->warning(new AnyValuesToken())
            ->shouldNotBeCalled();

        $calculator = new RequestTimer();
        $calculator->setLogger($this->logger->reveal());

        $request1 = $this->createRequest();
        $request2 = $this->createRequest();

        $calculator->from($request1);
        usleep(2 * 1000);
        $calculator->from($request2);
        usleep(3 * 1000);
        $request1Duration = $calculator->to($request1);
        $request2Duration = $calculator->to($request2);

        self::assertTrue($request1Duration > $request2Duration);
        self::assertTrue($request2Duration > 0);
    }

    private function createRequest(): RequestInterface
    {
        return $this
            ->prophesize(RequestInterface::class)
            ->reveal();
    }
}
