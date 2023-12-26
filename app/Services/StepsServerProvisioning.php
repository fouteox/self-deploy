<?php

namespace App\Services;

use App\Infrastructure\Entities\ServerStatus;
use App\Models\Server;
use App\Server\ProvisionStep;
use App\Server\SoftwareEnum;

class StepsServerProvisioning
{
    public static function countSteps(Server $server): string
    {
        $isNew = $server->status === ServerStatus::New;
        $isStarting = $server->status === ServerStatus::Starting;

        $completedSteps = $server->completed_provision_steps->toArray();
        $installedSoftware = $server->installed_software->toArray();

        $totalSteps = 2 + count(ProvisionStep::forFreshServer()) + count(SoftwareEnum::defaultStack());
        $currentStep = 1;

        $statusForServerCreation = $isNew ? 'current' : 'completed';
        if ($statusForServerCreation === 'completed') {
            $currentStep++;
        }

        $statusForServerStarting = $isStarting ? 'current' : ($isNew ? 'upcoming' : 'completed');
        if ($statusForServerStarting === 'completed') {
            $currentStep++;
        }

        foreach (ProvisionStep::forFreshServer() as $step) {
            if (! in_array($step->value, $completedSteps)) {
                break;
            }
            $currentStep++;
        }

        foreach (SoftwareEnum::defaultStack() as $software) {
            if (! in_array($software->value, $installedSoftware)) {
                break;
            }
            $currentStep++;
        }

        return "Step $currentStep/$totalSteps";
    }

    public static function allSteps(Server $server): array
    {
        $manualSteps = [
            [
                'status' => $server->status === ServerStatus::New ? 'current' : 'completed',
                'description' => __('Create the server at the provider'),
            ],
            [
                'status' => $server->status === ServerStatus::Starting ? 'current' : ($server->status === ServerStatus::New ? 'upcoming' : 'completed'),
                'description' => __('Wait for the server to start up'),
            ],
        ];

        $allStepsCompleted = [
            ...$server->completed_provision_steps->toArray(),
            ...$server->installed_software->toArray(),
        ];

        $allSteps = [
            ...$manualSteps,
            ...ProvisionStep::forFreshServer(),
            ...SoftwareEnum::defaultStack(),
        ];

        $lastStepWasCompleted = $server->status === ServerStatus::New;

        return array_map(function ($step) use ($allStepsCompleted, &$lastStepWasCompleted) {
            $completed = is_array($step)
                ? $step['status'] === 'completed'
                : in_array($step->value, $allStepsCompleted);
            $current = ! $completed && $lastStepWasCompleted;
            $lastStepWasCompleted = $completed;

            $status = $current ? 'current' : ($completed ? 'completed' : 'upcoming');

            $description = is_array($step)
                ? $step['description']
                : (get_class($step) === SoftwareEnum::class
                    ? __('Install :software', ['software' => $step->getDisplayName()])
                    : $step->getDescription());

            return [
                'status' => $status,
                'description' => $description,
            ];
        }, $allSteps);
    }
}
