<?php

namespace App\View\Components;

use Illuminate\Database\Eloquent\Model;

class StatusColumn
{
    public static function getStatus(Model $record, bool $installable = false): string
    {
        // TODO: Add icon
        $text = match (true) {
            $record->installation_failed_at !== null => __('Installation failed'),
            $record->uninstallation_failed_at !== null => __('Uninstallation failed'),
            $record->uninstallation_requested_at !== null => __('Uninstalling').'...',
            $record->installed_at !== null => __('Installed'),
            default => __('Installing').'...'
        };

        return $text;
    }

    public static function isSpinning(Model $record): bool
    {
        return (! $record->installation_failed_at &&
            ! $record->uninstallation_failed_at) &&
            (! $record->installed_at || $record->uninstallation_requested_at);
    }
}
