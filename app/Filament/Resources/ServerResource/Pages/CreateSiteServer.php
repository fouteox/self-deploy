<?php

namespace App\Filament\Resources\ServerResource\Pages;

use App\Filament\Resources\ServerResource;
use App\Models\Server;
use App\Traits\RedirectsIfProvisioned;
use AymanAlhattami\FilamentPageWithSidebar\Traits\HasPageSidebar;
use Filament\Resources\Pages\Page;

class CreateSiteServer extends Page
{
    use HasPageSidebar, RedirectsIfProvisioned;

    protected static string $resource = ServerResource::class;

    protected static ?string $title = 'Create Site';

    protected static string $view = 'filament.resources.server-resource.pages.create-site-server';

    public Server $record;

    public function getBreadcrumbs(): array
    {
        $parentBreadcrumbs = parent::getBreadcrumbs();

        $lastElement = array_splice($parentBreadcrumbs, -1);
        $lastKey = key($lastElement);
        $lastValue = reset($lastElement);

        $parentBreadcrumbs[$this->getResource()::getUrl('sites', ['record' => $this->record])] = 'Sites';

        $parentBreadcrumbs[$lastKey] = $lastValue;

        return $parentBreadcrumbs;
    }
}
