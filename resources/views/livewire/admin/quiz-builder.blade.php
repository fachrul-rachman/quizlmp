<div>
    @if (session('success'))
        <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800 dark:border-green-900/50 dark:bg-green-950/30 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    @error('questions')
        <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-200">
            {{ $message }}
        </div>
    @enderror

    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium mb-1">Nama Quiz</label>
            <input wire:model.defer="title" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950" />
            @error('title')
                <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Deskripsi</label>
            <textarea wire:model.defer="description" rows="3" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950"></textarea>
            @error('description')
                <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
            @enderror
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div>
                <label class="block text-sm font-medium mb-1">Durasi (menit)</label>
                <input wire:model.defer="durationMinutes" inputmode="numeric" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950" />
                @error('durationMinutes')
                    <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                @enderror
            </div>
            <div class="flex items-center gap-2 pt-6">
                <input type="checkbox" wire:model.defer="shuffleQuestions" class="rounded border-zinc-300 dark:border-zinc-700" />
                <label class="text-sm">Shuffle Soal</label>
            </div>
            <div class="flex items-center gap-2 pt-6">
                <input type="checkbox" wire:model.defer="shuffleOptions" class="rounded border-zinc-300 dark:border-zinc-700" />
                <label class="text-sm">Shuffle Opsi</label>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" wire:model.defer="isActive" class="rounded border-zinc-300 dark:border-zinc-700" />
            <label class="text-sm">Status Aktif</label>
        </div>
    </div>

    <div class="mt-6 rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950">
        <div class="flex items-center justify-between gap-3 mb-2">
            <div class="text-sm font-semibold">Import Soal (.xlsx)</div>
            <a href="{{ url('/admin/quizzes/template') }}" class="text-sm underline underline-offset-2">
                Download Template
            </a>
        </div>
        <div class="text-sm text-zinc-600 dark:text-zinc-300">
            Header: Soal, Jenis Jawaban, Opsi A, Opsi B, Opsi C, Opsi D, Opsi E, Jawaban Benar, Short Answer
        </div>
        <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center">
            <input type="file" wire:model="importFile" accept=".xlsx" class="block w-full text-sm" />
            <button type="button" wire:click="importFromXlsx" class="rounded-md border border-zinc-300 px-3 py-2 text-sm hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800/40">
                Import
            </button>
        </div>
        @error('importFile')
            <pre class="mt-2 whitespace-pre-wrap rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-200">{{ $message }}</pre>
        @enderror
    </div>

    <div class="mt-8 flex items-center justify-between gap-3">
        <div class="text-lg font-semibold">Soal</div>
        <button type="button" wire:click="addQuestion" class="rounded-md border border-zinc-300 px-3 py-2 text-sm hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800/40">
            Tambah Soal
        </button>
    </div>

    <div class="mt-4 space-y-4">
        @foreach ($questions as $qi => $q)
            <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950">
                <div class="flex items-start justify-between gap-3">
                    <div class="text-sm font-semibold">Soal {{ $qi + 1 }}</div>
                    <button type="button" wire:click="removeQuestion({{ $qi }})" class="text-sm text-red-600 underline underline-offset-2 dark:text-red-400">
                        Hapus Soal
                    </button>
                </div>

                <div class="mt-3 space-y-3">
                    <div>
                        <label class="block text-sm font-medium mb-1">Field Soal</label>
                        <textarea wire:model.defer="questions.{{ $qi }}.question_text" rows="3" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950"></textarea>
                        @error('questions.'.$qi.'.question_text')
                            <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Gambar Soal</label>
                        <input type="file" wire:model="questions.{{ $qi }}.question_image_upload" accept="image/*" class="block w-full text-sm" />
                        <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Teks atau gambar boleh salah satu, atau keduanya.</div>
                        @error('questions.'.$qi.'.question_image_upload')
                            <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                        @enderror

                        @php
                            $questionPreviewUrl = null;
                            if (!empty($q['question_image_upload'])) {
                                $questionPreviewUrl = $q['question_image_upload']->temporaryUrl();
                            } elseif (!empty($q['question_image_path']) && empty($q['remove_question_image'])) {
                                $questionPreviewUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($q['question_image_path']);
                            }
                        @endphp

                        @if ($questionPreviewUrl)
                            <div class="mt-3">
                                <img src="{{ $questionPreviewUrl }}" alt="Preview gambar soal {{ $qi + 1 }}" class="max-h-56 rounded-md border border-zinc-200 object-contain dark:border-zinc-800" />
                                <button type="button" wire:click="removeQuestionImage({{ $qi }})" class="mt-2 text-sm text-red-600 underline underline-offset-2 dark:text-red-400">
                                    Hapus Gambar Soal
                                </button>
                            </div>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium mb-1">Jenis Jawaban</label>
                            <select wire:model.live="questions.{{ $qi }}.question_type" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950">
                                <option value="multiple_choice">Multiple Choice</option>
                                <option value="short_answer">Short Answer</option>
                            </select>
                            @error('questions.'.$qi.'.question_type')
                                <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="flex items-center gap-2 pt-6">
                            <input type="checkbox" wire:model.defer="questions.{{ $qi }}.is_active" class="rounded border-zinc-300 dark:border-zinc-700" />
                            <label class="text-sm">Aktif</label>
                        </div>
                    </div>

                    @if (($q['question_type'] ?? 'multiple_choice') === 'multiple_choice')
                        <div class="mt-2">
                            <div class="flex items-center justify-between gap-3">
                                <div class="text-sm font-semibold">Opsi</div>
                                <button type="button" wire:click="addOption({{ $qi }})" class="rounded-md border border-zinc-300 px-3 py-2 text-sm hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800/40">
                                    Tambah Opsi
                                </button>
                            </div>

                            @error('questions.'.$qi.'.options')
                                <div class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                            @enderror

                            <div class="mt-3 space-y-2">
                                @foreach (($q['options'] ?? []) as $oi => $opt)
                                    <div class="rounded-md border border-zinc-200 p-3 dark:border-zinc-800">
                                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-12 sm:items-start">
                                        <div class="sm:col-span-1 flex items-center gap-2 pt-2">
                                            <input type="radio" name="correct-{{ $qi }}" @checked(!empty($opt['is_correct'])) wire:click="markCorrect({{ $qi }}, {{ $oi }})" />
                                        </div>
                                        <div class="sm:col-span-10 space-y-3">
                                            <input wire:model.defer="questions.{{ $qi }}.options.{{ $oi }}.option_text" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950" />
                                            @error('questions.'.$qi.'.options.'.$oi.'.option_text')
                                                <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                                            @enderror

                                            <div>
                                                <label class="block text-sm font-medium mb-1">Gambar Opsi</label>
                                                <input type="file" wire:model="questions.{{ $qi }}.options.{{ $oi }}.option_image_upload" accept="image/*" class="block w-full text-sm" />
                                                <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Setiap opsi boleh teks, gambar, atau keduanya.</div>
                                                @error('questions.'.$qi.'.options.'.$oi.'.option_image_upload')
                                                    <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                                                @enderror

                                                @php
                                                    $optionPreviewUrl = null;
                                                    if (!empty($opt['option_image_upload'])) {
                                                        $optionPreviewUrl = $opt['option_image_upload']->temporaryUrl();
                                                    } elseif (!empty($opt['option_image_path']) && empty($opt['remove_option_image'])) {
                                                        $optionPreviewUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($opt['option_image_path']);
                                                    }
                                                @endphp

                                                @if ($optionPreviewUrl)
                                                    <div class="mt-3">
                                                        <img src="{{ $optionPreviewUrl }}" alt="Preview gambar opsi {{ $oi + 1 }}" class="max-h-40 rounded-md border border-zinc-200 object-contain dark:border-zinc-800" />
                                                        <button type="button" wire:click="removeOptionImage({{ $qi }}, {{ $oi }})" class="mt-2 text-sm text-red-600 underline underline-offset-2 dark:text-red-400">
                                                            Hapus Gambar Opsi
                                                        </button>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="sm:col-span-1 pt-2">
                                            <button type="button" wire:click="removeOption({{ $qi }}, {{ $oi }})" class="text-sm text-red-600 underline underline-offset-2 dark:text-red-400">
                                                Hapus
                                            </button>
                                        </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="mt-2">
                            <label class="block text-sm font-medium mb-1">Jawaban Benar (pakai | untuk beberapa jawaban)</label>
                            <input wire:model.defer="questions.{{ $qi }}.short_answers" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950" />
                            @error('questions.'.$qi.'.short_answers')
                                <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                            @enderror
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-6 flex items-center gap-2">
        <button type="button" wire:click="save" class="rounded-md bg-zinc-900 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
            Simpan
        </button>
        <a href="{{ url('/admin/quizzes') }}" class="rounded-md border border-zinc-300 px-4 py-2 text-sm hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800/40">
            Kembali
        </a>
    </div>
</div>
