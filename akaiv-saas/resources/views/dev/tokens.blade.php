<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>AKAIV Design Tokens</title>
    @vite(['resources/css/filament/admin/theme.css', 'resources/js/filament/admin/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-950">
    <main class="mx-auto max-w-6xl px-6 py-10">
        <a class="akaiv-skip-link" href="#token-content">Skip to content</a>
        <header class="mb-10 flex flex-col items-start justify-between gap-6 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-brand-700">AKAIV Archives</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight">Design token preview</h1>
                <p class="mt-2 max-w-2xl text-slate-600">The shared visual language for secure legal document work.</p>
            </div>
            <x-legal-exhibit-badge label="Token baseline" />
        </header>
        <div id="token-content" class="space-y-8">
            <section aria-labelledby="colors-heading">
                <h2 id="colors-heading" class="mb-4 text-lg font-semibold">Brand colors</h2>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6">
                    @foreach ([50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950] as $shade)
                        <div class="overflow-hidden rounded-md border border-slate-200 bg-white shadow-card">
                            <div class="h-16 bg-brand-{{ $shade }}"></div>
                            <div class="p-3 text-sm font-medium">brand-{{ $shade }}</div>
                        </div>
                    @endforeach
                </div>
            </section>
            <section aria-labelledby="components-heading">
                <h2 id="components-heading" class="mb-4 text-lg font-semibold">Core components</h2>
                <div class="space-y-4">
                    <x-retention-warn-banner date="30 September 2026" days="25" />
                    <x-empty-state title="No documents found" description="Upload a document or adjust your filters to begin." />
                    <x-undo-toast />
                </div>
            </section>
        </div>
    </main>
</body>
</html>