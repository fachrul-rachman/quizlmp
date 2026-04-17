<x-layouts.admin title="Dashboard">
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950">
            <div class="text-sm text-zinc-500 dark:text-zinc-400">Total Quiz</div>
            <div class="mt-1 text-2xl font-semibold">{{ $stats['total_quizzes'] }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950">
            <div class="text-sm text-zinc-500 dark:text-zinc-400">Total Link Generated</div>
            <div class="mt-1 text-2xl font-semibold">{{ $stats['total_links'] }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950">
            <div class="text-sm text-zinc-500 dark:text-zinc-400">Total Hasil Masuk</div>
            <div class="mt-1 text-2xl font-semibold">{{ $stats['total_results'] }}</div>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950">
            <div class="text-sm text-zinc-500 dark:text-zinc-400">Total Admin User</div>
            <div class="mt-1 text-2xl font-semibold">{{ $stats['total_admin_users'] }}</div>
        </div>
    </div>

    <div class="mt-4 rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
        <div class="border-b border-zinc-200 px-4 py-3 text-sm font-semibold dark:border-zinc-800">Hasil Terbaru</div>

        @if ($latestResults->isEmpty())
            <div class="px-4 py-4 text-sm text-zinc-600 dark:text-zinc-300">Belum ada hasil quiz.</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-zinc-50 text-zinc-600 dark:bg-zinc-900/40 dark:text-zinc-300">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium">Nama Test</th>
                            <th class="px-4 py-2 text-left font-medium">Nama Peserta</th>
                            <th class="px-4 py-2 text-left font-medium">Melamar Untuk</th>
                            <th class="px-4 py-2 text-left font-medium">Score</th>
                            <th class="px-4 py-2 text-left font-medium">Grade</th>
                            <th class="px-4 py-2 text-left font-medium">Status</th>
                            <th class="px-4 py-2 text-left font-medium">Waktu Selesai</th>
                            <th class="px-4 py-2 text-left font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @foreach ($latestResults as $row)
                            <tr>
                                <td class="px-4 py-2">{{ $row->quiz_title }}</td>
                                <td class="px-4 py-2">{{ $row->participant_name }}</td>
                                <td class="px-4 py-2">{{ $row->participant_applied_for }}</td>
                                <td class="px-4 py-2">{{ number_format((float) $row->score_percentage, 2) }}</td>
                                <td class="px-4 py-2">{{ $row->grade_letter }} - {{ $row->grade_label }}</td>
                                <td class="px-4 py-2">
                                    {{ $row->result_status === 'auto_submitted' ? 'Auto Submitted' : 'Submitted' }}
                                </td>
                                <td class="px-4 py-2">{{ $row->calculated_at }}</td>
                                <td class="px-4 py-2">
                                    <a href="{{ url('/admin/results/'.$row->id) }}" class="text-zinc-900 underline underline-offset-2 hover:text-zinc-700 dark:text-zinc-100 dark:hover:text-zinc-300">Detail</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-layouts.admin>
