<?php

declare(strict_types=1);

namespace Vendor\Shop\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

final class Category extends AbstractEntity
{
    protected string $title = '';

    public function getTitle(): string
    {
        return $this->title;
    }
}
