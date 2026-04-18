<?php

namespace App\Livewire\Participant;

use App\Models\QuizAttempt;
use App\Models\QuizLink;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class QuizStart extends Component
{
    public string $token;

    public string $state = 'loading';
    public string $title = '';
    public int $durationMinutes = 0;
    public string $finalMessage = '';

    public string $participantName = '';
    public string $participantAppliedFor = '';

    public function mount(string $token): void
    {
        $this->token = $token;

        $link = QuizLink::query()
            ->with(['quiz:id,title,duration_minutes,is_active', 'attempt'])
            ->where('token', $token)
            ->first();

        if (! $link) {
            $this->state = 'invalid';
            return;
        }

        if (in_array($link->status, ['submitted', 'expired'], true)) {
            $this->state = 'final';
            $this->finalMessage = $link->status === 'submitted'
                ? 'Quiz ini sudah selesai dikerjakan.'
                : 'Waktu pengerjaan quiz ini sudah habis.';
            return;
        }

        if (! $link->quiz || ! $link->quiz->is_active) {
            $this->state = 'unavailable';
            return;
        }

        if ($link->status === 'unused') {
            $link->status = 'opened';
            $link->opened_at = $link->opened_at ?? now();
            $link->save();
        }

        $this->title = (string) $link->quiz->title;
        $this->durationMinutes = (int) $link->quiz->duration_minutes;

        if ($link->attempt) {
            $this->participantName = (string) $link->attempt->participant_name;
            $this->participantAppliedFor = (string) $link->attempt->participant_applied_for;

            if ($link->attempt->status === 'in_progress') {
                $this->redirect('/quiz/'.$token.'/work', navigate: false);
                return;
            }
        }

        $this->state = 'start';
    }

    public function saveIdentity(): void
    {
        $link = $this->getLinkOrFail();
        $attempt = $link->attempt;

        if (! in_array($link->status, ['unused', 'opened'], true)) {
            throw ValidationException::withMessages([
                'participantName' => 'Status link quiz tidak valid.',
            ]);
        }

        if ($attempt && $attempt->status !== 'not_started') {
            throw ValidationException::withMessages([
                'participantName' => 'Tidak bisa mengubah identitas setelah test dimulai.',
            ]);
        }

        $this->validate([
            'participantName' => ['required', 'string', 'max:255'],
            'participantAppliedFor' => ['required', 'string', 'max:255'],
        ], [], [
            'participantName' => 'Nama Peserta',
            'participantAppliedFor' => 'Melamar Untuk',
        ]);

        QuizAttempt::updateOrCreate(
            ['quiz_link_id' => $link->id],
            [
                'quiz_id' => $link->quiz_id,
                'participant_name' => $this->participantName,
                'participant_applied_for' => $this->participantAppliedFor,
                'started_at' => null,
                'submitted_at' => null,
                'time_limit_minutes' => (int) $link->quiz->duration_minutes,
                'status' => 'not_started',
            ],
        );

        session()->flash('success', 'Identitas berhasil disimpan. Test belum dimulai.');
    }

    public function startTest(): void
    {
        $link = $this->getLinkOrFail();

        if (! in_array($link->status, ['unused', 'opened'], true)) {
            return;
        }

        $this->validate([
            'participantName' => ['required', 'string', 'max:255'],
            'participantAppliedFor' => ['required', 'string', 'max:255'],
        ], [], [
            'participantName' => 'Nama Peserta',
            'participantAppliedFor' => 'Melamar Untuk',
        ]);

        $attempt = QuizAttempt::query()->where('quiz_link_id', $link->id)->first();
        if ($attempt && $attempt->status !== 'not_started') {
            return;
        }

        $now = now();

        if (! $attempt) {
            $attempt = QuizAttempt::create([
                'quiz_link_id' => $link->id,
                'quiz_id' => $link->quiz_id,
                'participant_name' => $this->participantName,
                'participant_applied_for' => $this->participantAppliedFor,
                'started_at' => $now,
                'submitted_at' => null,
                'time_limit_minutes' => (int) $link->quiz->duration_minutes,
                'status' => 'in_progress',
            ]);
        } else {
            $attempt->update([
                'participant_name' => $this->participantName,
                'participant_applied_for' => $this->participantAppliedFor,
                'started_at' => $now,
                'status' => 'in_progress',
            ]);
        }

        $link->update([
            'status' => 'in_progress',
            'started_at' => $link->started_at ?? $now,
        ]);

        $this->redirect('/quiz/'.$this->token.'/work', navigate: false);
    }

    private function getLinkOrFail(): QuizLink
    {
        $link = QuizLink::query()
            ->with(['quiz:id,title,duration_minutes,is_active', 'attempt'])
            ->where('token', $this->token)
            ->first();

        if (! $link) {
            abort(404);
        }

        if (in_array($link->status, ['submitted', 'expired'], true)) {
            abort(404);
        }

        if (! $link->quiz || ! $link->quiz->is_active) {
            abort(404);
        }

        return $link;
    }

    private function isStillWithinTimeLimit(QuizAttempt $attempt): bool
    {
        if (! $attempt->started_at) {
            return true;
        }

        $startedAt = CarbonImmutable::parse($attempt->started_at);
        $deadline = $startedAt->addMinutes((int) $attempt->time_limit_minutes);
        return CarbonImmutable::now()->lt($deadline);
    }

    public function render()
    {
        return view('livewire.participant.quiz-start');
    }
}
