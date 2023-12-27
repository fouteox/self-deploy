<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Filament\Resources\SiteResource;
use App\Models\DeploymentStatus;
use App\Traits\BreadcrumbTrait;
use App\Traits\HandlesUserContext;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ManageDeploymentsSite extends ManageRelatedRecords
{
    use BreadcrumbTrait, HandlesUserContext;

    protected static string $resource = SiteResource::class;

    protected static string $relationship = 'deployments';

    protected static ?string $title = 'Deployments';

    protected static ?string $navigationIcon = 'heroicon-s-queue-list';

    public function getListeners(): array
    {
        return [
            'echo-private:teams.'.$this->team()->id.',DeploymentUpdated' => 'refreshComponent',
        ];
    }

    public function refreshComponent(): void
    {
        $this->resetTable();
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                TextEntry::make('task.output')
                    ->label(__('Output'))
                    ->formatStateUsing(fn (string $state): string => $state ? nl2br(trim($state), false) : '...')
                    ->html()
                    ->hiddenLabel()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('updated_at')
                    ->label(__('Deployed at'))
                    ->dateTime(),
                TextColumn::make('user.name')
                    ->default(__('Via Deploy URL')),
                TextColumn::make('short_git_hash')
                    ->label(__('Git Hash')),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (DeploymentStatus $state): string => match ($state->value) {
                        'finished' => 'success',
                        'pending' => 'info',
                        'timeout', 'failed' => 'danger',
                    })
                    ->alignEnd(),
            ])
            ->actions([
                ViewAction::make(),
            ]);
    }
}
