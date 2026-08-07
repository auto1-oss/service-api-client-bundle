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
 * Mimics a request DTO extending a base class: fields live on both levels.
 */
class ExtendedMultipartRequestStub extends BaseMultipartRequestStub
{
    /**
     * @var string|null
     */
    private $ownField;

    public function getOwnField(): ?string
    {
        return $this->ownField;
    }

    public function setOwnField(?string $ownField): self
    {
        $this->ownField = $ownField;

        return $this;
    }
}