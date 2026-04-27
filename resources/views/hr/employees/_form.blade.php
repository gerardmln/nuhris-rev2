@php
    $isEdit = isset($employee);
    $facultyPositions = $facultyPositions ?? [];
    $aspPositions = $aspPositions ?? [];
    $rawPhone = (string) old('phone', $employee->phone ?? '');
    $phoneDigits = preg_replace('/\D+/', '', $rawPhone) ?? '';

    if (str_starts_with($phoneDigits, '63') && strlen($phoneDigits) === 12) {
        $phoneDigits = substr($phoneDigits, 2);
    } elseif (str_starts_with($phoneDigits, '0') && strlen($phoneDigits) === 11) {
        $phoneDigits = substr($phoneDigits, 1);
    }
@endphp

<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    @if ($isEdit)
        <div>
            <label for="employee_id" class="mb-1 block text-sm font-semibold text-slate-700">Employee ID *</label>
            <input id="employee_id" name="employee_id" type="text" value="{{ old('employee_id', $employee->employee_id ?? '') }}" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none" required>
            @error('employee_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
    @else
        <div class="rounded-md border border-dashed border-slate-300 bg-slate-50 px-3 py-3 md:col-span-2">
            <p class="text-sm font-semibold text-slate-700">Employee ID</p>
            <p class="text-sm text-slate-500">Automatically generated on save using the format <span class="font-medium text-slate-700">YYYY-001</span>.</p>
        </div>
    @endif

    <div>
        <label for="email" class="mb-1 block text-sm font-semibold text-slate-700">Email *</label>
        <input id="email" name="email" type="email" value="{{ old('email', $employee->email ?? '') }}" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none" required>
        @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="first_name" class="mb-1 block text-sm font-semibold text-slate-700">First Name *</label>
        <input id="first_name" name="first_name" type="text" value="{{ old('first_name', $employee->first_name ?? '') }}" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none" required>
        @error('first_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="last_name" class="mb-1 block text-sm font-semibold text-slate-700">Last Name *</label>
        <input id="last_name" name="last_name" type="text" value="{{ old('last_name', $employee->last_name ?? '') }}" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none" required>
        @error('last_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="phone" class="mb-1 block text-sm font-semibold text-slate-700">Mobile Number</label>
        <div class="flex w-full overflow-hidden rounded-md border border-slate-300 focus-within:border-blue-400">
            <span class="inline-flex items-center border-r border-slate-300 bg-slate-50 px-3 text-sm font-semibold text-slate-600">+63</span>
            <input
                id="phone"
                name="phone"
                type="tel"
                value="{{ $phoneDigits }}"
                inputmode="numeric"
                pattern="\d{10}"
                maxlength="10"
                autocomplete="tel-national"
                placeholder="9987654321"
                oninput="this.value = this.value.replace(/\D/g, '').slice(0, 10);"
                class="w-full border-0 px-3 py-2 text-sm focus:outline-none"
            >
        </div>
        <p class="mt-1 text-xs text-slate-500">Enter 10 digits only. Example: +63 998 765 4321</p>
        @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="employment_type" class="mb-1 block text-sm font-semibold text-slate-700">Employee Type *</label>
        <select id="employment_type" name="employment_type" data-employee-control="employment_type" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none" required>
            <option value="">Select Type</option>
            @foreach ($employmentTypes as $type)
                <option value="{{ $type }}" @selected(old('employment_type', $employee->employment_type ?? '') === $type)>
                    {{ $type }}
                </option>
            @endforeach
        </select>
        @error('employment_type')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="position" class="mb-1 block text-sm font-semibold text-slate-700">Position *</label>
        <select id="position" name="position" data-employee-control="position" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none" required>
            <option value="">Select Position / Office</option>
            @foreach ($facultyPositions as $position)
                <option value="{{ $position }}" data-employment-category="faculty" @selected(old('position', $employee->position ?? '') === $position)>
                    {{ $position }}
                </option>
            @endforeach
            @foreach ($aspPositions as $position)
                <option value="{{ $position }}" data-employment-category="asp" @selected(old('position', $employee->position ?? '') === $position)>
                    {{ $position }}
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-slate-500">Options are filtered by the selected Employee Type.</p>
        @error('position')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div data-employee-field="department">
        <label for="department_id" class="mb-1 block text-sm font-semibold text-slate-700">Department *</label>
        <select id="department_id" name="department_id" data-employee-control="department" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none">
            <option value="">Select Department</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}" @selected(old('department_id', $employee->department_id ?? '') == $department->id)>
                    {{ $department->name }}
                </option>
            @endforeach
        </select>
        @error('department_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div data-employee-field="ranking">
        <label for="ranking" class="mb-1 block text-sm font-semibold text-slate-700">Faculty Ranking</label>
        <select id="ranking" name="ranking" data-employee-control="ranking" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none">
            <option value="">N/A</option>
            @foreach ($facultyRankings as $ranking)
                <option value="{{ $ranking }}" @selected(old('ranking', $employee->ranking ?? '') === $ranking)>
                    {{ $ranking }}
                </option>
            @endforeach
        </select>
        @error('ranking')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="hire_date" class="mb-1 block text-sm font-semibold text-slate-700">Hire Date</label>
        <input id="hire_date" name="hire_date" type="date" value="{{ old('hire_date', isset($employee->hire_date) ? $employee->hire_date?->format('Y-m-d') : '') }}" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none">
        @error('hire_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="official_time_in" class="mb-1 block text-sm font-semibold text-slate-700">Official Time In</label>
        <input id="official_time_in" name="official_time_in" type="time" value="{{ old('official_time_in', isset($employee->official_time_in) ? $employee->official_time_in?->format('H:i') : '') }}" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none">
        @error('official_time_in')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="official_time_out" class="mb-1 block text-sm font-semibold text-slate-700">Official Time Out</label>
        <input id="official_time_out" name="official_time_out" type="time" value="{{ old('official_time_out', isset($employee->official_time_out) ? $employee->official_time_out?->format('H:i') : '') }}" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none">
        @error('official_time_out')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="resume_last_updated_at" class="mb-1 block text-sm font-semibold text-slate-700">Resume Last Updated</label>
        <input id="resume_last_updated_at" name="resume_last_updated_at" type="date" value="{{ old('resume_last_updated_at', isset($employee->resume_last_updated_at) ? $employee->resume_last_updated_at?->format('Y-m-d') : '') }}" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none">
        @error('resume_last_updated_at')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
</div>

@unless ($isEdit)
    <div class="mt-6 rounded-md border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
        <p class="font-semibold">Login account will be auto-created</p>
        <p class="mt-1 text-blue-700">
            After saving, NU HRIS will generate a temporary password for this employee and display it here so you can share it with them.
        </p>
    </div>
@endunless

<div class="mt-6 flex items-center justify-end gap-3">
    <a href="{{ route('employees.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
    <button type="submit" class="rounded-md bg-[#00386f] px-4 py-2 text-sm font-semibold text-white hover:bg-[#002f5d]">
        {{ $isEdit ? 'Update Employee' : 'Create Employee' }}
    </button>
</div>
