<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white text-zinc-900 dark:bg-zinc-900 dark:text-zinc-100">
        <main class="min-h-screen flex items-center justify-center px-4 py-10">
            <div class="w-full max-w-md">
                <div class="flex items-center justify-center mb-6">
                    <a href="{{ url('/admin/login') }}" class="inline-flex items-center gap-2">
                        <x-app-logo-icon class="h-8 w-8" />
                        <span class="font-semibold text-lg">{{ config('app.name') }}</span>
                    </a>
                </div>

                <div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                    {{ $slot }}
                </div>
            </div>
        </main>
    </body>
</html>

