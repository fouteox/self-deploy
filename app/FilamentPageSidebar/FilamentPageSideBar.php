<?php

namespace App\FilamentPageSidebar;

use AymanAlhattami\FilamentPageWithSidebar\FilamentPageSidebar as BaseFilamentPageSidebar;
use Closure;

class FilamentPageSideBar extends BaseFilamentPageSidebar
{
    protected bool|Closure $descriptionCopyable = false;

    public function getDescriptionCopyable(): bool
    {
        return $this->descriptionCopyable;
    }

    public function setDescriptionCopyable(bool|Closure $copyable): static
    {
        $this->descriptionCopyable = $this->evaluate($copyable);

        return $this;
    }
}
