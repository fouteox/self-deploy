@php
    $softwares = $this->getRecord()->installedSoftware()->map(function (\App\Server\Software $software) {
                return [
                    'id' => $software->value,
                    'name' => $software->getDisplayName(),
                    'hasRestartTask' => (bool) $software->restartTaskClass(),
                    'hasUpdateAlternativesTask' => (bool) $software->updateAlternativesTask(),
                ];
            })->sortBy(fn(array $software) => $software['name']);
@endphp

<x-filament-panels::page>
    @foreach ($softwares as $software)
        <x-filament::card>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-black">{{ $software['name'] }}</h2>
                </div>
                <div class="flex items-center space-x-2">
                    @if ($software['hasUpdateAlternativesTask'])
                        <x-modal-action
                            :item="$software['id']"
                            action="{{__('Make CLI default')}}"
                            :$record
                        />
                    @endif

                    @if ($software['hasRestartTask'])
                        <x-modal-action
                            :item="$software['id']"
                            color="danger"
                            action="{{__('Restart')}}"
                            :$record
                        />
                    @endif
                </div>
            </div>
        </x-filament::card>
    @endforeach
</x-filament-panels::page>
