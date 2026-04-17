<x-layouts.admin title="Detail Quiz">
    @if (session('success'))
        <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800 dark:border-green-900/50 dark:bg-green-950/30 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex items-center justify-between gap-3 mb-4">
        <div class="text-lg font-semibold">Detail Quiz</div>
        <div class="flex items-center gap-2">
            <a href="{{ url('/admin/quizzes/'.$quiz->id.'/edit') }}" class="rounded-md border border-zinc-300 px-3 py-2 text-sm hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800/40">
                Edit
            </a>
            <a href="{{ url('/admin/quizzes') }}" class="rounded-md border border-zinc-300 px-3 py-2 text-sm hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800/40">
                Kembali
            </a>
        </div>
    </div>

    <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div>
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Nama Quiz</div>
                <div class="mt-1 font-semibold">{{ $quiz->title }}</div>
            </div>
            <div>
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Durasi</div>
                <div class="mt-1 font-semibold">{{ $quiz->duration_minutes }} menit</div>
            </div>
            <div>
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Jumlah Soal</div>
                <div class="mt-1 font-semibold">{{ $quiz->questions->count() }}</div>
            </div>
            <div>
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Shuffle Soal</div>
                <div class="mt-1 font-semibold">{{ $quiz->shuffle_questions ? 'Ya' : 'Tidak' }}</div>
            </div>
            <div>
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Shuffle Opsi</div>
                <div class="mt-1 font-semibold">{{ $quiz->shuffle_options ? 'Ya' : 'Tidak' }}</div>
            </div>
            <div>
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Status</div>
                <div class="mt-1 font-semibold">{{ $quiz->is_active ? 'Aktif' : 'Nonaktif' }}</div>
            </div>
            <div>
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Dibuat Oleh</div>
                <div class="mt-1 font-semibold">{{ $quiz->creator?->name ?? '-' }}</div>
            </div>
        </div>

        @if (filled($quiz->description))
            <div class="mt-4">
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Deskripsi</div>
                <div class="mt-1 text-sm whitespace-pre-line">{{ $quiz->description }}</div>
            </div>
        @endif
    </div>

    <div class="mt-4 rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
        <div class="border-b border-zinc-200 px-4 py-3 text-sm font-semibold dark:border-zinc-800">Soal</div>
        @if ($quiz->questions->isEmpty())
            <div class="px-4 py-4 text-sm text-zinc-600 dark:text-zinc-300">Belum ada soal.</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-zinc-50 text-zinc-600 dark:bg-zinc-900/40 dark:text-zinc-300">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium">No</th>
                            <th class="px-4 py-2 text-left font-medium">Tipe</th>
                            <th class="px-4 py-2 text-left font-medium">Soal</th>
                            <th class="px-4 py-2 text-left font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @foreach ($quiz->questions as $q)
                            <tr>
                                <td class="px-4 py-2">{{ $q->order_number }}</td>
                                <td class="px-4 py-2">{{ $q->question_type === 'short_answer' ? 'Short Answer' : 'Multiple Choice' }}</td>
                                <td class="px-4 py-2">{{ \Illuminate\Support\Str::limit($q->question_text, 80) }}</td>
                                <td class="px-4 py-2">{{ $q->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-layouts.admin>
