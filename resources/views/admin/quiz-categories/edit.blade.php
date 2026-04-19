<x-layouts.admin title="Edit Kategori Quiz">
    <div class="mb-4 flex items-center justify-between gap-3">
        <div class="text-lg font-semibold">Edit Kategori Quiz</div>
    </div>

    <form method="POST" action="{{ url('/admin/quiz-categories/'.$quizCategory->id) }}" class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950">
        @csrf
        @method('PUT')
        @include('admin.quiz-categories._form', ['quizCategory' => $quizCategory, 'submitLabel' => 'Simpan Perubahan'])
    </form>
</x-layouts.admin>
