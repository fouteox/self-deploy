<?php

namespace App\View\Components;

use Illuminate\Database\Eloquent\Model;

// TODO: add a spinner icon
class StatusColumn
{
    /**
     * Get the status based on the model's state.
     *
     * @param  Model  $record The model instance.
     * @return string The status text.
     */
    public static function getStatus(Model $record): string
    {
        $attributes = self::checkAttributes($record);

        return match (true) {
            $attributes['hasInstallationFailedAt'] => __('Installation failed'),
            $attributes['hasUninstallationFailedAt'] => __('Uninstallation failed'),
            $attributes['hasUninstallationRequestedAt'] => __('Uninstalling').'...',
            $attributes['hasInstalledAt'] => __('Installed'),
            default => __('Installing').'...'
        };
    }

    /**
     * Check the record's attributes and return their states.
     *
     * @param  Model  $record The model instance to check.
     * @return array An array containing the states of the attributes.
     */
    protected static function checkAttributes(Model $record): array
    {
        return [
            'hasInstallationFailedAt' => $record->__isset('installation_failed_at'),
            'hasUninstallationFailedAt' => $record->__isset('uninstallation_failed_at'),
            'hasUninstallationRequestedAt' => $record->__isset('uninstallation_requested_at'),
            'hasInstalledAt' => $record->__isset('installed_at'),
        ];
    }
}
