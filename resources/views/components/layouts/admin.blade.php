@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        @livewireStyles
    </head>
    <body class="min-h-screen bg-white text-zinc-900 dark:bg-zinc-900 dark:text-zinc-100">
        @php($user = auth()->user())
        @php($navLinkClass = function (bool $active): string {
            return $active
                ? 'block rounded-md px-3 py-2 text-sm font-semibold bg-zinc-900 text-white dark:bg-white dark:text-zinc-900'
                : 'block rounded-md px-3 py-2 text-sm hover:bg-zinc-200/70 dark:hover:bg-zinc-800/60';
        })
        <div class="min-h-screen lg:flex">
            <aside class="hidden lg:block lg:w-64 lg:flex-none border-e border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-950">
                <div class="px-4 py-4 border-b border-zinc-200 dark:border-zinc-800">
                    <a href="{{ url('/admin/dashboard') }}" class="inline-flex items-center gap-2">
                        <x-app-logo-icon class="h-7 w-7" />
                        <span class="font-semibold">{{ config('app.name') }}</span>
                    </a>
                </div>

                <nav class="p-3 space-y-1">
                    <a href="{{ url('/admin/dashboard') }}" class="{{ $navLinkClass(request()->routeIs('admin.dashboard')) }}">Dashboard</a>
                    <a href="{{ url('/admin/quizzes') }}" class="{{ $navLinkClass(request()->routeIs('admin.quizzes.*')) }}">Quiz</a>
                    <a href="{{ url('/admin/quiz-categories') }}" class="{{ $navLinkClass(request()->routeIs('admin.quiz-categories.*')) }}">Kategori Quiz</a>
                    <a href="{{ url('/admin/generate-link') }}" class="{{ $navLinkClass(request()->routeIs('admin.links.*')) }}">Generate Link</a>
                    <a href="{{ url('/admin/results') }}" class="{{ $navLinkClass(request()->routeIs('admin.results.*')) }}">Hasil</a>
                    @if(($user?->role ?? null) === 'super_admin')
                        <a href="{{ url('/admin/users') }}" class="{{ $navLinkClass(request()->routeIs('admin.users.*')) }}">Admin Users</a>
                    @endif
                    <form method="POST" action="{{ url('/logout') }}" class="pt-2">
                        @csrf
                        <button type="submit" class="w-full text-left rounded-md px-3 py-2 text-sm hover:bg-zinc-200/70 dark:hover:bg-zinc-800/60">Logout</button>
                    </form>
                </nav>
            </aside>

            <div class="flex-1 min-w-0">
                <header class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-950">
                    <div class="px-4 py-3 flex items-center gap-3">
                        <details class="lg:hidden">
                            <summary class="cursor-pointer select-none rounded-md px-3 py-2 text-sm hover:bg-zinc-200/70 dark:hover:bg-zinc-800/60">
                                Menu
                            </summary>
                            <div class="mt-2 rounded-md border border-zinc-200 bg-white p-2 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                                <a href="{{ url('/admin/dashboard') }}" class="{{ $navLinkClass(request()->routeIs('admin.dashboard')) }}">Dashboard</a>
                                <a href="{{ url('/admin/quizzes') }}" class="{{ $navLinkClass(request()->routeIs('admin.quizzes.*')) }}">Quiz</a>
                                <a href="{{ url('/admin/quiz-categories') }}" class="{{ $navLinkClass(request()->routeIs('admin.quiz-categories.*')) }}">Kategori Quiz</a>
                                <a href="{{ url('/admin/generate-link') }}" class="{{ $navLinkClass(request()->routeIs('admin.links.*')) }}">Generate Link</a>
                                <a href="{{ url('/admin/results') }}" class="{{ $navLinkClass(request()->routeIs('admin.results.*')) }}">Hasil</a>
                                @if(($user?->role ?? null) === 'super_admin')
                                    <a href="{{ url('/admin/users') }}" class="{{ $navLinkClass(request()->routeIs('admin.users.*')) }}">Admin Users</a>
                                @endif
                                <form method="POST" action="{{ url('/logout') }}" class="pt-1">
                                    @csrf
                                    <button type="submit" class="w-full text-left rounded-md px-3 py-2 text-sm hover:bg-zinc-200/70 dark:hover:bg-zinc-800/60">Logout</button>
                                </form>
                            </div>
                        </details>

                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-semibold truncate">{{ $title ?? '' }}</div>
                        </div>

                        <div class="flex items-center gap-3 text-sm">
                            <div class="text-right leading-tight">
                                <div class="font-medium">{{ $user?->name ?? 'Guest' }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $user?->role ?? '-' }}</div>
                            </div>
                        </div>
                    </div>
                </header>

                <main class="p-4">
                    {{ $slot }}
                </main>
            </div>
        </div>

        @livewireScripts
    </body>
</html>
