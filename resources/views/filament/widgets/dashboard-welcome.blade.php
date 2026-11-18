<x-filament-widgets::widget>
    <section class="relative isolate overflow-hidden rounded-2xl bg-stockflow-ink px-6 py-7 text-white shadow-sm sm:px-8">
        <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex max-w-2xl flex-col gap-3">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stockflow-mint">
                    StockFlow <span aria-hidden="true">/</span> Inventory workspace
                </p>
                <h2 class="text-2xl font-semibold tracking-tight sm:text-3xl">
                    Welcome back, {{ auth()->user()?->name ?? 'team' }}
                </h2>
                <p class="text-sm leading-6 text-slate-200 sm:text-base">
                    Your inventory, movement history, and stock health in one place.
                </p>
            </div>
            <div class="flex items-center gap-3 rounded-xl border border-white/15 bg-white/5 px-4 py-3">
                <img class="h-10 w-10" src="{{ asset('images/stockflow-icon.svg') }}" alt="">
                <div class="flex flex-col gap-0.5">
                    <span class="text-sm font-semibold">StockFlow</span>
                    <span class="text-xs text-slate-300">Inventory management</span>
                </div>
            </div>
        </div>
    </section>
</x-filament-widgets::widget>
