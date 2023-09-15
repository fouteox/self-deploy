<?php

namespace App\Filament\Resources\ServerResource\Pages;

use App\Filament\Resources\ServerResource;
use App\Models\PendingDeploymentException;
use App\Models\Server;
use App\Models\Site;
use App\Models\SiteType;
use App\Models\TlsSetting;
use App\Server\PhpVersion;
use App\Traits\HandlesUserContext;
use App\Traits\RedirectsIfProvisioned;
use AymanAlhattami\FilamentPageWithSidebar\Traits\HasPageSidebar;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rules\Unique;

class CreateSiteServer extends Page
{
    use HandlesUserContext, HasPageSidebar, RedirectsIfProvisioned;

    protected static string $resource = ServerResource::class;

    protected static ?string $title = 'Create Site';

    protected static string $view = 'filament.resources.server-resource.pages.create-site-server';

    public Server $record;

    public ?array $data = [];

    public string $type_key = 'server_public_key';

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
                            ->prefix('https://')
                            ->placeholder('example.com')
                            ->required()
                            ->maxValue(255)
                            ->unique(modifyRuleUsing: function (Unique $rule) {
                                return $rule->where('server_id', $this->record->id);
                            }),
                        Grid::make()
                            ->schema([
                                Select::make('php_version')
                                    ->label('PHP Version')
                                    ->options($this->record->installedPhpVersions())
                                    ->default(array_keys($this->record->installedPhpVersions())[0])
                                    ->selectablePlaceholder(false)
                                    ->required()
                                    ->enum(PhpVersion::class)
                                    ->in(array_keys($this->record->installedPhpVersions()))
                                    ->native(false),
                                Select::make('type')
                                    ->label('Project type')
                                    ->options([
                                        'laravel' => 'Laravel',
                                        'wordpress' => 'WordPress',
                                        'static' => 'Static',
                                    ])
                                    ->default('laravel')
                                    ->selectablePlaceholder(false)
                                    ->required()
                                    ->enum(SiteType::class),
                            ]),
                        TextInput::make('web_folder')
                            ->label('Web folder')
                            ->default('/public')
                            ->requiredUnless('type', SiteType::Wordpress->value)
                            ->maxValue(255),
                        Checkbox::make('zero_downtime_deployment')
                            ->label('Enable zero downtime deployment')
                            ->default(true)
                            ->rules(['boolean']),
                        TextInput::make('repository_url')
                            ->label('Repository URL')
                            ->maxValue(255),
                        TextInput::make('repository_branch')
                            ->label('Repository branch')
                            ->default('main')
                            ->maxValue(255),
                        $this->createTextInput(
                            name: 'server_public_key',
                            label: 'Public Key Server',
                            default: $this->record->user_public_key ?? 'default',
                            helperText: 'Make sure this key is added to Github or other repository provider.',
                            switchAction: fn () => $this->type_key = 'deploy_key_uuid',
                            switchLabel: 'Switch to deploy key'
                        ),
                        $this->createTextInput(
                            name: 'deploy_key_uuid',
                            label: 'Deploy key UUID',
                            default: 'test',
                            helperText: 'Instead of adding the public key of the server, you can add this deploy key to Github or other repository provider.',
                            switchAction: fn () => $this->type_key = 'server_public_key',
                            switchLabel: 'Switch to server\'s public key',
                            rules: ['uuid']
                        ),
                    ]),
                Actions::make([
                    Action::make('create')
                        ->action(fn () => $this->store()),
                ]),
            ])
            ->model(Site::class)
            ->statePath('data');
    }

    protected function createTextInput(string $name, string $label, string $default, string $helperText, callable $switchAction, string $switchLabel, array $rules = []): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->helperText($helperText)
            ->default($default)
            ->disabled()
            ->dehydrated()
            ->hidden(fn ($get) => $this->type_key !== $name)
            ->extraAttributes(function ($state) {
                return [
                    'x-on:click' => 'window.navigator.clipboard.writeText("'.$state.'"); $tooltip("Copied to clipboard", { timeout: 1500 });',
                ];
            })
            ->suffixAction(
                Action::make('copy')
                    ->icon('heroicon-m-clipboard')
            )
            ->hintAction(
                Action::make('switchAction')
                    ->label($switchLabel)
                    ->action($switchAction)
            )
            ->rules($rules);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @throws PendingDeploymentException
     */
    public function store()
    {
        //        abort_unless($this->team()->subscriptionOptions()->canCreateSiteOnServer($server), 403);

        $site = $this->record->sites()->make(Arr::except($this->form->getState(), ['deploy_key_uuid', 'server_public_key']));

        $site->tls_setting = TlsSetting::Auto;
        $site->user = $this->record->username;
        $site->path = "/home/$site->user/$site->address";
        $site->forceFill($site->type->defaultAttributes($site->zero_downtime_deployment));

        if ($this->form->getState()['deploy_key_uuid']) {
            $deployKey = Cache::get("deploy-key-$this->record->id-{$this->form->getState()['deploy_key_uuid']}");

            if (! $deployKey) {
                Notification::make()
                    ->title(__('The deploy key has expired. Please try again.'))
                    ->danger()
                    ->send();

                return back();
            }

            $site->deploy_key_public = $deployKey->publicKey;
            $site->deploy_key_private = $deployKey->privateKey;
        }

        $site->save();

        $this->logActivity(__("Created site ':address' on server ':server'", ['address' => $site->address, 'server' => $this->record->name]), $site);

        $deployment = $site->deploy(user: $this->user());

        if ($this->form->getState()['deploy_key_uuid']) {
            Cache::forget($this->form->getState()['deploy_key_uuid']);
        }
        // TODO: gerer la redirection sur une nouvelle route et créer une clé uuid
        //        return to_route('servers.sites.deployments.show', [$server, $site, $deployment]);
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
