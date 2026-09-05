@props([
    'title' => 'Nothing here yet',
    'description' => null,
    'icon' => 'heroicon-o-document-magnifying-glass',
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-6 py-12 text-center']) }} role="status" aria-live="polite">
    <x-dynamic-component :component="$icon" class="mb-4 h-10 w-10 text-gray-400" aria-hidden="true" />
    <h2 class="text-base font-semibold text-gray-950">{{ $title }}</h2>
    @if ($description)
        <p class="mt-2 max-w-md text-sm text-gray-600">{{ $description }}</p>
    @endif
    @if (isset($action) && $action->isNotEmpty())
        <div class="mt-5">{{ $action }}</div>
    @endif
</div>