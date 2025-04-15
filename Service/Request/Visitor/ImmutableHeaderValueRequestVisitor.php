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

namespace Auto1\ServiceAPIClientBundle\Service\Request\Visitor;

use Psr\Http\Message\RequestInterface;

/**
 * Class ImmutableHeaderValueRequestVisitor.
 */
class ImmutableHeaderValueRequestVisitor implements RequestVisitorInterface
{
    /**
     * @var string
     */
    private $headerName;

    /**
     * @var string
     */
    private $headerValue;

    /**
     * ImmutableHeaderValueRequestVisitor constructor.
     *
     * @param string $headerName
     * @param string $headerValue
     */
    public function __construct(string $headerName, string $headerValue)
    {
        $this->headerName = $headerName;
        $this->headerValue = $headerValue;
    }

    /**
     * {@inheritdoc}
     */
    public function visit(RequestInterface $request): RequestInterface
    {
        return $request->withHeader($this->headerName, $this->headerValue);
    }
}
