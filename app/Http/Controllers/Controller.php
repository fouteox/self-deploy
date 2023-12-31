<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Log an activity.
     */
    public function logActivity(string $description, ?Model $subject = null): ActivityLog
    {
        return ActivityLog::create([
            'team_id' => $this->team()->id,
            'user_id' => $this->user()->id,
            'subject_id' => $subject?->getKey(),
            'subject_type' => $subject?->getMorphClass(),
            'description' => $description,
        ]);
    }

    /**
     * Get the current team of the authenticated user.
     */
    protected function team(): Team
    {
        return $this->user()->currentTeam;
    }

    /**
     * Get the authenticated user.
     */
    protected function user(): User
    {
        return auth()->user();
    }
}
