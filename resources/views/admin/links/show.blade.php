<x-layouts.admin title="Link Detail">
    <div class="flex items-center justify-between gap-3 mb-4">
        <div class="text-lg font-semibold">Link Detail</div>
        <a href="{{ url('/admin/links') }}" class="rounded-md border border-zinc-300 px-3 py-2 text-sm hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800/40">
            Kembali
        </a>
    </div>

    @php($baseUrl = rtrim((string) config('app.url'), '/'))
    @php($url = $baseUrl !== '' ? $baseUrl.'/quiz/'.$link->token : url('/quiz/'.$link->token))

    <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div>
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Nama Quiz</div>
                <div class="mt-1 font-semibold">{{ $link->quiz?->title ?? '-' }}</div>
            </div>
            <div>
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Status</div>
                <div class="mt-1 font-semibold">{{ $link->status }}</div>
            </div>
            <div class="sm:col-span-2">
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Token</div>
                <div class="mt-1 font-mono">{{ $link->token }}</div>
            </div>
            <div class="sm:col-span-2">
                <div class="text-sm text-zinc-500 dark:text-zinc-400">URL Lengkap</div>
                <div class="mt-1 font-mono break-all">{{ $url }}</div>
                <button type="button" class="mt-2 underline underline-offset-2 text-sm" onclick="copyText('{{ $url }}')">Copy Link</button>
            </div>
            <div>
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Opened At</div>
                <div class="mt-1">{{ $link->opened_at }}</div>
            </div>
            <div>
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Started At</div>
                <div class="mt-1">{{ $link->started_at }}</div>
            </div>
            <div>
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Submitted At</div>
                <div class="mt-1">{{ $link->submitted_at }}</div>
            </div>
            <div>
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Expired At</div>
                <div class="mt-1">{{ $link->expired_at }}</div>
            </div>
            <div>
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Dibuat Oleh</div>
                <div class="mt-1">{{ $link->creator?->name ?? '-' }}</div>
            </div>
            <div>
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Dibuat Pada</div>
                <div class="mt-1">{{ $link->created_at }}</div>
            </div>
        </div>
    </div>

    @if ($link->attempt)
        <div class="mt-4 rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950">
            <div class="text-sm font-semibold mb-3">Data Attempt</div>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Nama Peserta</div>
                <div class="mt-1 font-semibold">{{ $link->attempt->participant_name }}</div>
            </div>
            <div>
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Melamar Untuk</div>
                <div class="mt-1 font-semibold">{{ $link->attempt->participant_applied_for }}</div>
            </div>
            <div>
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Status Attempt</div>
                <div class="mt-1">{{ $link->attempt->status }}</div>
            </div>
                <div>
                    <div class="text-sm text-zinc-500 dark:text-zinc-400">Waktu Mulai</div>
                    <div class="mt-1">{{ $link->attempt->started_at }}</div>
                </div>
                <div>
                    <div class="text-sm text-zinc-500 dark:text-zinc-400">Waktu Submit</div>
                    <div class="mt-1">{{ $link->attempt->submitted_at }}</div>
                </div>
            </div>
        </div>
    @endif

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
