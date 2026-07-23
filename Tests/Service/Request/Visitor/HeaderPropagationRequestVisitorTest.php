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
    }

    public function testDecorate()
    {
        $headerNamesArray = [
            'additionalHeader1',
            'additionalHeader2',
        ];
        $timesShouldBeCalled = \count($headerNamesArray);

        // Populate the request's real HeaderBag instead of replacing the `headers`
        // property (assigning it directly is deprecated as of Symfony 7 / removed later).
        foreach ($headerNamesArray as $headerName) {
            $this->previousRequest->headers->set($headerName, 'someHeaderValue');
        }

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
