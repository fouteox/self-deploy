<x-filament::modal width="3xl">
    <x-slot name="trigger">
        <x-filament::button class="mt-4 inline-flex">
            View Provisioning Script
        </x-filament::button>
    </x-slot>

    <x-slot name="heading">
        Provision Command
    </x-slot>

    <x-slot name="description">
        Run this script as root on your server to start the provisioning process:
    </x-slot>

    <x-filament::input.wrapper disabled="true">
        <x-filament::input
            type="text"
            value="{{ $record->provisionCommand() }}"
            x-ref="textareaContent"
        />

        <x-slot name="suffix">
            <x-filament::icon
                x-on:click="
                    window.navigator.clipboard.writeText($refs.textareaContent.value);
                    $tooltip('Copied to clipboard', { timeout: 1500 })
                "
                icon="heroicon-o-clipboard-document"
                class="h-5 w-5 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 cursor-pointer"
            />
        </x-slot>
    </x-filament::input.wrapper>
</x-filament::modal>
