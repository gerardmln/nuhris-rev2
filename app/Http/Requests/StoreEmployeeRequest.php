<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
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
        $employmentTypes = config('hris.employment_types', []);
        $facultyPositions = config('hris.faculty_positions', []);
        $aspPositions = config('hris.admin_support_offices', []);
        $allPositions = array_values(array_unique(array_merge($facultyPositions, $aspPositions)));
        $rankings = config('hris.faculty_rankings', []);

        $employmentType = Str::lower((string) $this->input('employment_type'));
        $selectedPosition = Str::lower((string) $this->input('position'));
        $requiresDepartment = Str::contains($selectedPosition, ['professor', 'dean', 'program chair']);
        $allowedRankings = $this->allowedRankingsForPosition($selectedPosition, $rankings);
        $requiresRanking = ! empty($allowedRankings);

        // Scope the allowed positions by the selected employment type so HR
        // cannot save an ASP office when employment_type=Faculty (and vice-versa).
        $allowedPositions = match (true) {
            str_contains($employmentType, 'faculty') => $facultyPositions,
            str_contains($employmentType, 'admin') => $aspPositions,
            default => $allPositions,
        };

        return [
            // Allow HR to provide an existing employee_id (for "Add Existing Employee").
            // When empty/null, the model will auto-generate one on save.
            'employee_id' => ['nullable', 'string', 'max:50', 'unique:employees,employee_id'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:employees,email'],
            'phone' => ['nullable', 'digits:10'],
            'address' => ['nullable', 'string', 'max:255'],
            'department_id' => [Rule::requiredIf($requiresDepartment), 'nullable', 'exists:departments,id'],
            'position' => ['required', Rule::in($allowedPositions)],
            'employment_type' => ['required', Rule::in($employmentTypes)],
            'ranking' => [
                Rule::requiredIf($requiresRanking),
                'nullable',
                Rule::in($requiresRanking ? $allowedRankings : $rankings),
            ],
            'status' => ['nullable', 'in:active'],
            'hire_date' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'position.in' => 'The selected position does not belong to the chosen Employee Type.',
            'ranking.in' => 'The selected Faculty Ranking does not match the selected Position.',
            'phone.digits' => 'Mobile Number must contain exactly 10 digits after +63 (e.g., 9949960496).',
        ];
    }

    /**
     * @param  array<int, string>  $rankings
     * @return array<int, string>
     */
    private function allowedRankingsForPosition(string $selectedPosition, array $rankings): array
    {
        $prefix = match (true) {
            str_contains($selectedPosition, 'assistant professor') => 'assistant professor',
            str_contains($selectedPosition, 'associate professor') => 'associate professor',
            str_contains($selectedPosition, 'full professor') => 'full professor',
            str_contains($selectedPosition, 'instructor') => 'instructor',
            default => '',
        };

        if ($prefix === '') {
            return [];
        }

        return array_values(array_filter($rankings, fn ($ranking) => str_starts_with(Str::lower((string) $ranking), $prefix)));
    }
}
