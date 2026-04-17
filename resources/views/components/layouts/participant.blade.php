<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        @livewireStyles
    </head>
    <body class="min-h-screen bg-white text-zinc-900 dark:bg-zinc-900 dark:text-zinc-100">
        <main class="min-h-screen flex items-center justify-center px-4 py-10">
            <div class="w-full max-w-3xl">
                {{ $slot }}
            </div>
        </main>

        @livewireScripts
    </body>
</html>

