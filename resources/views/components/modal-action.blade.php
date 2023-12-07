<x-filament::modal width="3xl" :id="$item">
    <x-slot name="trigger">
        <x-filament::button :color="$color ?? null">
            {{ $action }}
        </x-filament::button>
    </x-slot>

    <x-slot name="heading">
        Are you sure you want to continue?
    </x-slot>
    {{-- TODO: differencier default de restart--}}
    <x-slot name="footerActions">
        <x-filament::button
            :color="$color ?? null"
            wire:click="{{$type}}('{{ $item }}')"
        >
            {{ $action }}
        </x-filament::button>

        <x-filament::button color="gray">
            Cancel
        </x-filament::button>
    </x-slot>
</x-filament::modal>
