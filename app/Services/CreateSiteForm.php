<?php

namespace App\Services;

use App\KeyPairGenerator;
use App\Models\Server;
use App\Models\SiteType;
use App\Server\PhpVersion;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;
use Livewire\Component;

class CreateSiteForm
{
    public static function form(Form $form, ?string $serverId = null): Form
    {
        if ($serverId !== null) {
            $servers = collect([Server::find($serverId)]);
        } else {
            $servers = Server::where('team_id', auth()->user()->current_team_id)->get();
        }

        $type_key = 'server_public_key';

        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Select::make('server_id')
                            ->label('Server')
                            ->options(fn () => $servers->pluck('name', 'id'))
                            ->required()
                            ->exists(
                                table: Server::class,
                                column: 'id',
                                modifyRuleUsing: fn (Exists $rule) => $rule->where('team_id', auth()->user()->current_team_id))
                            ->default($serverId)
                            ->live()
                            ->afterStateUpdated(function (Set $set, $state, KeyPairGenerator $keyPairGenerator) use ($servers) {
                                $userId = auth()->id();
                                $deploy_key_uuid = Cache::get('deploy-key-uuid-{$userId}');

                                if (! $deploy_key_uuid) {
                                    $deploy_key_uuid = Str::uuid()->toString();
                                    Cache::put('deploy-key-uuid-{$userId}', $deploy_key_uuid, config('session.lifetime') * 60);
                                }

                                $key_pair = Cache::remember(
                                    key: "deploy-key-$userId-$deploy_key_uuid",
                                    ttl: config('session.lifetime') * 60,
                                    callback: fn () => $keyPairGenerator->ed25519()
                                );

                                $set('server_public_key', $servers->firstWhere('id', $state)?->user_public_key);
                                $set('deploy_key', $key_pair->publicKey);
                                $set('deploy_key_uuid', $deploy_key_uuid);
                            })
                            ->hidden(fn (Component $livewire, $operation): bool => isset($livewire->record) || $operation === 'edit'),
                        TextInput::make('address')
                            ->label('Hostname')
                            ->prefix('https://')
                            ->placeholder('example.com')
                            ->required()
                            ->maxValue(255)
                            ->unique(modifyRuleUsing: function (Unique $rule, Get $get) {
                                return $rule->where('server_id', $get('server_id'));
                            })
                            ->hidden(fn (Get $get, $operation): bool => ! filled($get('server_id')) || $operation === 'edit'),
                        Grid::make()
                            ->schema([
                                Select::make('php_version')
                                    ->label('PHP Version')
                                    ->options(function (Get $get) use ($servers) {
                                        return $servers->firstWhere('id', $get('server_id'))?->installedPhpVersions() ?? [];
                                    })
                                    ->default(function (Get $get) use ($servers) {
                                        $serverId = $get('server_id');
                                        //                                        dump('test');

                                        if ($serverId === null) {
                                            return null;
                                        }

                                        $server = $servers->firstWhere('id', $serverId);

                                        return $server ? array_keys($server->installedPhpVersions())[0] ?? null : null;
                                    })
                                    ->required()
                                    ->enum(PhpVersion::class)
                                    ->visible(fn (Get $get): bool => filled($get('server_id'))),
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
                                    ->enum(SiteType::class)
                                    ->visible(fn (Get $get): bool => filled($get('server_id'))),
                            ]),
                        TextInput::make('web_folder')
                            ->label('Web folder')
                            ->default('/public')
                            ->requiredUnless('type', SiteType::Wordpress->value)
                            ->maxValue(255)
                            ->visible(fn (Get $get): bool => filled($get('server_id'))),
                        Checkbox::make('zero_downtime_deployment')
                            ->label('Enable zero downtime deployment')
                            ->default(true)
                            ->rules(['boolean'])
                            ->visible(fn (Get $get): bool => filled($get('server_id')))
                            ->hiddenOn('edit'),
                        TextInput::make('repository_url')
                            ->label('Repository URL')
                            ->maxValue(255)
                            ->visible(fn (Get $get): bool => filled($get('server_id'))),
                        TextInput::make('repository_branch')
                            ->label('Repository branch')
                            ->default('main')
                            ->maxValue(255)
                            ->visible(fn (Get $get): bool => filled($get('server_id'))),
                        Hidden::make('type_key')
                            ->default(true)
                            ->hiddenOn('edit'),
                        Hidden::make('deploy_key_uuid')
                            ->required()
                            ->hiddenOn('edit'),
                        TextInput::make('server_public_key')
                            ->label('Public Key Server')
                            ->helperText('Make sure this key is added to Github or other repository provider.')
                            ->default(fn (Get $get) => $servers->firstWhere('id', $get('server_id'))?->user_public_key)
                            ->disabled()
                            ->dehydrated()
                            ->hidden(fn ($get) => $type_key !== 'server_public_key')
                            ->extraAttributes(fn ($state): array => [
                                'x-on:click' => 'window.navigator.clipboard.writeText("'.$state.'"); $tooltip("Copied to clipboard", { timeout: 1500 });',
                            ])
                            ->suffixAction(
                                Action::make('copy')
                                    ->icon('heroicon-m-clipboard')
                            )
                            ->hintAction(
                                Action::make('switchAction')
                                    ->label('Switch to deploy key')
                                    ->action(fn (Set $set) => $set('type_key', false))
                            )
                            ->visible(fn (Get $get): bool => filled($get('server_id')) && $get('type_key'))
                            ->hiddenOn('edit'),
                        TextInput::make('deploy_key')
                            ->label('Deploy key UUID')
                            ->helperText('Instead of adding the public key of the server, you can add this deploy key to Github or other repository provider.')
                            ->disabled()
                            ->dehydrated()
                            ->hidden(fn ($get) => $type_key !== 'server_public_key')
                            ->extraAttributes(fn ($state): array => [
                                'x-on:click' => 'window.navigator.clipboard.writeText("'.$state.'"); $tooltip("Copied to clipboard", { timeout: 1500 });',
                            ])
                            ->suffixAction(
                                Action::make('copy')
                                    ->icon('heroicon-m-clipboard')
                            )
                            ->hintAction(
                                Action::make('switchAction')
                                    ->label('Switch to server\'s public key')
                                    ->action(fn (Set $set) => $set('type_key', true))
                            )
                            ->visible(fn (Get $get): bool => filled($get('server_id')) && ! $get('type_key'))
                            ->hiddenOn('edit'),
                    ]),
            ]);
    }
}
