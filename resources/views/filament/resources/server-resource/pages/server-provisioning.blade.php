@php
    $isNew = $record->status === \App\Infrastructure\Entities\ServerStatus::New;
    $isStarting = $record->status === \App\Infrastructure\Entities\ServerStatus::Starting;
    $isProvisioning = $record->status === \App\Infrastructure\Entities\ServerStatus::Provisioning;

    $statusClasses = [
        'completed' => 'text-green-500',
        'current' => 'text-blue-500 animate-pulse ring-2 ring-blue-300 ring-opacity-50',
        'upcoming' => 'text-gray-400'
    ];

    $lastStepWasCompleted = $isProvisioning;
    $completedSteps = $record->completed_provision_steps->toArray();
    $installedSoftware = $record->installed_software->toArray();

    $totalSteps = 2 + count(\App\Server\ProvisionStep::forFreshServer()) + count(\App\Server\Software::defaultStack());
    $currentStep = 1;

    $statusForServerCreation = $isNew ? 'current' : 'completed';
    if ($statusForServerCreation === 'completed') {
        $currentStep++;
    }

    $statusForServerStarting = $isStarting ? 'current' : ($isNew ? 'upcoming' : 'completed');
    if ($statusForServerStarting === 'completed') {
        $currentStep++;
    }

    foreach (\App\Server\ProvisionStep::forFreshServer() as $step) {
        if (!in_array($step->value, $completedSteps)) {
            break;
        }
        $currentStep++;
    }

    foreach (\App\Server\Software::defaultStack() as $software) {
        if (!in_array($software->value, $installedSoftware)) {
            break;
        }
        $currentStep++;
    }
@endphp

<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="order-1 md:order-2">
            <x-filament::section>
                <x-slot name="heading">
                    Server Provisioning
                </x-slot>

                @if ($record->status === \App\Infrastructure\Entities\ServerStatus::Provisioning)
                    {{ __('The server is currently being provisioned.') }}
                @elseif ($record->status === \App\Infrastructure\Entities\ServerStatus::Starting)
                    {{ __('The server is created at the provider and is currently starting up.') }}
                @else
                    {{ __('The server is currently being created at the provider.') }}
                @endif

                {{ __('This page will automatically refresh on updates.') }}

                @if ($record->provider === \App\Provider::CustomServer && ! $isProvisioning)
                    <p class="mt-6">{{ __('Need to see the provisioning script again?') }}</p>

                    <x-provision-modal :$record/>
                @endif
            </x-filament::section>
        </div>

        <div class="order-2 md:order-1 md:col-span-2">
            <x-filament::section>
                <x-slot name="heading">
                    Step {{ $currentStep }}/{{ $totalSteps }}
                </x-slot>

                <ul class="grid gap-2.5">
                    <x-status-list-item :statusClasses="$statusClasses" :status="$statusForServerCreation"
                                        :description="__('Create the server at the provider')"/>

                    <x-status-list-item :statusClasses="$statusClasses" :status="$statusForServerStarting"
                                        :description="__('Wait for the server to start up')"/>

                    @foreach (\App\Server\ProvisionStep::forFreshServer() as $step)
                        @php
                            $completed = in_array($step->value, $completedSteps);
                            $current = ! $completed && $lastStepWasCompleted;
                            $lastStepWasCompleted = $completed;

                            $status = $current ? 'current' : ($completed ? 'completed' : 'upcoming');
                        @endphp

                        <x-status-list-item :statusClasses="$statusClasses" :status="$status"
                                            :description="$step->getDescription()"/>
                    @endforeach

                    @foreach (\App\Server\Software::defaultStack() as $software)
                        @php
                            $completed = in_array($software->value, $installedSoftware);
                            $current = ! $completed && $lastStepWasCompleted;
                            $lastStepWasCompleted = $completed;

                            $status = $current ? 'current' : ($completed ? 'completed' : 'upcoming');
                        @endphp

                        <x-status-list-item :statusClasses="$statusClasses" :status="$status"
                                            :description="__('Install :software', ['software' => $software->getDisplayName()])"/>
                    @endforeach
                </ul>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
