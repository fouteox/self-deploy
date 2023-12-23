<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Filament\Resources\SiteResource;
use App\Models\Log;
use App\Models\Site;
use App\Traits\BreadcrumbTrait;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

/* @method Site getRecord() */
class LogSite extends Page implements HasTable
{
    use BreadcrumbTrait, InteractsWithRecord, InteractsWithTable {
        BreadcrumbTrait::getBreadcrumbs insteadof InteractsWithRecord;
    }

    protected static string $resource = SiteResource::class;

    protected static string $view = 'filament.resources.site-resource.pages.log-site';

    protected static ?string $title = 'Logs';

    protected static ?string $navigationIcon = 'heroicon-s-book-open';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        static::authorizeResourceAccess();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Log::queryForLogs($this->getRecord()))
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('description')
                    ->wrap(),
            ])
            ->paginated(false);
    }
}
