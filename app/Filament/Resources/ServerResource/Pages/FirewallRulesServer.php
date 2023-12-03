<?php

namespace App\Filament\Resources\ServerResource\Pages;

use App\Enum;
use App\Filament\Resources\ServerResource;
use App\Jobs\UninstallFirewallRule;
use App\Models\ActivityLog;
use App\Models\FirewallRule;
use App\Rules\FirewallPort;
use App\Server\Firewall\RuleAction;
use App\Traits\BreadcrumbTrait;
use App\Traits\RedirectsIfProvisioned;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FirewallRulesServer extends ManageRelatedRecords
{
    use BreadcrumbTrait, RedirectsIfProvisioned;

    protected static string $resource = ServerResource::class;

    protected static string $relationship = 'firewallrules';

    protected static ?string $title = 'Firewall Rules';

    protected static ?string $navigationIcon = 'heroicon-s-shield-check';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('server'))
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->sortable(),
                TextColumn::make('port')
                    ->sortable(),
                TextColumn::make('action')
                    ->state(fn (FirewallRule $record) => $record->action->name)
                    ->sortable(),
                TextColumn::make('from_ipv4')
                    ->default(__('Any'))
                    ->sortable(),
                TextColumn::make('status'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->after(function (FirewallRule $record): void {
                        ActivityLog::create([
                            'team_id' => auth()->user()->current_team_id,
                            'user_id' => auth()->user()->id,
                            'subject_id' => $record->getKey(),
                            'subject_type' => $record->getMorphClass(),
                            'description' => __("Created firewall rule ':name' on server ':server'", ['name' => $record->name, 'server' => $record->server->name]),
                        ]);
                    })
                    ->successNotificationTitle(__('The Firewall Rule has been created and will be installed on the server.')),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->after(function (FirewallRule $record): void {
                        ActivityLog::create([
                            'team_id' => auth()->user()->current_team_id,
                            'user_id' => auth()->user()->id,
                            'subject_id' => $record->getKey(),
                            'subject_type' => $record->getMorphClass(),
                            'description' => __("Updated firewall rule ':name' on server ':server'", ['name' => $record->name, 'server' => $record->server->name]),
                        ]);
                    })
                    ->successNotificationTitle(__('The Firewall Rule name has been updated.')),
                Tables\Actions\DeleteAction::make()
                    ->using(function (FirewallRule $record): void {
                        $record->markUninstallationRequest();

                        dispatch(new UninstallFirewallRule($record, auth()->user()));

                        ActivityLog::create([
                            'team_id' => auth()->user()->current_team_id,
                            'user_id' => auth()->user()->id,
                            'subject_id' => $record->getKey(),
                            'subject_type' => $record->getMorphClass(),
                            'description' => __("Deleted firewall rule ':name' on server ':server'", ['name' => $record->name, 'server' => $record->server->name]),
                        ]);

                        Notification::make()
                            ->title(__('The Firewall Rule will be uninstalled from the server.'))
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Radio::make('action')
                    ->options(Enum::options(RuleAction::class))
                    ->inline()
                    ->inlineLabel(false)
                    ->required()
                    ->enum(RuleAction::class)
                    ->visibleOn('create'),
                TextInput::make('port')
                    ->required()
                    ->rule(new FirewallPort)
                    ->visibleOn('create'),
                TextInput::make('from_ipv4')
                    ->ipv4()
                    ->visibleOn('create'),
            ]);
    }
}
