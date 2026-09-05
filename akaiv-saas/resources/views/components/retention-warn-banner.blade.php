@props([
    'date',
    'days' => null,
])

<div {{ $attributes->merge(['class' => 'flex items-start gap-3 border-l-4 border-warning-500 bg-warning-50 px-4 py-3 text-sm text-warning-900']) }} role="status" aria-live="polite">
    <x-heroicon-o-clock class="mt-0.5 h-5 w-5 shrink-0" aria-hidden="true" />
    <p>
        <span class="font-semibold">Retention review required.</span>
        This document is scheduled for review on {{ $date }}@if ($days !== null) ({{ $days }} days remaining)@endif.
    </p>
</div>