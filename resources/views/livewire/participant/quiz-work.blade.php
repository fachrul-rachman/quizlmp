<div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-950" @if($state==='work') wire:poll.1s="tick" @endif>
    @if ($state === 'invalid')
        <h1 class="text-xl font-semibold">Link Quiz Tidak Valid</h1>
        <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">Link yang Anda buka tidak ditemukan atau tidak tersedia.</div>
    @elseif ($state === 'submitted')
        <h1 class="text-xl font-semibold">Link Quiz Tidak Bisa Digunakan</h1>
        <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">Quiz ini sudah selesai dikerjakan.</div>
    @elseif ($state === 'expired' || $state === 'expired_view')
        <h1 class="text-xl font-semibold">Link Quiz Tidak Bisa Digunakan</h1>
        <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">Waktu pengerjaan quiz ini sudah habis.</div>
    @elseif ($state === 'unavailable')
        <h1 class="text-xl font-semibold">Quiz tidak tersedia.</h1>
    @elseif ($state === 'no_questions')
        <h1 class="text-xl font-semibold">Quiz tidak tersedia.</h1>
        <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">Soal tidak ditemukan.</div>
    @elseif ($state === 'work')
        <div class="flex items-start justify-between gap-3">
            <div>
                <div class="text-sm text-zinc-500 dark:text-zinc-400">Nama Quiz</div>
                <div class="mt-1 text-lg font-semibold">{{ $title }}</div>
                <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">Nama: {{ $participantName }}</div>
                <div class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">Melamar Untuk: {{ $participantAppliedFor }}</div>
            </div>

            <div class="rounded-md border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm font-semibold dark:border-zinc-800 dark:bg-zinc-900/40">
                Sisa Waktu: {{ gmdate('i:s', $secondsRemaining) }}
            </div>
        </div>

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
                <div class="whitespace-pre-line">{{ $currentQuestionText }}</div>

                @if ($currentQuestionType === 'multiple_choice')
                    <div class="mt-4 space-y-2">
                        @foreach ($currentOptions as $opt)
                            <label class="flex items-start gap-3 rounded-md border border-zinc-200 p-3 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-900/30">
                                <input
                                    type="radio"
                                    name="selected_option"
                                    value="{{ $opt['id'] }}"
                                    class="mt-1"
                                    wire:model="selectedOptionId"
                                />
                                <div class="min-w-0">
                                    <div class="text-sm font-semibold">{{ $opt['label'] }}</div>
                                    <div class="text-sm text-zinc-700 dark:text-zinc-200 whitespace-pre-line">{{ $opt['text'] }}</div>
                                </div>
                            </label>
                        @endforeach
                    </div>
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
                    <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">Jawaban akan dikirim final.</div>
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
