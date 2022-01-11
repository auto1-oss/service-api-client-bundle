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
        $cut = new EndpointConfiguration("POST", $path);

        $this->assertEquals($regex, $cut->getPathRegexp());
    }

    public function provider()
    {
        return [
            'vehicle_by_id' => [
                '/v1/some/{id}',
                '|/v1/some/\w+|'
            ]
        ];
    }
}
