<?php

namespace App\Traits;

trait BreadcrumbTrait
{
    public function getBreadcrumbs(): array
    {
        return [...static::getResource()::getBreadcrumbs($this->record), $this->getBreadcrumb()];
    }
}
