<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="StockFlow inventory and stock management">

        <title>StockFlow | Inventory Management</title>

        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">
        <div class="mx-auto flex min-h-screen max-w-7xl flex-col px-6 lg:px-8">
            <header class="flex items-center justify-between py-7">
                <a href="{{ route('home') }}" aria-label="StockFlow home">
                    <picture>
                        <source srcset="{{ asset('images/stockflow-logo-dark.svg') }}" media="(prefers-color-scheme: dark)">
                        <img class="h-12 w-auto" src="{{ asset('images/stockflow-logo.svg') }}" alt="StockFlow">
                    </picture>
                </a>
                <a
                    class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-teal-700 hover:text-teal-800 dark:border-slate-700 dark:text-slate-200 dark:hover:border-teal-400 dark:hover:text-teal-300"
                    href="{{ route('filament.admin.auth.login') }}"
                >
                    Admin sign in
                </a>
            </header>

            <main class="grid flex-1 items-center gap-12 py-12 lg:grid-cols-2 lg:gap-20">
                <section class="flex max-w-2xl flex-col items-start gap-5">
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-teal-700 dark:text-teal-300">
                        Inventory management
                    </p>
                    <h1 class="text-5xl font-semibold tracking-tight text-slate-950 sm:text-6xl dark:text-white">
                        Keep every item in flow.
                    </h1>
                    <p class="max-w-xl text-lg leading-8 text-slate-600 dark:text-slate-300">
                        StockFlow brings your product catalog into one clear, dependable workspace.
                    </p>
                    <a
                        class="inline-flex items-center gap-3 rounded-lg bg-teal-700 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-700 dark:bg-teal-600 dark:hover:bg-teal-500"
                        href="{{ route('filament.admin.auth.login') }}"
                    >
                        Sign in to StockFlow
                        <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 20 20" fill="none">
                            <path d="M4.5 10h11m0 0-4.5-4.5M15.5 10 11 14.5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"/>
                        </svg>
                    </a>
                </section>

                <section class="relative overflow-hidden rounded-3xl bg-[#102a43] p-8 text-white shadow-xl shadow-slate-900/10 sm:p-12">
                    <div class="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-teal-400/10 blur-3xl"></div>
                    <div class="relative flex flex-col gap-5">
                        <img class="h-16 w-16" src="{{ asset('images/stockflow-icon.svg') }}" alt="">
                        <p class="text-sm font-medium uppercase tracking-[0.16em] text-teal-300">
                            Clear by design
                        </p>
                        <h2 class="text-3xl font-semibold tracking-tight">
                            The right stock, at a glance.
                        </h2>
                        <p class="max-w-md leading-7 text-slate-300">
                            A focused catalog for organizing products and categories across your warehouse.
                        </p>
                        <div class="flex items-center gap-3 border-t border-white/15 pt-5 text-sm text-slate-300">
                            <span class="h-2 w-2 rounded-full bg-teal-300"></span>
                            Built for a single-warehouse team
                        </div>
                    </div>
                </section>
            </main>

            <footer class="border-t border-slate-200 py-5 text-sm text-slate-500 dark:border-slate-800 dark:text-slate-400">
                StockFlow <span aria-hidden="true">·</span> Inventory management
            </footer>
        </div>
    </body>
</html>
