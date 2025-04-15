<?php
/*
* This file is part of the auto1-oss/service-api-client-bundle.
*
* (c) AUTO1 Group SE https://www.auto1-group.com
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace Auto1\ServiceAPIClientBundle\Tests\Service;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\HttpFoundation\HeaderBag;
use Auto1\ServiceAPIClientBundle\Service\Request\Visitor\HeaderPropagationRequestVisitor;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class HeaderPropagationRequestVisitor.
 */
class HeaderPropagationRequestVisitorTest extends TestCase
{
    /**
     * @var RequestInterface|ObjectProphecy
     */
    private $requestProphecy;

    /**
     * @var HeaderBag|ObjectProphecy
     */
    private $headerBagProphecy;

    /**
     * @var Request
     */
    private $previousRequest;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->previousRequest = new Request();
        $this->requestProphecy = $this->prophesize(RequestInterface::class);
        $this->headerBagProphecy = $this->prophesize(HeaderBag::class);
    }

    public function testDecorate()
    {
        $headerNamesArray = [
            'additionalHeader1',
            'additionalHeader2',
        ];
        $timesShouldBeCalled = \count($headerNamesArray);

        $this->headerBagProphecy
            ->has(Argument::type('string'))
            ->shouldBeCalledTimes($timesShouldBeCalled)
            ->willReturn(true)
        ;
        $this->headerBagProphecy
            ->get(Argument::type('string'))
            ->shouldBeCalledTimes($timesShouldBeCalled)
            ->willReturn('someHeaderValue')
        ;
        /** @var HeaderBag $headerBag */
        $headerBag = $this->headerBagProphecy->reveal();
        $this->previousRequest->headers = $headerBag;

        $this->requestProphecy
            ->withHeader(Argument::type('string'), Argument::type('string'))
            ->shouldBeCalledTimes($timesShouldBeCalled)
            ->willReturn($this->requestProphecy)
        ;
        /** @var Request $request */
        $request = $this->requestProphecy->reveal();

        $headerPropagationRequestVisitor = new HeaderPropagationRequestVisitor(
            $this->previousRequest,
            $headerNamesArray
        );
        $headerPropagationRequestVisitor->visit($request);
    }
}
