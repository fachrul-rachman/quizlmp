<x-layouts.admin title="Tambah Kategori Quiz">
    <div class="mb-4 flex items-center justify-between gap-3">
        <div class="text-lg font-semibold">Tambah Kategori Quiz</div>
    </div>

    <form method="POST" action="{{ url('/admin/quiz-categories') }}" class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950">
        @csrf
        @include('admin.quiz-categories._form', ['submitLabel' => 'Simpan Kategori'])
    </form>
</x-layouts.admin>
