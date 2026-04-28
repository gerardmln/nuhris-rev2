<?php

namespace App\Http\Requests;

use App\Models\Department;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnnouncementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $employeeTypes = ['faculty', 'admin_support'];
        $facultyPositions = array_values(config('hris.faculty_positions', []));
        $adminSupportOffices = array_values(config('hris.admin_support_offices', []));
        $facultyRankings = array_values(config('hris.faculty_rankings', []));
        $facultyDepartmentIds = Department::query()->facultySchools()->pluck('id')->all();

        $selectedEmployeeType = (string) $this->input('target_employee_type', '');
        $selectedPosition = (string) $this->input('target_office', '');

        $allowedPositions = match ($selectedEmployeeType) {
            'faculty' => $facultyPositions,
            'admin_support' => $adminSupportOffices,
            default => array_values(array_unique(array_merge($facultyPositions, $adminSupportOffices))),
        };

        $rankPrefix = $this->rankingPrefixForPosition($selectedPosition);
        $allowedRankings = $rankPrefix === ''
            ? $facultyRankings
            : array_values(array_filter($facultyRankings, fn ($ranking) => str_starts_with(mb_strtolower((string) $ranking), $rankPrefix)));

        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'priority' => ['required', 'in:low,medium,high'],
            'target_employee_type' => ['nullable', Rule::in($employeeTypes)],
            'target_office' => ['nullable', Rule::in($allowedPositions)],
            'target_department_id' => ['nullable', Rule::in($facultyDepartmentIds)],
            'target_ranking' => ['nullable', Rule::in($allowedRankings)],
            'expires_at' => ['required', 'date'],
        ];
    }

    private function rankingPrefixForPosition(string $position): string
    {
        $normalized = mb_strtolower(trim($position));

        return match (true) {
            str_contains($normalized, 'assistant professor') => 'assistant professor',
            str_contains($normalized, 'associate professor') => 'associate professor',
            str_contains($normalized, 'full professor') => 'full professor',
            str_contains($normalized, 'instructor') => 'instructor',
            default => '',
        };
    }
}
