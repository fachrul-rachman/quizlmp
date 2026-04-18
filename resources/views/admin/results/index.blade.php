<x-layouts.admin title="Daftar Hasil">
    <div class="flex items-center justify-between gap-3 mb-4">
        <div class="text-lg font-semibold">Daftar Hasil</div>
    </div>

    <form method="GET" action="{{ url('/admin/results') }}" class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-4">
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium mb-1">Search</label>
            <input name="search" value="{{ $search }}" placeholder="Nama peserta, jabatan, atau nama quiz" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950" />
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Quiz</label>
            <select name="quiz_id" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950">
                <option value="">Semua</option>
                @foreach ($quizzes as $quiz)
                    <option value="{{ $quiz->id }}" @selected((string) $quiz->id === (string) $quizId)>{{ $quiz->title }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Status</label>
            <select name="status" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950">
                <option value="all" @selected($status === 'all')>Semua</option>
                <option value="submitted" @selected($status === 'submitted')>submitted</option>
                <option value="auto_submitted" @selected($status === 'auto_submitted')>auto_submitted</option>
            </select>
        </div>
        <div class="sm:col-span-4 flex gap-2">
            <button type="submit" class="rounded-md bg-zinc-900 px-3 py-2 text-sm font-semibold text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                Filter
            </button>
            <a href="{{ url('/admin/results') }}" class="rounded-md border border-zinc-300 px-3 py-2 text-sm hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800/40">
                Reset
            </a>
        </div>
    </form>

    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
        @if ($rows->isEmpty())
            <div class="px-4 py-4 text-sm text-zinc-600 dark:text-zinc-300">Belum ada hasil quiz.</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-zinc-50 text-zinc-600 dark:bg-zinc-900/40 dark:text-zinc-300">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium">Peserta</th>
                            <th class="px-4 py-2 text-left font-medium">Quiz</th>
                            <th class="px-4 py-2 text-left font-medium">Score</th>
                            <th class="px-4 py-2 text-left font-medium">Status</th>
                            <th class="px-4 py-2 text-left font-medium">Tanggal</th>
                            <th class="px-4 py-2 text-left font-medium">File Drive</th>
                            <th class="px-4 py-2 text-left font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @foreach ($rows as $row)
                            @php($result = $row['result'])
                            @php($attempt = $result->attempt)
                            @php($pdf = $row['pdf'])
                            <tr>
                                <td class="px-4 py-3 align-top">
                                    <div class="font-medium">{{ $attempt?->participant_name ?? '-' }}</div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $attempt?->participant_applied_for ?: '-' }}</div>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <div>{{ $result->quiz?->title ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <div class="font-medium">{{ number_format((float) $result->score_percentage, 2) }}%</div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $result->correct_answers }}/{{ $result->total_questions }} benar • Grade {{ $result->grade_letter ?? '-' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium {{ $result->result_status === 'submitted' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200' : 'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-200' }}">
                                        {{ $result->result_status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <div>{{ optional($result->calculated_at)->format('d M Y') ?: '-' }}</div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ optional($result->calculated_at)->format('H:i:s') ?: '' }}</div>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    @if ($pdf?->google_drive_url)
                                        <a href="{{ $pdf->google_drive_url }}" target="_blank" rel="noreferrer" class="underline underline-offset-2">
                                            Buka File
                                        </a>
                                    @else
                                        <span class="text-zinc-500 dark:text-zinc-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <a href="{{ url('/admin/results/'.$result->id) }}" class="underline underline-offset-2">Detail</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-3">
                {{ $results->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
