<?php

namespace App\Traits;

trait BreadcrumbTrait
{
    public function getBreadcrumbs(): array
    {
        return array_merge(static::getResource()::getBreadcrumbs($this->record), [
            0 => $this->getBreadcrumb(),
        ]);
    }
}
