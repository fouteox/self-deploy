<?php

namespace App\Filament\Resources\ServerResource\Pages;

use App\Filament\Resources\ServerResource;
use App\Models\Server;
use App\Models\Software;
use App\Traits\BreadcrumbTrait;
use App\Traits\RedirectsIfProvisioned;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

/* @method Server getRecord() */
class SoftwareServer extends Page implements HasTable
{
    use BreadcrumbTrait, InteractsWithRecord, InteractsWithTable, RedirectsIfProvisioned {
        BreadcrumbTrait::getBreadcrumbs insteadof InteractsWithRecord;
    }

    protected static string $resource = ServerResource::class;

    protected static string $view = 'filament.resources.server-resource.pages.software-server';

    protected static ?string $title = 'Softwares';

    protected static ?string $navigationIcon = 'heroicon-s-code-bracket';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        static::authorizeResourceAccess();
    }

    public function table(Table $table): Table
    {
        Software::initializeForServer($this->getRecord());

        return $table
            ->query(Software::query())
            ->columns([
                TextColumn::make('name'),
            ])
            ->filters([
                // ...
            ])
            ->actions([
                Action::make('restart'),
            ])
            ->bulkActions([
                // ...
            ])
            ->paginated(false);
    }
}
