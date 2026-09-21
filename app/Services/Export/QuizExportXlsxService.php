<?php

namespace App\Services\Export;

use App\Models\Quiz;
use App\Support\QuestionDifficulty;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class QuizExportXlsxService
{
    private const HEADERS = [
        'Nomor Soal',
        'Status Soal',
        'Jenis Jawaban',
        'Tingkat Kesulitan',
        'Pertanyaan',
        'Path Gambar Soal',
        'Opsi A',
        'Path Gambar Opsi A',
        'Opsi B',
        'Path Gambar Opsi B',
        'Opsi C',
        'Path Gambar Opsi C',
        'Opsi D',
        'Path Gambar Opsi D',
        'Opsi E',
        'Path Gambar Opsi E',
        'Jawaban Benar',
        'Short Answer',
    ];

    /**
     * @param  Collection<int, Quiz>  $quizzes
     * @return array{path:string, download_name:string}
     */
    public function exportToTempFile(Collection $quizzes): array
    {
        if ($quizzes->isEmpty()) {
            throw new \InvalidArgumentException('Minimal satu quiz harus dipilih.');
        }

        $spreadsheet = new Spreadsheet;
        $usedSheetNames = [];

        foreach ($quizzes->values() as $index => $quiz) {
            $sheet = $index === 0
                ? $spreadsheet->getActiveSheet()
                : $spreadsheet->createSheet();

            $sheetName = $this->sheetName((string) $quiz->title, (int) $quiz->id, $usedSheetNames);
            $usedSheetNames[] = $sheetName;
            $sheet->setTitle($sheetName);
            $this->fillWorksheet($sheet, $quiz);
        }

        $spreadsheet->setActiveSheetIndex(0);

        $tempDir = storage_path('app/tmp');
        if (! File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        $timestamp = CarbonImmutable::now('Asia/Jakarta')->format('Ymd_His_u');
        $tempPath = $tempDir.DIRECTORY_SEPARATOR.'export_quiz_'.$timestamp.'.xlsx';
        (new Xlsx($spreadsheet))->save($tempPath);
        $spreadsheet->disconnectWorksheets();

        return [
            'path' => $tempPath,
            'download_name' => 'Export Quiz '.CarbonImmutable::now('Asia/Jakarta')->format('Ymd_His').'.xlsx',
        ];
    }

    private function fillWorksheet(Worksheet $sheet, Quiz $quiz): void
    {
        $sheet->mergeCells('A1:R1');
        $this->setText($sheet, 'A1', 'EXPORT QUIZ — '.$quiz->title);
        $sheet->getStyle('A1:R1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF17365D']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $sheet->mergeCells('A3:R3');
        $this->setText($sheet, 'A3', 'INFORMASI QUIZ');
        $this->applySectionStyle($sheet, 'A3:R3');

        $questions = $quiz->questions;
        $information = [
            ['Nama Quiz', (string) $quiz->title],
            ['Deskripsi', (string) ($quiz->description ?? '')],
            ['Durasi (menit)', (int) $quiz->duration_minutes],
            ['Shuffle Soal', $quiz->shuffle_questions ? 'Ya' : 'Tidak'],
            ['Shuffle Opsi', $quiz->shuffle_options ? 'Ya' : 'Tidak'],
            ['Tampilkan Jawaban Benar', $quiz->instant_feedback_enabled ? 'Ya' : 'Tidak'],
            ['Kesulitan Bertingkat', $quiz->difficulty_levels_enabled ? 'Ya' : 'Tidak'],
            ['Jumlah Soal', $questions->count()],
            ['Soal Aktif', $questions->where('is_active', true)->count()],
        ];

        foreach ($information as $offset => [$label, $value]) {
            $row = 4 + $offset;
            $sheet->mergeCells("A{$row}:B{$row}");
            $sheet->mergeCells("C{$row}:F{$row}");
            $this->setText($sheet, "A{$row}", $label);
            is_int($value)
                ? $sheet->setCellValue("C{$row}", $value)
                : $this->setText($sheet, "C{$row}", $value);
            $sheet->getStyle("A{$row}:B{$row}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['argb' => 'FF17365D']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD9EAF7']],
            ]);
            $sheet->getStyle("A{$row}:F{$row}")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_HAIR);
        }

        $sheet->mergeCells('A14:R14');
        $this->setText($sheet, 'A14', 'DAFTAR SOAL');
        $this->applySectionStyle($sheet, 'A14:R14');

        foreach (self::HEADERS as $columnIndex => $header) {
            $this->setText($sheet, [$columnIndex + 1, 15], $header);
        }
        $sheet->getStyle('A15:R15')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1F4E78']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD9E2F3']]],
        ]);
        $sheet->getRowDimension(15)->setRowHeight(34);

        foreach ($questions as $offset => $question) {
            $row = 16 + $offset;
            $options = $question->options->keyBy(fn ($option) => strtoupper((string) $option->option_key));
            $correctAnswers = $question->options
                ->where('is_correct', true)
                ->pluck('option_key')
                ->map(fn ($key) => strtoupper((string) $key))
                ->implode(', ');
            $shortAnswers = $question->shortAnswerKeys->pluck('answer_text')->implode(' | ');

            $sheet->setCellValue("A{$row}", (int) $question->order_number);
            $values = [
                'B' => $question->is_active ? 'Aktif' : 'Nonaktif',
                'C' => $question->question_type === 'multiple_choice' ? 'Pilihan Ganda' : 'Short Answer',
                'D' => QuestionDifficulty::label((string) $question->difficulty_level),
                'E' => (string) $question->question_text,
                'F' => (string) ($question->question_image_path ?? ''),
                'G' => (string) ($options->get('A')?->option_text ?? ''),
                'H' => (string) ($options->get('A')?->option_image_path ?? ''),
                'I' => (string) ($options->get('B')?->option_text ?? ''),
                'J' => (string) ($options->get('B')?->option_image_path ?? ''),
                'K' => (string) ($options->get('C')?->option_text ?? ''),
                'L' => (string) ($options->get('C')?->option_image_path ?? ''),
                'M' => (string) ($options->get('D')?->option_text ?? ''),
                'N' => (string) ($options->get('D')?->option_image_path ?? ''),
                'O' => (string) ($options->get('E')?->option_text ?? ''),
                'P' => (string) ($options->get('E')?->option_image_path ?? ''),
                'Q' => $correctAnswers,
                'R' => $shortAnswers,
            ];

            foreach ($values as $column => $value) {
                $this->setText($sheet, "{$column}{$row}", $value);
            }

            $sheet->getStyle("A{$row}:R{$row}")->applyFromArray([
                'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['argb' => 'FFD9E2F3']]],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => $offset % 2 === 0 ? 'FFF7FAFC' : 'FFFFFFFF'],
                ],
            ]);
        }

        $lastRow = max(15, 15 + $questions->count());
        $sheet->setAutoFilter("A15:R{$lastRow}");
        $sheet->freezePane('A16');
        $sheet->getStyle("A1:R{$lastRow}")->getAlignment()->setWrapText(true);
        $sheet->getColumnDimension('A')->setWidth(13);
        $sheet->getColumnDimension('B')->setWidth(14);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(19);
        $sheet->getColumnDimension('E')->setWidth(44);
        $sheet->getColumnDimension('F')->setWidth(28);
        foreach (range('G', 'R') as $column) {
            $sheet->getColumnDimension($column)->setWidth(in_array($column, ['H', 'J', 'L', 'N', 'P'], true) ? 25 : 22);
        }
    }

    private function applySectionStyle(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FF1F4E78']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD9EAF7']],
        ]);
    }

    /** @param string|array{0:int, 1:int} $coordinate */
    private function setText(Worksheet $sheet, string|array $coordinate, string $value): void
    {
        $sheet->setCellValueExplicit($coordinate, $value, DataType::TYPE_STRING);
    }

    /** @param array<int, string> $usedNames */
    private function sheetName(string $title, int $quizId, array $usedNames): string
    {
        $base = preg_replace('/[\\\\\/\?\*\[\]:]+/u', ' ', trim($title)) ?? '';
        $base = trim((string) preg_replace('/\s+/u', ' ', $base), " '");
        $base = $base !== '' ? $base : 'Quiz '.$quizId;
        $name = mb_substr($base, 0, 31);

        if (! in_array($name, $usedNames, true)) {
            return $name;
        }

        $suffix = ' - '.$quizId;
        $name = mb_substr($base, 0, 31 - mb_strlen($suffix)).$suffix;
        $counter = 2;
        while (in_array($name, $usedNames, true)) {
            $suffix = ' - '.$quizId.'-'.$counter++;
            $name = mb_substr($base, 0, 31 - mb_strlen($suffix)).$suffix;
        }

        return $name;
    }
}
