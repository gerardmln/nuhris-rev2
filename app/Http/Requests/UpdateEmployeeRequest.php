<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
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
        $employeeId = $this->route('employee')?->id;
        $employmentTypes = config('hris.employment_types', []);
        $facultyPositions = config('hris.faculty_positions', []);
        $aspPositions = config('hris.admin_support_offices', []);
        $allPositions = array_values(array_unique(array_merge($facultyPositions, $aspPositions)));
        $rankings = config('hris.faculty_rankings', []);

        $employmentType = Str::lower((string) $this->input('employment_type'));
        $selectedPosition = Str::lower((string) $this->input('position'));
        $requiresDepartment = Str::contains($selectedPosition, ['professor', 'dean', 'program chair']);
        $requiresRanking = Str::contains($selectedPosition, 'professor');

        $allowedPositions = match (true) {
            str_contains($employmentType, 'faculty') => $facultyPositions,
            str_contains($employmentType, 'admin') => $aspPositions,
            default => $allPositions,
        };

        return [
            'employee_id' => ['required', 'string', 'max:50', Rule::unique('employees', 'employee_id')->ignore($employeeId)],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('employees', 'email')->ignore($employeeId)],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'department_id' => [Rule::requiredIf($requiresDepartment), 'nullable', 'exists:departments,id'],
            'position' => ['required', Rule::in($allowedPositions)],
            'employment_type' => ['required', Rule::in($employmentTypes)],
            'ranking' => [
                Rule::requiredIf($requiresRanking),
                'nullable',
                Rule::in($rankings),
            ],
            'status' => ['required', 'in:active,on_leave,resigned,terminated'],
            'hire_date' => ['nullable', 'date'],
            'official_time_in' => ['nullable', 'date_format:H:i'],
            'official_time_out' => ['nullable', 'date_format:H:i'],
            'resume_last_updated_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'position.in' => 'The selected position does not belong to the chosen Employee Type.',
        ];
    }
}
