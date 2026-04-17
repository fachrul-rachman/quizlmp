<x-layouts.admin title="Daftar Link">
    @if (session('success'))
        <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800 dark:border-green-900/50 dark:bg-green-950/30 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex items-center justify-between gap-3 mb-4">
        <div class="text-lg font-semibold">Daftar Link</div>
        <a href="{{ url('/admin/generate-link') }}" class="rounded-md border border-zinc-300 px-3 py-2 text-sm hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800/40">
            Generate Link
        </a>
    </div>

    <form method="GET" action="{{ url('/admin/links') }}" class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-4">
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium mb-1">Search Token</label>
            <input name="search" value="{{ $search }}" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950" />
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Quiz</label>
            <select name="quiz_id" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950">
                <option value="">Semua</option>
                @foreach ($quizzes as $q)
                    <option value="{{ $q->id }}" @selected((string) $q->id === (string) $quizId)>{{ $q->title }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Status</label>
            <select name="status" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950">
                <option value="all" @selected($status === 'all')>Semua</option>
                <option value="unused" @selected($status === 'unused')>unused</option>
                <option value="opened" @selected($status === 'opened')>opened</option>
                <option value="in_progress" @selected($status === 'in_progress')>in_progress</option>
                <option value="submitted" @selected($status === 'submitted')>submitted</option>
                <option value="expired" @selected($status === 'expired')>expired</option>
            </select>
        </div>

        <div class="sm:col-span-4 flex gap-2">
            <button type="submit" class="rounded-md bg-zinc-900 px-3 py-2 text-sm font-semibold text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                Filter
            </button>
            <a href="{{ url('/admin/links') }}" class="rounded-md border border-zinc-300 px-3 py-2 text-sm hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800/40">
                Reset
            </a>
        </div>
    </form>

    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
        @if ($links->isEmpty())
            <div class="px-4 py-4 text-sm text-zinc-600 dark:text-zinc-300">Belum ada link.</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-zinc-50 text-zinc-600 dark:bg-zinc-900/40 dark:text-zinc-300">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium">Nama Quiz</th>
                            <th class="px-4 py-2 text-left font-medium">Token</th>
                            <th class="px-4 py-2 text-left font-medium">Status</th>
                            <th class="px-4 py-2 text-left font-medium">Opened At</th>
                            <th class="px-4 py-2 text-left font-medium">Started At</th>
                            <th class="px-4 py-2 text-left font-medium">Submitted At</th>
                            <th class="px-4 py-2 text-left font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @foreach ($links as $link)
                            @php($baseUrl = rtrim((string) config('app.url'), '/'))
                            @php($url = $baseUrl !== '' ? $baseUrl.'/quiz/'.$link->token : url('/quiz/'.$link->token))
                            <tr>
                                <td class="px-4 py-2">{{ $link->quiz?->title ?? '-' }}</td>
                                <td class="px-4 py-2 font-mono">{{ $link->token }}</td>
                                <td class="px-4 py-2">{{ $link->status }}</td>
                                <td class="px-4 py-2">{{ $link->opened_at }}</td>
                                <td class="px-4 py-2">{{ $link->started_at }}</td>
                                <td class="px-4 py-2">{{ $link->submitted_at }}</td>
                                <td class="px-4 py-2">
                                    <div class="flex items-center gap-3">
                                        <button type="button" class="underline underline-offset-2" onclick="copyText('{{ $url }}')">Copy Link</button>
                                        <a href="{{ url('/admin/links/'.$link->id) }}" class="underline underline-offset-2">Detail</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-3">
                {{ $links->links() }}
            </div>
        @endif
    </div>

    <script>
        function fallbackCopyText(text) {
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.setAttribute('readonly', '');
            ta.style.position = 'fixed';
            ta.style.top = '-1000px';
            ta.style.left = '-1000px';
            document.body.appendChild(ta);
            ta.select();
            ta.setSelectionRange(0, ta.value.length);
            try { document.execCommand('copy'); } catch (e) {}
            document.body.removeChild(ta);
        }
        function copyText(text) {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).catch(() => fallbackCopyText(text));
                return;
            }
            fallbackCopyText(text);
        }
    </script>
</x-layouts.admin>
