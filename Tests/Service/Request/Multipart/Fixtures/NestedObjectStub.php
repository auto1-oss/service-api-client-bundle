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

namespace Auto1\ServiceAPIClientBundle\Tests\Service\Request\Multipart\Fixtures;

/**
 * A plain nested value object, used both standalone and as array elements.
 */
class NestedObjectStub
{
    /**
     * @var string|null
     */
    private $label;

    /**
     * @var string|null
     */
    private $code;

    /**
     * @var string|null
     */
    private $displayName;

    public function __construct(?string $label = null, ?string $code = null, ?string $displayName = null)
    {
        $this->label = $label;
        $this->code = $code;
        $this->displayName = $displayName;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }
}
