<?php

namespace App\Filament\Resources\ServerResource\Pages;

use App\Filament\Resources\ServerResource;
use App\Models\Server;
use App\Models\Site;
use App\Traits\RedirectsIfProvisioned;
use AymanAlhattami\FilamentPageWithSidebar\Traits\HasPageSidebar;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;

class CreateSiteServer extends Page
{
    use HasPageSidebar, RedirectsIfProvisioned;

    protected static string $resource = ServerResource::class;

    protected static ?string $title = 'Create Site';

    protected static string $view = 'filament.resources.server-resource.pages.create-site-server';

    public Server $record;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        TextInput::make('address')
                            ->label('Hostname')
                            ->prefix('http(s)://')
                            ->url()
                            ->placeholder('example.com'),
                        Grid::make()
                            ->schema([
                                Select::make('php_version')
                                    ->label('PHP Version')
                                    ->options($this->record->installedPhpVersions())
                                    ->native(false),
                                Select::make('type')
                                    ->label('Project type')
                                    ->options([
                                        'laravel' => 'Laravel',
                                        'wordpress' => 'WordPress',
                                        'static' => 'Static',
                                    ]),
                            ]),
                        TextInput::make('web_folder')
                            ->label('Web folder')
                            ->default('/public'),
                    ]),
                Actions::make([
                    Action::make('create')
                        ->action(fn () => dd($this->form->getModel(), $this->form->getState())),
                ]),
            ])
            ->model(Site::class)
            ->statePath('data');
    }

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
