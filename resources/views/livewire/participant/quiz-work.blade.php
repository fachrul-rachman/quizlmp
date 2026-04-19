<div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-950" @if($state==='work') wire:poll.1s="tick" @endif>
    @if ($state === 'invalid')
        <h1 class="text-xl font-semibold">Link Quiz Tidak Valid</h1>
        <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">Link yang Anda buka tidak ditemukan atau sudah tidak berlaku. Periksa kembali link dari admin.</div>
    @elseif ($state === 'submitted')
        <h1 class="text-xl font-semibold">Link Quiz Tidak Bisa Digunakan</h1>
        <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">Quiz ini sudah selesai dikerjakan.</div>
    @elseif ($state === 'expired' || $state === 'expired_view')
        <h1 class="text-xl font-semibold">Link Quiz Tidak Bisa Digunakan</h1>
        <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">Waktu pengerjaan quiz ini sudah habis.</div>
    @elseif ($state === 'unavailable')
        <h1 class="text-xl font-semibold">Quiz tidak tersedia.</h1>
        <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">Quiz sedang nonaktif atau link ini tidak dapat dipakai lagi.</div>
    @elseif ($state === 'no_questions')
        <h1 class="text-xl font-semibold">Soal belum tersedia.</h1>
        <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">Data soal tidak ditemukan. Hubungi admin untuk memeriksa quiz ini.</div>
    @elseif ($state === 'work')
        <div class="flex items-start justify-between gap-3">
            <div>
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Nama Quiz</div>
                <div class="mt-1 text-lg font-semibold">{{ $title }}</div>
                <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">Nama: {{ $participantName }}</div>
                <div class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">Melamar Untuk: {{ $participantAppliedFor }}</div>
            </div>

            <div class="rounded-md border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm font-semibold dark:border-zinc-800 dark:bg-zinc-900/40">
                Sisa Waktu:
                {{ $secondsRemaining >= 3600 ? gmdate('H:i:s', $secondsRemaining) : gmdate('i:s', $secondsRemaining) }}
            </div>
        </div>

        @if ($instantFeedbackEnabled)
            <div class="mt-4 rounded-md border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900 dark:border-sky-900/50 dark:bg-sky-950/20 dark:text-sky-100">
                Setiap soal pilihan ganda hanya bisa dijawab satu kali. Setelah Anda memilih opsi, jawaban langsung terkunci dan hasil benar atau salah langsung ditampilkan.
            </div>
        @else
            <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-100">
                Jawaban tersimpan saat Anda memilih opsi atau mengetik. Tombol submit akan aktif setelah semua soal terjawab.
            </div>
        @endif

        <div class="mt-6">
            <div class="flex items-center justify-between gap-3">
                <div class="text-sm font-semibold">Soal {{ $step }} dari {{ count($questionIds) }}</div>
                <div class="flex items-center gap-2">
                    <button type="button" class="rounded-md border border-zinc-300 px-3 py-1.5 text-sm hover:bg-zinc-100 disabled:opacity-50 dark:border-zinc-700 dark:hover:bg-zinc-800/40" wire:click="prev" @disabled($step <= 1)>
                        Back
                    </button>
                    <button type="button" class="rounded-md border border-zinc-300 px-3 py-1.5 text-sm hover:bg-zinc-100 disabled:opacity-50 dark:border-zinc-700 dark:hover:bg-zinc-800/40" wire:click="next" @disabled($step >= count($questionIds))>
                        Next
                    </button>
                </div>
            </div>

            <div class="mt-3 rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950">
                <div class="text-sm text-zinc-600 dark:text-zinc-300 mb-2">Soal</div>
                @if (filled($currentQuestionText))
                    <div class="whitespace-pre-line">{{ $currentQuestionText }}</div>
                @endif

                @if ($currentQuestionImagePath)
                    <div class="@if(filled($currentQuestionText)) mt-4 @endif">
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($currentQuestionImagePath) }}" alt="Gambar soal" class="max-h-72 rounded-md border border-zinc-200 object-contain dark:border-zinc-800" />
                    </div>
                @endif

                @if ($currentQuestionType === 'multiple_choice')
                    <div class="mt-4 space-y-2">
                        @foreach ($currentOptions as $opt)
                            @php
                                $isSelected = $selectedOptionId === $opt['id'];
                                $isCorrectOption = $instantFeedbackEnabled && !empty($opt['is_correct']);
                                $showLockedState = $instantFeedbackEnabled && $currentAnswerLocked;
                                $optionClass = 'border-zinc-200 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-900/30';

                                if ($showLockedState) {
                                    if ($isCorrectOption) {
                                        $optionClass = 'border-emerald-300 bg-emerald-50 text-emerald-950 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-100';
                                    } elseif ($isSelected && $currentAnswerIsCorrect === false) {
                                        $optionClass = 'border-red-300 bg-red-50 text-red-950 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-100';
                                    }
                                } elseif ($isSelected) {
                                    $optionClass = 'border-sky-300 bg-sky-50 dark:border-sky-900/50 dark:bg-sky-950/20';
                                }
                            @endphp
                            <label class="flex items-start gap-3 rounded-md border p-3 {{ $optionClass }}">
                                <input
                                    type="radio"
                                    name="selected_option"
                                    value="{{ $opt['id'] }}"
                                    class="mt-1"
                                    wire:model="selectedOptionId"
                                    @disabled($instantFeedbackEnabled && $currentAnswerLocked)
                                />
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 text-sm font-semibold">
                                        <span>{{ $opt['label'] }}</span>
                                        @if ($instantFeedbackEnabled && $currentAnswerLocked && !empty($opt['is_correct']))
                                            <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200">Benar</span>
                                        @elseif ($instantFeedbackEnabled && $currentAnswerLocked && $isSelected && $currentAnswerIsCorrect === false)
                                            <span class="inline-flex rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800 dark:bg-red-950/40 dark:text-red-200">Pilihan Anda</span>
                                        @endif
                                    </div>
                                    @if (filled($opt['text']))
                                        <div class="text-sm text-zinc-700 dark:text-zinc-200 whitespace-pre-line">{{ $opt['text'] }}</div>
                                    @endif
                                    @if (!empty($opt['image_path']))
                                        <div class="@if(filled($opt['text'])) mt-3 @else mt-2 @endif">
                                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($opt['image_path']) }}" alt="Gambar opsi {{ $opt['label'] }}" class="max-h-48 rounded-md border border-zinc-200 object-contain dark:border-zinc-800" />
                                        </div>
                                    @endif
                                </div>
                            </label>
                        @endforeach
                    </div>
                    @if ($instantFeedbackEnabled && $currentAnswerLocked)
                        <div class="mt-3 text-sm font-medium {{ $currentAnswerIsCorrect ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300' }}">
                            {{ $currentAnswerIsCorrect ? 'Jawaban Anda benar.' : 'Jawaban Anda salah. Opsi yang benar ditandai hijau.' }}
                        </div>
                    @endif
                @else
                    <div class="mt-4">
                        <label class="block text-sm font-medium mb-1">Jawaban</label>
                        <textarea wire:model.debounce.500ms="shortAnswerText" rows="3" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950"></textarea>
                    </div>
                @endif
            </div>

            <div class="mt-4 flex items-center justify-between gap-3">
                <div class="text-sm text-zinc-600 dark:text-zinc-300">Terjawab: {{ $answeredCount }}/{{ $totalQuestions }}</div>
                <button
                    type="button"
                    wire:click="openSubmitConfirm"
                    @disabled(! $canSubmit)
                    class="rounded-md bg-zinc-900 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-800 disabled:opacity-50 disabled:hover:bg-zinc-900 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200 dark:disabled:hover:bg-white"
                >
                    Submit Jawaban
                </button>
            </div>
        </div>

        @if ($showSubmitConfirm)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
                <div class="w-full max-w-md rounded-lg border border-zinc-200 bg-white p-4 shadow-lg dark:border-zinc-800 dark:bg-zinc-950">
                    <div class="text-sm font-semibold">Konfirmasi Submit</div>
                    <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">Jawaban akan dikirim final dan tidak bisa diubah lagi.</div>
                    <div class="mt-4 flex items-center justify-end gap-2">
                        <button type="button" wire:click="cancelSubmit" class="rounded-md border border-zinc-300 px-3 py-2 text-sm hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800/40">
                            Batal
                        </button>
                        <button type="button" wire:click="submit" class="rounded-md bg-zinc-900 px-3 py-2 text-sm font-semibold text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                            Submit
                        </button>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
