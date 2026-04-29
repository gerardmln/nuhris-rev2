<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\WfhMonitoringSubmission;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use RuntimeException;

class WfhMonitoringService
{
    public function parseWorkbook(UploadedFile $file, string $targetDate): array
    {
        try {
            $spreadsheet = SpreadsheetIOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, false);
        } catch (\Throwable $exception) {
            throw new RuntimeException('Unable to read the Excel file: '.$exception->getMessage());
        }

        if (empty($rows) || count($rows) < 2) {
            throw new RuntimeException('The Excel file does not contain any data rows.');
        }

        $headerRowIndex = null;
        $header = [];

        foreach ($rows as $index => $row) {
            $normalizedRow = array_map(
                fn ($cell) => strtolower(trim((string) $cell)),
                $row
            );

            if ($this->looksLikeHeaderRow($normalizedRow)) {
                $headerRowIndex = $index;
                $header = $normalizedRow;
                break;
            }
        }

        if ($headerRowIndex === null) {
            $headerRowIndex = 0;
            $header = array_map(
                fn ($cell) => strtolower(trim((string) $cell)),
                $rows[0]
            );
        }

        $columnIndex = $this->resolveColumns($header);
        $dataRows = array_slice($rows, $headerRowIndex + 1);

        foreach ($dataRows as $row) {
            if ($this->isEmptyRow($row)) {
                continue;
            }

            $rowDate = null;

            if ($columnIndex['date'] !== null) {
                $rowDate = $this->parseExcelDate($row[$columnIndex['date']] ?? null);
            }

            if (! $rowDate) {
                foreach ($row as $cell) {
                    $rowDate = $this->parseExcelDate($cell);
                    if ($rowDate) {
                        break;
                    }
                }
            }

            if ($rowDate !== $targetDate) {
                continue;
            }

            $timeIn = $columnIndex['time_in'] !== null ? $this->normalizeTime($row[$columnIndex['time_in']] ?? null) : null;
            $timeOut = $columnIndex['time_out'] !== null ? $this->normalizeTime($row[$columnIndex['time_out']] ?? null) : null;

            if (! $timeIn || ! $timeOut) {
                throw new RuntimeException('The workbook row for '.$targetDate.' must include both Time In and Time Out values.');
            }

            return [
                'matched_date' => $rowDate,
                'time_in' => $timeIn,
                'time_out' => $timeOut,
            ];
        }

        throw new RuntimeException('Could not find a row in the workbook for '.$targetDate.'. Make sure the sheet includes Date, Time In, and Time Out columns.');
    }

    public function buildAttendancePayload(Employee $employee, WfhMonitoringSubmission $submission, EmployeeScheduleService $scheduleService): array
    {
        $referenceDate = Carbon::parse($submission->wfh_date);
        $timeIn = $submission->time_in?->format('H:i:s');
        $timeOut = $submission->time_out?->format('H:i:s');
        $evaluation = $scheduleService->evaluateDailyRecord($employee, $referenceDate, $timeIn, $timeOut);

        $payload = [
            'time_in' => $timeIn,
            'time_out' => $timeOut,
            'scheduled_time_in' => $evaluation['scheduled_time_in'],
            'scheduled_time_out' => $evaluation['scheduled_time_out'],
            'tardiness_minutes' => $evaluation['tardiness_minutes'],
            'undertime_minutes' => $evaluation['undertime_minutes'],
            'overtime_minutes' => $evaluation['overtime_minutes'],
            'schedule_status' => 'validated',
            'schedule_notes' => 'WFH approved via monitoring sheet',
            'status' => 'present',
        ];

        if (in_array($evaluation['schedule_status'], ['no_schedule', 'non_working_day'], true)) {
            $payload['scheduled_time_in'] = null;
            $payload['scheduled_time_out'] = null;
            $payload['tardiness_minutes'] = 0;
            $payload['undertime_minutes'] = 0;
            $payload['overtime_minutes'] = 0;
        }

        return $payload;
    }

    private function resolveColumns(array $header): array
    {
        $find = function (array $candidates) use ($header): ?int {
            foreach ($header as $idx => $label) {
                if (in_array($label, $candidates, true)) {
                    return $idx;
                }
            }

            return null;
        };

        return [
            'date' => $find(['date', 'wfh date', 'monitoring date', 'work date']),
            'time_in' => $find(['time in', 'time-in', 'in', 'clock in', 'login']),
            'time_out' => $find(['time out', 'time-out', 'out', 'clock out', 'logout']),
        ];
    }

    private function looksLikeHeaderRow(array $row): bool
    {
        $values = array_filter($row, fn ($cell) => $cell !== '');

        if (empty($values)) {
            return false;
        }

        $hasDate = in_array('date', $values, true) || in_array('wfh date', $values, true) || in_array('monitoring date', $values, true) || in_array('work date', $values, true);
        $hasTime = in_array('time in', $values, true) || in_array('time out', $values, true) || in_array('time-in', $values, true) || in_array('time-out', $values, true);

        return $hasDate && $hasTime;
    }

    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $cell) {
            if ($cell !== null && trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    private function parseExcelDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                $datetime = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value);
                return $datetime->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeTime(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value)->format('H:i:s');
            } catch (\Throwable) {
                return null;
            }
        }

        if (preg_match('/(\d{1,2}):(\d{2})\s*(AM|PM)/i', $value, $matches)) {
            $hour = (int) $matches[1];
            $minute = $matches[2];
            $period = strtoupper($matches[3]);

            if ($period === 'PM' && $hour !== 12) {
                $hour += 12;
            } elseif ($period === 'AM' && $hour === 12) {
                $hour = 0;
            }

            return sprintf('%02d:%s:00', $hour, $minute);
        }

        try {
            return Carbon::parse($value)->format('H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }
}
