@php
    $files = $this->getRecord()->files()->editableFiles();
@endphp

<x-filament-panels::page>
    @foreach($files as $file)
        <x-filament::card>
            <div class="flex items-center gap-4">
                <h2 class="flex-none w-1/4 font-semibold text-sm">{{ $file->name }}</h2>
                <p class="flex-grow text-sm text-left">{{ $file->description }}</p>
                <x-filament::button
                    color="danger"
                    wire:click="test('{{encrypt(gzencode($file->path, 9))}}')"
                >
                    Load
                </x-filament::button>
                <x-filament::button class="flex-none">
                    View
                </x-filament::button>
            </div>
        </x-filament::card>
    @endforeach
    <x-filament::modal width="3xl" id="modaleEditFile">
        <x-slot name="heading">
            {{ $this->fileContents }}
        </x-slot>
        <x-slot name="footerActions">
            <x-filament::button
                color="gray"
                wire:click="$dispatch('close-modal', { id: 'modaleEditFile'})"
            >
                Cancel
            </x-filament::button>
        </x-slot>
    </x-filament::modal>
</x-filament-panels::page>
