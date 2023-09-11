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

    //    public function getBreadcrumbs(): array
    //    {
    //        $parentBreadcrumbs = parent::getBreadcrumbs();
    //
    //        // Utilisation de end() pour obtenir la dernière clé du tableau
    //        $lastKey = key(end($parentBreadcrumbs));
    //
    //        // Retirer le dernier élément et le stocker
    //        $lastBreadcrumbValue = array_pop($parentBreadcrumbs);
    //
    //        // Ajouter votre élément personnalisé
    //        $parentBreadcrumbs['your_custom_url'] = 'Your Custom Breadcrumb';
    //
    //        // Ajouter à nouveau le dernier élément du parent
    //        $parentBreadcrumbs[$lastKey] = $lastBreadcrumbValue;
    //
    //        return $parentBreadcrumbs;
    //    }
}
