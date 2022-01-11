<?php

declare(strict_types=1);

namespace Auto1\ServiceAPIClientBundle\Tests\Service;

use Auto1\ServiceAPIClientBundle\Service\Request\Visitor\EndpointConfiguration;
use PHPUnit\Framework\TestCase;

/**
 * Class EndpointConfigurationTest.
 */
class EndpointConfigurationTest extends TestCase
{
    /**
     * @dataProvider provider
     */
    public function testRegexGeneration($path, $regex)
    {
        $cut = new EndpointConfiguration('POST', $path);

        self::assertEquals($regex, $cut->getPathRegexp());
    }

    public function provider()
    {
        return [
            'endpoint_by_id' => [
                '/v1/some/{id}',
                '|/v1/some/\w+|'
            ],
            'optional_field' => [
                '/v1/some/{id}?type={type}',
                '|/v1/some/\w+\?type=\w+|'
            ]
        ];
    }
}
