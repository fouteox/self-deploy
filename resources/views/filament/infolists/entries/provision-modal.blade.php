<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    <x-filament::modal width="3xl">
        <x-slot name="trigger">
            <x-filament::button>
                View Provisioning Script
            </x-filament::button>
        </x-slot>

        <x-slot name="heading">
            Provision Command
        </x-slot>

        <x-slot name="description">
            Run this script as root on your server to start the provisioning process:
        </x-slot>

        <div
            x-on:click="
            window.navigator.clipboard.writeText($refs.textareaContent.value);
            $tooltip('Copied to clipboard', { timeout: 1500 })
        "
        >
            <x-filament::input.wrapper>
                <x-filament::input
                    type="text"
                    value="{{ $getRecord()->provisionCommand() }}"
                    x-ref="textareaContent"
                    readonly
                />

                <x-slot name="suffix">
                    <x-filament::icon
                        icon="heroicon-o-clipboard-document"
                        class="h-5 w-5 text-gray-500 dark:text-gray-400 cursor-pointer"
                    />
                </x-slot>
            </x-filament::input.wrapper>
        </div>

    </x-filament::modal>
</x-dynamic-component>
