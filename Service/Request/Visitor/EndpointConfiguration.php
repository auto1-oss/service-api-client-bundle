<?php
declare(strict_types=1);

namespace Auto1\ServiceAPIClientBundle\Service\Request\Visitor;

/**
 * Class EndpointConfiguration.
 */
class EndpointConfiguration
{
    /**
     * @var string
     */
    private $method;

    /**
     * @var string
     */
    private $path;

    /**
     * EndpointConfiguration constructor.
     *
     * @param string $method
     * @param string $path
     */
    public function __construct(string $method, string $path)
    {
        $this->method = $method;
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getPathRegexp(): string
    {
        $regex = preg_replace('/{(\w*)}/', '\w+', $this->getPath());

        return "|{$regex}|";
    }
}
