<?php

namespace App\Filament\Resources\ServerResource\Pages;

use App\Enum;
use App\Filament\Resources\ServerResource;
use App\Jobs\UninstallFirewallRule;
use App\Models\FirewallRule;
use App\Rules\FirewallPort;
use App\Server\Firewall\RuleAction;
use App\Traits\BreadcrumbTrait;
use App\Traits\HandlesUserContext;
use App\Traits\RedirectsIfProvisioned;
use App\View\Components\StatusColumn;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FirewallRulesServer extends ManageRelatedRecords
{
    use BreadcrumbTrait, HandlesUserContext, RedirectsIfProvisioned;

    protected static string $resource = ServerResource::class;

    protected static string $relationship = 'firewallrules';

    protected static ?string $title = 'Firewall Rules';

    protected static ?string $navigationIcon = 'heroicon-s-shield-check';

    public function getListeners(): array
    {
        return [
            'echo-private:teams.'.auth()->user()->current_team_id.',FirewallRuleDeleted' => 'refreshComponent',
            'echo-private:teams.'.auth()->user()->current_team_id.',FirewallRuleUpdated' => 'refreshComponent',
        ];
    }

    public function refreshComponent(): void
    {
        $this->resetTable();
    }

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
                TextColumn::make('status')
                    ->state(fn (FirewallRule $record): string => StatusColumn::getStatus(record: $record)),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->after(function (FirewallRule $record): void {
                        $this->logActivity(__("Created firewall rule ':name' on server ':server'", ['name' => $record->name, 'server' => $record->server->name]), $record);
                    })
                    ->successNotificationTitle(__('The Firewall Rule has been created and will be installed on the server.')),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->after(function (FirewallRule $record): void {
                        $this->logActivity(__("Updated firewall rule ':name' on server ':server'", ['name' => $record->name, 'server' => $record->server->name]), $record);
                    })
                    ->successNotificationTitle(__('The Firewall Rule name has been updated.')),
                Tables\Actions\DeleteAction::make()
                    ->using(function (FirewallRule $record): void {
                        $record->markUninstallationRequest();

                        dispatch(new UninstallFirewallRule($record, auth()->user()));

                        $this->logActivity(__("Deleted firewall rule ':name' from server ':server'", ['name' => $record->name, 'server' => $record->server->name]), $record);

                        $this->sendNotification(__('The Firewall Rule will be uninstalled from the server.'));
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
