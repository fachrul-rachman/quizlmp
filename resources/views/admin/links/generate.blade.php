<x-layouts.admin title="Generate Link">
    @if (session('success'))
        <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800 dark:border-green-900/50 dark:bg-green-950/30 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="text-lg font-semibold mb-4">Generate Link</div>

    <form method="POST" action="{{ url('/admin/generate-link') }}" class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950">
        @csrf

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium mb-1">Pilih Quiz</label>
                <select name="quiz_id" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950" required>
                    <option value="">-- Pilih Quiz --</option>
                    @foreach ($activeQuizzes as $q)
                        <option value="{{ $q->id }}" @selected(old('quiz_id') == $q->id)>{{ $q->title }}</option>
                    @endforeach
                </select>
                @error('quiz_id')
                    <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Jumlah Link</label>
                <input name="count" value="{{ old('count', 1) }}" inputmode="numeric" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950" required />
                @error('count')
                    <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="mt-4 flex items-center gap-2">
            <button type="submit" class="rounded-md bg-zinc-900 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                Generate Link
            </button>
            <a href="{{ url('/admin/links') }}" class="rounded-md border border-zinc-300 px-4 py-2 text-sm hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800/40">
                Lihat Daftar Link
            </a>
        </div>
    </form>

    @if ($generatedLinks->isNotEmpty())
        <div class="mt-6 rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
            <div class="flex items-center justify-between gap-3 border-b border-zinc-200 px-4 py-3 text-sm font-semibold dark:border-zinc-800">
                <div>Hasil Generate</div>
                <button type="button" class="rounded-md border border-zinc-300 px-3 py-1.5 text-sm hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800/40" onclick="copyAllGenerated()">
                    Copy All
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-zinc-50 text-zinc-600 dark:bg-zinc-900/40 dark:text-zinc-300">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium"></th>
                            <th class="px-4 py-2 text-left font-medium">No</th>
                            <th class="px-4 py-2 text-left font-medium">Nama Quiz</th>
                            <th class="px-4 py-2 text-left font-medium">Token</th>
                            <th class="px-4 py-2 text-left font-medium">URL Lengkap</th>
                            <th class="px-4 py-2 text-left font-medium">Status</th>
                            <th class="px-4 py-2 text-left font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @foreach ($generatedLinks as $idx => $link)
                            @php($baseUrl = rtrim((string) config('app.url'), '/'))
                            @php($url = $baseUrl !== '' ? $baseUrl.'/quiz/'.$link->token : url('/quiz/'.$link->token))
                            <tr>
                                <td class="px-4 py-2">
                                    <input type="checkbox" class="generated-checkbox" value="{{ $url }}" checked />
                                </td>
                                <td class="px-4 py-2">{{ $idx + 1 }}</td>
                                <td class="px-4 py-2">{{ $link->quiz?->title ?? '-' }}</td>
                                <td class="px-4 py-2 font-mono">{{ $link->token }}</td>
                                <td class="px-4 py-2 font-mono">{{ $url }}</td>
                                <td class="px-4 py-2">{{ $link->status }}</td>
                                <td class="px-4 py-2">
                                    <button type="button" class="underline underline-offset-2" onclick="copyText('{{ $url }}')">Copy</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
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
            function copyAllGenerated() {
                const values = Array.from(document.querySelectorAll('.generated-checkbox'))
                    .filter(cb => cb.checked)
                    .map(cb => cb.value);
                const text = values.join('\n');
                copyText(text);
            }
        </script>
    @endif
</x-layouts.admin>
