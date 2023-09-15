<?php

namespace App\Traits;

use App\Models\ActivityLog;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

trait HandlesUserContext
{
    public function logActivity(string $description, Model $subject = null): ActivityLog
    {
        return ActivityLog::create([
            'team_id' => $this->team()->id,
            'user_id' => $this->user()->id,
            'subject_id' => $subject?->getKey(),
            'subject_type' => $subject?->getMorphClass(),
            'description' => $description,
        ]);
    }

    protected function team(): Team
    {
        return $this->user()->currentTeam;
    }

    protected function user(): User
    {
        return auth()->user();
    }
}
