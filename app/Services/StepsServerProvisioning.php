<?php

namespace App\Services;

use App\Infrastructure\Entities\ServerStatus;
use App\Models\Server;
use App\Server\ProvisionStep;
use App\Server\SoftwareEnum;

class StepsServerProvisioning
{
    public static function allSteps(Server $server): array
    {
        $allStepsCompleted = [
            ...$server->completed_provision_steps->toArray(),
            ...$server->installed_software->toArray(),
        ];

        $allSteps = [
            ...ProvisionStep::forFreshServer(),
            ...SoftwareEnum::defaultStack(),
        ];

        $lastStepWasCompleted = $server->status === ServerStatus::Provisioning;

        return array_map(function ($step) use ($allStepsCompleted, &$lastStepWasCompleted) {
            $completed = in_array($step->value, $allStepsCompleted);
            $current = ! $completed && $lastStepWasCompleted;
            $lastStepWasCompleted = $completed;

            $status = $current ? 'current' : ($completed ? 'completed' : 'upcoming');

            $description = get_class($step) === ProvisionStep::class
                ? $step->getDescription()
                : __('Install :software', ['software' => $step->getDisplayName()]);

            return [
                'status' => $status,
                'description' => $description,
            ];
        }, $allSteps);
    }
}
