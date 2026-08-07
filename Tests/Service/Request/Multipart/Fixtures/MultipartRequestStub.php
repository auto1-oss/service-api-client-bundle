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
use Psr\Http\Message\StreamInterface;

/**
 * Mimics a generated multipart request DTO: file streams plus scalar fields.
 */
class MultipartRequestStub implements ServiceRequestInterface
{
    /**
     * @var StreamInterface|null
     */
    private $file;

    /**
     * @var StreamInterface|null
     */
    private $coverImage;

    /**
     * @var string|null
     */
    private $description;

    /**
     * @var int|null
     */
    private $version;

    /**
     * @var bool|null
     */
    private $enabled;

    /**
     * @var \DateTimeInterface|null
     */
    private $createdAt;

    /**
     * @var string[]|null
     */
    private $tags;

    /**
     * @var NestedObjectStub|null
     */
    private $owner;

    /**
     * @var NestedObjectStub[]|null
     */
    private $documents;

    /**
     * @var StreamInterface[]|null
     */
    private $attachments;

    public function getFile(): ?StreamInterface
    {
        return $this->file;
    }

    public function setFile(?StreamInterface $file): self
    {
        $this->file = $file;

        return $this;
    }

    public function getCoverImage(): ?StreamInterface
    {
        return $this->coverImage;
    }

    public function setCoverImage(?StreamInterface $coverImage): self
    {
        $this->coverImage = $coverImage;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getVersion(): ?int
    {
        return $this->version;
    }

    public function setVersion(?int $version): self
    {
        $this->version = $version;

        return $this;
    }

    public function isEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(?bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getTags(): ?array
    {
        return $this->tags;
    }

    public function setTags(?array $tags): self
    {
        $this->tags = $tags;

        return $this;
    }

    public function getOwner(): ?NestedObjectStub
    {
        return $this->owner;
    }

    public function setOwner(?NestedObjectStub $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    public function getDocuments(): ?array
    {
        return $this->documents;
    }

    public function setDocuments(?array $documents): self
    {
        $this->documents = $documents;

        return $this;
    }

    public function getAttachments(): ?array
    {
        return $this->attachments;
    }

    public function setAttachments(?array $attachments): self
    {
        $this->attachments = $attachments;

        return $this;
    }
}
