@php
    $software = $getRecord();
@endphp

<div>
    @if ($software->hasUpdateAlternativesTask)
        <x-modal-action
            :item="$software['id']"
            action="{{__('Make CLI default')}}"
            type="makeDefaultCli"
            :$record
        />
    @endif

    @if ($software->hasRestartTask)
        <x-modal-action
            :item="$software['id']"
            type="restart"
            color="danger"
            action="{{__('Restart')}}"
            :$record
        />
    @endif
</div>
