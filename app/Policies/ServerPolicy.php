<?php

namespace App\Policies;

use App\Models\Server;
use App\Models\User;

class ServerPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Server $server): bool
    {
        return $user->currentTeam->id === $server->team_id && ! $server->uninstallation_requested_at;
    }

    /**
     * Determine whether the user can manage the model.
     */
    public function manage(User $user, Server $server): bool
    {
        return $user->currentTeam->id === $server->team_id && ! $server->uninstallation_requested_at;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Server $server): bool
    {
        return $user->currentTeam->id === $server->team_id && ! $server->uninstallation_requested_at && $server->provisioned_at;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Server $server): bool
    {
        return $user->currentTeam->id === $server->team_id && ! $server->uninstallation_requested_at;
    }

    public function deleteAny(): bool
    {
        return false;
    }
}
