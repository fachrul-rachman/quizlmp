<?php

namespace App\Support;

use App\Models\Division;

final readonly class DivisionContext
{
    public function __construct(
        public ?int $id,
        public ?string $code,
        public string $name,
        public string $participantAppliedForLabel,
        public string $participantIntroTitle,
    ) {
    }

    public static function from(?Division $division): self
    {
        $defaults = config('divisions.default', []);
        $profile = $division
            ? config('divisions.profiles.'.$division->code, [])
            : [];
        $settings = array_merge($defaults, is_array($profile) ? $profile : []);

        return new self(
            id: $division?->id,
            code: $division?->code,
            name: $division?->name ?? '',
            participantAppliedForLabel: (string) ($settings['participant_applied_for_label'] ?? 'Jabatan/Peringkat'),
            participantIntroTitle: (string) ($settings['participant_intro_title'] ?? 'Sebelum mulai'),
        );
    }
}
