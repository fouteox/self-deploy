<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    @php
        $arrayState = $getState();
        $arrayState = \Illuminate\Support\Arr::wrap($arrayState);
        $statusClasses = [
            'completed' => 'text-green-500',
            'current' => 'text-blue-500 animate-pulse ring-2 ring-blue-300 ring-opacity-50',
            'upcoming' => 'text-gray-400'
        ];
    @endphp
    <li class="flex items-center">
        <div class="{{ $statusClasses[$arrayState['status']] }} mr-2 flex-none rounded-full p-1">
            <div class="h-2 w-2 rounded-full bg-current"></div>
        </div>
        {{ $arrayState['description'] }}
    </li>
</x-dynamic-component>
