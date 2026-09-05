@props([
    'message' => 'Document moved to trash.',
    'undoUrl' => null,
])

<div {{ $attributes->merge(['class' => 'flex items-center justify-between gap-4 rounded-md bg-gray-950 px-4 py-3 text-sm text-white shadow-card']) }} role="status" aria-live="assertive">
    <span>{{ $message }}</span>
    @if ($undoUrl)
        <a class="min-h-11 shrink-0 rounded-md px-3 py-2 font-semibold text-sky-300 hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-300" href="{{ $undoUrl }}">Undo</a>
    @endif
</div>