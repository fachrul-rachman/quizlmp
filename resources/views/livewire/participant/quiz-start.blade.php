<div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
    @if ($state === 'invalid')
        <h1 class="text-xl font-semibold">Link Quiz Tidak Valid</h1>
        <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">Link yang Anda buka tidak ditemukan atau sudah tidak berlaku. Periksa kembali link dari admin.</div>
    @elseif ($state === 'final')
        <h1 class="text-xl font-semibold">Link Quiz Tidak Bisa Digunakan</h1>
        <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ $finalMessage }}</div>
    @elseif ($state === 'unavailable')
        <h1 class="text-xl font-semibold">Quiz tidak tersedia.</h1>
        <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">Quiz sedang nonaktif atau belum siap digunakan. Hubungi admin untuk link pengganti.</div>
    @elseif ($state === 'start')
        @if (session('success'))
            <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800 dark:border-green-900/50 dark:bg-green-950/30 dark:text-green-200">
                {{ session('success') }}
            </div>
        @endif

        <div class="mb-4">
            <div class="text-sm text-zinc-500 dark:text-zinc-400">Nama Quiz</div>
            <div class="mt-1 text-lg font-semibold">{{ $title }}</div>
            @if ($divisionName !== '')
                <div class="mt-2 inline-flex rounded-full border border-zinc-200 bg-zinc-50 px-2.5 py-1 text-xs font-semibold text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">
                    {{ $divisionName }}
                </div>
            @endif
            <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">Durasi: {{ $durationMinutes }} menit</div>
        </div>

        <div class="mb-4 rounded-md border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900 dark:border-blue-900/50 dark:bg-blue-950/20 dark:text-blue-100">
            <div class="font-semibold">{{ $participantIntroTitle }}</div>
            <ul class="mt-2 space-y-1.5 text-sm">
                <li class="flex gap-2">
                    <span class="mt-0.5 inline-flex h-5 w-5 items-center justify-center rounded-full bg-blue-100 text-blue-900">✓</span>
                    <span>Timer mulai saat Anda menekan tombol <span class="font-semibold">Mulai Test</span>.</span>
                </li>
                <li class="flex gap-2">
                    <span class="mt-0.5 inline-flex h-5 w-5 items-center justify-center rounded-full bg-blue-100 text-blue-900">✓</span>
                    <span>Tidak ada tombol kembali atau nomor soal. Setelah klik <span class="font-semibold">Jawab</span>, otomatis lanjut ke soal berikutnya.</span>
                </li>
                <li class="flex gap-2">
                    <span class="mt-0.5 inline-flex h-5 w-5 items-center justify-center rounded-full bg-blue-100 text-blue-900">✓</span>
                    <span>Jawaban yang sudah dikirim per soal dianggap final (tidak bisa diubah).</span>
                </li>
                <li class="flex gap-2">
                    <span class="mt-0.5 inline-flex h-5 w-5 items-center justify-center rounded-full bg-blue-100 text-blue-900">✓</span>
                    <span>Jika waktu habis, test akan selesai otomatis.</span>
                </li>
                <li class="flex gap-2">
                    <span class="mt-0.5 inline-flex h-5 w-5 items-center justify-center rounded-full bg-blue-100 text-blue-900">✓</span>
                    <span>Anda bisa klik <span class="font-semibold">Simpan Identitas</span> dulu. Test belum berjalan sampai tombol mulai ditekan.</span>
                </li>
            </ul>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="participantName" class="block text-sm font-medium mb-1">Nama Peserta</label>
                <input id="participantName" wire:model.defer="participantName" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950" />
                @error('participantName')
                    <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                @enderror
            </div>
            @if (! $isHrDivision)
                <div>
                    <label for="participantAppliedFor" class="block text-sm font-medium mb-1">{{ $participantAppliedForLabel }}</label>
                    <input id="participantAppliedFor" wire:model.defer="participantAppliedFor" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950" />
                    @error('participantAppliedFor')
                        <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                    @enderror
                </div>
            @endif
            @if ($isHrDivision)
                <div>
                    <label for="participantAge" class="block text-sm font-medium mb-1">Usia</label>
                    <input id="participantAge" type="number" min="15" max="100" step="1" inputmode="numeric" wire:model.defer="participantAge" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950" />
                    @error('participantAge')
                        <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                    @enderror
                </div>
                <div>
                    <label for="participantHeightCm" class="block text-sm font-medium mb-1">Tinggi Badan (cm)</label>
                    <input id="participantHeightCm" type="number" min="50" max="250" step="0.01" inputmode="decimal" wire:model.defer="participantHeightCm" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950" />
                    @error('participantHeightCm')
                        <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                    @enderror
                </div>
                <div>
                    <label for="participantWeightKg" class="block text-sm font-medium mb-1">Berat Badan (kg)</label>
                    <input id="participantWeightKg" type="number" min="20" max="300" step="0.01" inputmode="decimal" wire:model.defer="participantWeightKg" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950" />
                    @error('participantWeightKg')
                        <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                    @enderror
                </div>
                <div>
                    <label for="participantLastJob" class="block text-sm font-medium mb-1">Pekerjaan Terakhir</label>
                    <input id="participantLastJob" wire:model.defer="participantLastJob" maxlength="255" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950" />
                    @error('participantLastJob')
                        <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                    @enderror
                </div>
                <div>
                    <label for="participantLastCompany" class="block text-sm font-medium mb-1">Perusahaan Terakhir</label>
                    <input id="participantLastCompany" wire:model.defer="participantLastCompany" maxlength="255" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950" />
                    @error('participantLastCompany')
                        <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                    @enderror
                </div>
                <div>
                    <label for="participantLastJobStartedOn" class="block text-sm font-medium mb-1">Sejak Kapan Bekerja</label>
                    <input id="participantLastJobStartedOn" type="month" max="{{ now()->format('Y-m') }}" wire:model.defer="participantLastJobStartedOn" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950" />
                    @error('participantLastJobStartedOn')
                        <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                    @enderror
                </div>
                <div>
                    <label for="participantCurrentDomicile" class="block text-sm font-medium mb-1">Domisili Sekarang</label>
                    <input id="participantCurrentDomicile" wire:model.defer="participantCurrentDomicile" maxlength="255" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950" />
                    @error('participantCurrentDomicile')
                        <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                    @enderror
                </div>
            @endif
        </div>

        <div class="mt-5 flex items-center gap-2">
            <button type="button" wire:click="startTest" class="rounded-md bg-blue-900 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">
                Mulai Test
            </button>
            <button type="button" wire:click="saveIdentity" class="rounded-md border border-zinc-300 px-4 py-2 text-sm hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800/40">
                Simpan Identitas
            </button>
        </div>
    @endif
</div>
