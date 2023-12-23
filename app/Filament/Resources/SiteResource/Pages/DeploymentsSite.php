<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Filament\Resources\SiteResource;
use App\Traits\BreadcrumbTrait;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

class DeploymentsSite extends Page
{
    use BreadcrumbTrait, InteractsWithRecord {
        BreadcrumbTrait::getBreadcrumbs insteadof InteractsWithRecord;
    }

    protected static string $resource = SiteResource::class;

    protected static string $view = 'filament.resources.site-resource.pages.deployments-site';

    protected static ?string $title = 'Deployments';

    protected static ?string $navigationIcon = 'heroicon-s-queue-list';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        static::authorizeResourceAccess();
    }
}
