<x-layouts.admin title="Quiz">
    @if (session('success'))
        <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800 dark:border-green-900/50 dark:bg-green-950/30 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex items-center justify-between gap-3 mb-4">
        <div class="text-lg font-semibold">Quiz</div>
        <a href="{{ url('/admin/quizzes/create') }}" class="rounded-md bg-zinc-900 px-3 py-2 text-sm font-semibold text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
            Tambah Quiz
        </a>
    </div>

    <form method="GET" action="{{ url('/admin/quizzes') }}" class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-4">
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium mb-1">Search</label>
            <input name="search" value="{{ $search }}" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950" />
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Status</label>
            <select name="status" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950">
                <option value="all" @selected($status === 'all')>Semua</option>
                <option value="active" @selected($status === 'active')>Aktif</option>
                <option value="inactive" @selected($status === 'inactive')>Nonaktif</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Kategori</label>
            <select name="category_id" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950">
                <option value="all" @selected($categoryId === 'all')>Semua</option>
                <option value="default" @selected($categoryId === 'default')>Folder Utama</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected($categoryId === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="sm:col-span-4 flex gap-2">
            <button type="submit" class="rounded-md bg-zinc-900 px-3 py-2 text-sm font-semibold text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                Filter
            </button>
            <a href="{{ url('/admin/quizzes') }}" class="rounded-md border border-zinc-300 px-3 py-2 text-sm hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800/40">
                Reset
            </a>
        </div>
    </form>

    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
        @if ($quizzes->isEmpty())
            <div class="px-4 py-4 text-sm text-zinc-600 dark:text-zinc-300">Belum ada quiz.</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-zinc-50 text-zinc-600 dark:bg-zinc-900/40 dark:text-zinc-300">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium">Nama Quiz</th>
                            <th class="px-4 py-2 text-left font-medium">Kategori</th>
                            <th class="px-4 py-2 text-left font-medium">Durasi</th>
                            <th class="px-4 py-2 text-left font-medium">Jumlah Soal</th>
                            <th class="px-4 py-2 text-left font-medium">Shuffle Soal</th>
                            <th class="px-4 py-2 text-left font-medium">Shuffle Opsi</th>
                            <th class="px-4 py-2 text-left font-medium">Status</th>
                            <th class="px-4 py-2 text-left font-medium">Dibuat Oleh</th>
                            <th class="px-4 py-2 text-left font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @foreach ($quizzes as $quiz)
                            <tr>
                                <td class="px-4 py-2">{{ $quiz->title }}</td>
                                <td class="px-4 py-2">
                                    <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium bg-sky-100 text-sky-800 dark:bg-sky-950/40 dark:text-sky-200">
                                        {{ $quiz->category?->name ?? 'Folder Utama' }}
                                    </span>
                                </td>
                                <td class="px-4 py-2">{{ $quiz->duration_minutes }} menit</td>
                                <td class="px-4 py-2">{{ (int) ($quiz->active_questions_count ?? 0) }}</td>
                                <td class="px-4 py-2">{{ $quiz->shuffle_questions ? 'Ya' : 'Tidak' }}</td>
                                <td class="px-4 py-2">{{ $quiz->shuffle_options ? 'Ya' : 'Tidak' }}</td>
                                <td class="px-4 py-2">{{ $quiz->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                                <td class="px-4 py-2">{{ $quiz->creator?->name ?? '-' }}</td>
                                <td class="px-4 py-2">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ url('/admin/quizzes/'.$quiz->id) }}" class="underline underline-offset-2">Detail</a>
                                        <a href="{{ url('/admin/quizzes/'.$quiz->id.'/edit') }}" class="underline underline-offset-2">Edit</a>
                                        <button type="button" class="underline underline-offset-2 text-red-600 dark:text-red-400" onclick="document.getElementById('delete-quiz-{{ $quiz->id }}').showModal()">
                                            Hapus
                                        </button>
                                    </div>

                                    <dialog id="delete-quiz-{{ $quiz->id }}" class="rounded-lg border border-zinc-200 bg-white p-0 shadow-lg dark:border-zinc-800 dark:bg-zinc-950">
                                        <form method="dialog" class="p-4 border-b border-zinc-200 dark:border-zinc-800">
                                            <div class="text-sm font-semibold">Konfirmasi Hapus</div>
                                        </form>
                                        <div class="p-4 text-sm">
                                            Quiz akan dihapus dari daftar aktif.
                                        </div>
                                        <div class="p-4 pt-0 flex items-center justify-end gap-2">
                                            <form method="dialog">
                                                <button class="rounded-md border border-zinc-300 px-3 py-2 text-sm hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800/40">Batal</button>
                                            </form>
                                            <form method="POST" action="{{ url('/admin/quizzes/'.$quiz->id) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white hover:bg-red-500">Hapus</button>
                                            </form>
                                        </div>
                                    </dialog>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-3">
                {{ $quizzes->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
