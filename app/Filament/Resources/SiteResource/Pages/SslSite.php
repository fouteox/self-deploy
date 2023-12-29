<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Filament\Resources\SiteResource;
use App\Jobs\InstallCertificate;
use App\Jobs\UpdateSiteTlsSetting;
use App\Models\Site;
use App\Models\TlsSetting;
use App\Traits\BreadcrumbTrait;
use App\Traits\HandlesUserContext;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

/* @method Site getRecord() */
class SslSite extends EditRecord
{
    use BreadcrumbTrait, HandlesUserContext;

    protected static string $resource = SiteResource::class;

    protected static ?string $title = 'SSL';

    protected static ?string $navigationIcon = 'heroicon-s-lock-closed';

    public function getListeners(): array
    {
        return [
            'echo-private:teams.'.$this->team()->id.',SiteUpdated' => 'refreshData',
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Radio::make('tls_setting')
                            ->options(TlsSetting::class)
                            ->required()
                            ->enum(TlsSetting::class)
                            ->hiddenLabel()
                            ->live(),
                        TextInput::make('private_key')
                            ->visible(fn (Get $get): bool => $get('tls_setting') === TlsSetting::Custom->value)
                            ->required(fn (Get $get, Site $record): bool => $get('tls_setting') === TlsSetting::Custom->value && ! $record->activeCertificate),
                        TextInput::make('certificate')
                            ->visible(fn (Get $get): bool => $get('tls_setting') === TlsSetting::Custom->value)
                            ->required(fn (Get $get, Site $record): bool => $get('tls_setting') === TlsSetting::Custom->value && ! $record->activeCertificate)
                            ->helperText(fn (Site $record): string => $record->activeCertificate ? __('Only fill the fields if you want to replace the current certificate.') : ''),
                        Placeholder::make('current_certificat')
                            ->content(fn (Site $record): string => __('You may find the current certificate on the server at: ').$record->activeCertificate->siteDirectory())
                            ->visible(fn (Site $record) => $record->activeCertificate),
                    ])
                    ->hidden(fn (Site $record) => $record->pending_tls_update_since),
                Section::make()
                    ->schema([
                        Placeholder::make('tls_pending')
                            ->hiddenLabel()
                            ->content(__('Your SSL settings are being updated. This may take a few minutes.')),
                    ])
                    ->visible(fn (Site $record) => $record->pending_tls_update_since),
            ]);
    }

    public function refreshData(): void
    {
        $this->refreshFormData([
            'tls_setting',
        ]);
    }

    /* @var Site $record */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $newCertificate = null;

        if (isset($data['private_key'])) {
            $newCertificate = $record->certificates()->create([
                'private_key' => $data['private_key'],
                'certificate' => $data['certificate'],
            ]);
        }

        $newTlsSetting = TlsSetting::from($data['tls_setting']);

        $record->pending_tls_update_since = now();

        if ($newCertificate) {
            dispatch(new InstallCertificate($newCertificate, $this->user()));

            $this->sendNotification(__('The certificate will be uploaded to the server and installed. This may take a few minutes.'));
        } elseif ($record->tls_setting !== $newTlsSetting) {
            dispatch(new UpdateSiteTlsSetting($record, $newTlsSetting, $newCertificate, $this->user()));

            $this->sendNotification(__('The site SSL settings will be updated. It might take a few minutes before the changes are applied.'));
        } else {
            $this->sendNotification(__('No changes were made.'));

            $record->pending_tls_update_since = null;
        }

        $record->saveQuietly();

        if ($record->pending_tls_update_since) {
            $this->logActivity(__("Updated SSL settings of site ':address' on server ':server'", ['address' => $record->address, 'server' => $record->server->name]), $record);
        }

        return $record;
    }

    protected function beforeFill(): void
    {
        if ($this->getRecord()->pending_tls_update_since?->diffInMinutes() > 3) {
            // Clear the pending TLS update after 3 minutes.
            $this->getRecord()->forceFill(['pending_tls_update_since' => null])->saveQuietly();
        }
    }

    protected function getSavedNotification(): ?Notification
    {
        return null;
    }

    protected function getRedirectUrl(): string
    {
        return SiteResource::getUrl('ssl', ['record' => $this->getRecord()]);
    }

    protected function getFormActions(): array
    {
        return $this->getRecord()->pending_tls_update_since ? [] : [
            $this->getSaveFormAction(),
        ];
    }
}
