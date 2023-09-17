<?php

namespace App\Traits;

trait BreadcrumbTrait
{
    public function getBreadcrumbs(): array
    {
        $breadcrumb = $this->getBreadcrumb();

        return array_merge(self::getResource()::getBreadcrumbs($this->record), [
            0 => $breadcrumb,
        ]);
    }
}
