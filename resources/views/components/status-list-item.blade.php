@props(['statusClasses', 'status', 'description'])
<li class="flex items-center">
    <div class="{{ $statusClasses[$status] }} mr-2 flex-none rounded-full p-1">
        <div class="h-2 w-2 rounded-full bg-current"></div>
    </div>
    {{ $description }}
</li>
