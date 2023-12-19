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
                    wire:click="openModal('{{encrypt(gzencode($file->path, 9))}}')"
                >
                    View
                </x-filament::button>
            </div>
        </x-filament::card>
    @endforeach
    <x-filament::modal width="2xl" id="modaleEditFile">
        <x-slot name="heading">
            Edit File
        </x-slot>

        <form wire:submit="create">
            {{ $this->form }}
            <x-filament::button type="submit">
                Submit
            </x-filament::button>
            <x-filament::button
                color="gray"
                wire:click="resetData"
            >
                Cancel
            </x-filament::button>
        </form>
    </x-filament::modal>
</x-filament-panels::page>
