@props([
    'label' => 'Exhibit A',
    'caseNumber' => null,
])

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 rounded-md border border-brand-200 bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-900']) }}>
    <span aria-hidden="true" class="h-2 w-2 rounded-full bg-brand-600"></span>
    <span>{{ $label }}</span>
    @if ($caseNumber)
        <span class="font-normal text-brand-700">{{ $caseNumber }}</span>
    @endif
</span>