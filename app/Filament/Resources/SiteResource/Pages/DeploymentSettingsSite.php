<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Filament\Resources\SiteResource;
use App\Models\Site;
use App\Traits\BreadcrumbTrait;
use App\Traits\HandlesUserContext;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class DeploymentSettingsSite extends EditRecord
{
    use BreadcrumbTrait, HandlesUserContext;

    protected static string $resource = SiteResource::class;

    protected static ?string $title = 'Deployments Settings';

    protected static ?string $navigationIcon = 'heroicon-s-wrench-screwdriver';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        TextInput::make('deploy_notification_email')
                            ->label(__('Notification Email for Deploy URL'))
                            ->helperText(__('The email address to send notifications to when the deploy URL is called and the deployment fails.'))
                            ->email(),
                        TextInput::make('deployment_releases_retention')
                            ->label(__('Number of Releases to Retain'))
                            ->helperText(__('The number of releases to retain on the server. The oldest releases will be deleted when a new release is deployed.'))
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(50)
                            ->required(fn (Site $record): bool => $record->zero_downtime_deployment),
                        Textarea::make('shared_directories')
                            ->label(__('Shared Directories (one per line)'))
                            ->helperText(__('These directories will be shared between the old and new deployment.'))
                            ->autosize()
                            ->formatStateUsing(fn (array $state): string => implode(PHP_EOL, $state)),
                        Textarea::make('shared_files')
                            ->label(__('Shared Files (one per line)'))
                            ->helperText(__('These files will be shared between the old and new deployment.'))
                            ->autosize()
                            ->formatStateUsing(fn (array $state): string => implode(PHP_EOL, $state)),
                        Textarea::make('writeable_directories')
                            ->label(__('Writeable Directories (one per line)'))
                            ->helperText(__('These directories will be writeable by the webserver.'))
                            ->autosize()
                            ->formatStateUsing(fn (?array $state): string => implode(PHP_EOL, $state ?? [])),
                        Textarea::make('hook_before_updating_repository')
                            ->label(__('Before Updating Repository'))
                            ->helperText(__('This bash script will be executed before updating the repository.'))
                            ->autosize(),
                        Textarea::make('hook_after_updating_repository')
                            ->label(__('After Updating Repository'))
                            ->helperText(__('This bash script will be executed after updating the repository.'))
                            ->autosize(),
                        Textarea::make('hook_before_making_current')
                            ->label(__('Before Activating New Release'))
                            ->helperText(__('This bash script will be executed before swapping the symlink to the new release.'))
                            ->autosize(),
                        Textarea::make('hook_after_making_current')
                            ->label(__('After Activating New Release'))
                            ->helperText(__('This bash script will be executed after swapping the symlink to the new release.'))
                            ->autosize(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /* @var Site $record */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        foreach (['hook_before_updating_repository', 'hook_after_updating_repository', 'hook_before_making_current', 'hook_after_making_current'] as $hook) {
            // Replace new lines with new lines that are compatible with bash
            $data[$hook] = str_replace(["\r\n", "\n", "\r"], "\n", $data[$hook] ?? '');
        }

        foreach (['shared_directories', 'shared_files', 'writeable_directories'] as $key) {
            $data[$key] = collect(explode(PHP_EOL, $data[$key] ?? ''))
                ->map(fn ($item) => trim($item))
                ->filter(fn ($item) => $item !== '')
                ->values()
                ->all();
        }

        $record->update($data);

        $this->logActivity(__("Updated deployment settings of site ':address' on server ':server'", ['address' => $record->address, 'server' => $record->server->name]), $record);

        return $record;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('The deployment settings have been saved.');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
        ];
    }
}
