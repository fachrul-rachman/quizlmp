@php($quizCategory = $quizCategory ?? null)
@php($submitLabel = $submitLabel ?? 'Simpan')

@if (session('error'))
    <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-200">
        {{ session('error') }}
    </div>
@endif

<div>
    <label class="mb-1 block text-sm font-medium">Nama Kategori</label>
    <input name="name" value="{{ old('name', $quizCategory?->name) }}" required class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950" />
    @error('name')
        <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
    @enderror
</div>

<div class="mt-6 flex items-center gap-2">
    <button type="submit" class="rounded-md bg-zinc-900 px-3 py-2 text-sm font-semibold text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
        {{ $submitLabel }}
    </button>
    <a href="{{ url('/admin/quiz-categories') }}" class="rounded-md border border-zinc-300 px-3 py-2 text-sm hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800/40">
        Batal
    </a>
</div>
