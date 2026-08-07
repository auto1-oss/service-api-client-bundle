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

use Auto1\ServiceAPIRequest\ServiceRequestInterface;

/**
 * Base DTO class with a private property that a child instance cannot expose via reflection.
 */
abstract class BaseMultipartRequestStub implements ServiceRequestInterface
{
    /**
     * @var string|null
     */
    private $parentField;

    public function getParentField(): ?string
    {
        return $this->parentField;
    }

    public function setParentField(?string $parentField): self
    {
        $this->parentField = $parentField;

        return $this;
    }
}