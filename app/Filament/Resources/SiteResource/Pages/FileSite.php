<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Filament\Resources\SiteResource;
use App\Models\File;
use App\Models\Site;
use App\Traits\BreadcrumbTrait;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

/* @method Site getRecord() */
class FileSite extends Page implements HasTable
{
    use BreadcrumbTrait, InteractsWithRecord, InteractsWithTable {
        BreadcrumbTrait::getBreadcrumbs insteadof InteractsWithRecord;
    }

    protected static string $resource = SiteResource::class;

    protected static string $view = 'filament.resources.site-resource.pages.file-site';

    protected static ?string $title = 'Files';

    protected static ?string $navigationIcon = 'heroicon-s-document-text';

    public ?array $data = [];

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->form->fill();

        static::authorizeResourceAccess();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(File::queryForFiles($this->getRecord()))
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('description')
                    ->wrap(),
            ])
            ->paginated(false);
    }
}
