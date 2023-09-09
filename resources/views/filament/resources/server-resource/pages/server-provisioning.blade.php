@php
    $isNew = $server->status === \App\Infrastructure\Entities\ServerStatus::New;
    $isStarting = $server->status === \App\Infrastructure\Entities\ServerStatus::Starting;
    $isProvisioning = $server->status === \App\Infrastructure\Entities\ServerStatus::Provisioning;

    $statusClasses = [
        'completed' => 'text-green-500',
        'current' => 'text-blue-500 animate-pulse ring-2 ring-blue-300 ring-opacity-50',
        'upcoming' => 'text-gray-400'
    ];

    $lastStepWasCompleted = $isProvisioning;
    $completedSteps = $server->completed_provision_steps->toArray();
    $installedSoftware = $server->installed_software->toArray();



    $totalSteps = 2 + count(\App\Server\ProvisionStep::forFreshServer()) + count(\App\Server\Software::defaultStack());
    $currentStep = 0;

    if (!$isNew) {
        $currentStep++;
    }

    if (!$isStarting && $isNew) {
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

                @if ($server->status === \App\Infrastructure\Entities\ServerStatus::Provisioning)
                    {{ __('The server is currently being provisioned.') }}
                @elseif ($server->status === \App\Infrastructure\Entities\ServerStatus::Starting)
                    {{ __('The server is created at the provider and is currently starting up.') }}
                @else
                    {{ __('The server is currently being created at the provider.') }}
                @endif

                {{ __('This page will automatically refresh on updates.') }}

                @if ($server->provider === \App\Provider::CustomServer && ! $isProvisioning)
                    <p class="mt-6">{{ __('Need to see the provisioning script again?') }}</p>

                    <x-provision-modal :$server/>
                @endif
            </x-filament::section>
        </div>

        <div class="order-2 md:order-1 md:col-span-2">
            <x-filament::section>
                <x-slot name="heading">
                    Step {{ $currentStep }}/{{ $totalSteps }}
                </x-slot>

                <ul class="grid gap-4">
                    @php
                        $status = $isNew ? 'current' : 'completed';
                    @endphp
                    <x-status-list-item :statusClasses="$statusClasses" :status="$status"
                                        :description="__('Create the server at the provider')"/>

                    @php
                        $status = $isStarting ? 'current' : ($isNew ? 'upcoming' : 'completed');
                    @endphp
                    <x-status-list-item :statusClasses="$statusClasses" :status="$status"
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
