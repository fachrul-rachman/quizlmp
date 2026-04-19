<x-layouts.admin title="Kategori Quiz">
    @if (session('success'))
        <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800 dark:border-green-900/50 dark:bg-green-950/30 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-200">
            {{ session('error') }}
        </div>
    @endif

    <div class="mb-4 flex items-center justify-between gap-3">
        <div class="text-lg font-semibold">Kategori Quiz</div>
        <a href="{{ url('/admin/quiz-categories/create') }}" class="rounded-md bg-zinc-900 px-3 py-2 text-sm font-semibold text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
            Tambah Kategori
        </a>
    </div>

    <form method="GET" action="{{ url('/admin/quiz-categories') }}" class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="sm:col-span-2">
            <label class="mb-1 block text-sm font-medium">Search</label>
            <input name="search" value="{{ $search }}" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950" />
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="rounded-md bg-zinc-900 px-3 py-2 text-sm font-semibold text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                Filter
            </button>
            <a href="{{ url('/admin/quiz-categories') }}" class="rounded-md border border-zinc-300 px-3 py-2 text-sm hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800/40">
                Reset
            </a>
        </div>
    </form>

    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
        @if ($categories->isEmpty())
            <div class="px-4 py-4 text-sm text-zinc-600 dark:text-zinc-300">Belum ada kategori quiz.</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-zinc-50 text-zinc-600 dark:bg-zinc-900/40 dark:text-zinc-300">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium">Nama Kategori</th>
                            <th class="px-4 py-2 text-left font-medium">Jumlah Quiz</th>
                            <th class="px-4 py-2 text-left font-medium">Dibuat</th>
                            <th class="px-4 py-2 text-left font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @foreach ($categories as $category)
                            <tr>
                                <td class="px-4 py-3 font-medium">{{ $category->name }}</td>
                                <td class="px-4 py-3">{{ $category->quizzes_count }}</td>
                                <td class="px-4 py-3">{{ optional($category->created_at)->format('d M Y H:i') ?: '-' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ url('/admin/quiz-categories/'.$category->id.'/edit') }}" class="underline underline-offset-2">Edit</a>
                                        @if ($category->quizzes_count === 0)
                                            <button type="button" class="text-red-600 underline underline-offset-2 dark:text-red-400" onclick="document.getElementById('delete-category-{{ $category->id }}').showModal()">
                                                Hapus
                                            </button>
                                        @else
                                            <span class="text-xs text-zinc-500 dark:text-zinc-400">Tidak bisa dihapus</span>
                                        @endif
                                    </div>

                                    @if ($category->quizzes_count === 0)
                                        <dialog id="delete-category-{{ $category->id }}" class="rounded-lg border border-zinc-200 bg-white p-0 shadow-lg dark:border-zinc-800 dark:bg-zinc-950">
                                            <form method="dialog" class="border-b border-zinc-200 p-4 dark:border-zinc-800">
                                                <div class="text-sm font-semibold">Konfirmasi Hapus</div>
                                            </form>
                                            <div class="p-4 text-sm">
                                                Kategori <span class="font-semibold">{{ $category->name }}</span> akan dihapus.
                                            </div>
                                            <div class="flex items-center justify-end gap-2 p-4 pt-0">
                                                <form method="dialog">
                                                    <button class="rounded-md border border-zinc-300 px-3 py-2 text-sm hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800/40">Batal</button>
                                                </form>
                                                <form method="POST" action="{{ url('/admin/quiz-categories/'.$category->id) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white hover:bg-red-500">Hapus</button>
                                                </form>
                                            </div>
                                        </dialog>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-3">
                {{ $categories->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
