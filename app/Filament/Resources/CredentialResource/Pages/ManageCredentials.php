<?php

namespace App\Filament\Resources\CredentialResource\Pages;

use App\Filament\Resources\CredentialResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ManageRecords;

class ManageCredentials extends ManageRecords
{
    protected static string $resource = CredentialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->modalDescription(__('Credentials belong to your profile and are personal. Team members can not see or use your credentials.'))
                ->extraModalFooterActions(fn (Action $action): array => [
                    $action->makeModalSubmitAction('connect_github', arguments: ['connect_github' => true])
                        ->color('primary'),
                ])
                ->action(fn (array $data, array $arguments) => $this->redirect(route('github.redirect')))
                ->modalSubmitAction(false)
                ->createAnother(false),
        ];
    }
}
