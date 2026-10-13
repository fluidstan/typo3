<?php

declare(strict_types=1);

namespace Vendor\Shop\Domain\Model;

use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

final class Product extends AbstractEntity
{
    protected string $title = '';

    protected string $sku = '';

    protected string $teaser = '';

    protected float $price = 0.0;

    protected int $detailPage = 0;

    protected bool $available = true;

    protected ?FileReference $image = null;

    protected ?\DateTimeImmutable $releasedAt = null;

    /** @var ObjectStorage<Category> */
    private readonly ObjectStorage $categories;

    public function __construct()
    {
        $this->categories = new ObjectStorage();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function getTeaser(): string
    {
        return $this->teaser;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getDetailPage(): int
    {
        return $this->detailPage;
    }

    public function isAvailable(): bool
    {
        return $this->available;
    }

    public function getImage(): ?FileReference
    {
        return $this->image;
    }

    public function getReleasedAt(): ?\DateTimeImmutable
    {
        return $this->releasedAt;
    }

    /**
     * @return ObjectStorage<Category>
     */
    public function getCategories(): ObjectStorage
    {
        return $this->categories;
    }
}
