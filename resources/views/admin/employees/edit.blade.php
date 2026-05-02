@extends('admin.layout')

@section('title', 'Edit Employee')
@section('page_title', 'Edit Employee')

@section('content')
    @php
        $cancelRoute = route('admin.employees.index');
    @endphp

    <div class="p-6">
        <form method="POST" action="{{ route('admin.employees.update', $employee) }}">
            @csrf
            @method('PUT')

            @include('hr.employees._form')
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            function getMode(value) {
                const normalized = (value || '').trim().toLowerCase();

                if (normalized === 'part-time faculty') {
                    return 'part-time-faculty';
                }

                if (normalized.includes('admin support personnel')) {
                    return 'admin-support';
                }

                if (normalized.includes('faculty')) {
                    return 'faculty';
                }

                return 'all';
            }

            function updatePositionOptions(form) {
                const employmentTypeSelect = form.querySelector('[data-employee-control="employment_type"]');
                const positionSelect = form.querySelector('[data-employee-control="position"]');
                const departmentField = form.querySelector('[data-employee-field="department"]');
                const departmentSelect = form.querySelector('[data-employee-control="department"]');

                if (!employmentTypeSelect || !positionSelect) {
                    return;
                }

                const mode = getMode(employmentTypeSelect.value);
                const options = Array.from(positionSelect.options);
                const shouldHideDepartment = !employmentTypeSelect.value && !positionSelect.value;

                if (departmentField) {
                    departmentField.classList.toggle('hidden', shouldHideDepartment);
                }

                if (departmentSelect) {
                    departmentSelect.disabled = shouldHideDepartment;
                    departmentSelect.required = !shouldHideDepartment && mode === 'part-time-faculty';
                }

                options.forEach((option) => {
                    const category = (option.dataset.employmentCategory || '').toLowerCase();
                    const value = (option.value || '').trim().toLowerCase();
                    const isPartTimeOption = /part\s*[- ]\s*time/.test(value);

                    let disabled = false;

                    if (mode === 'part-time-faculty') {
                        disabled = !isPartTimeOption;
                    } else if (mode === 'faculty') {
                        disabled = category === 'asp' || isPartTimeOption;
                    } else if (mode === 'admin-support') {
                        disabled = category === 'faculty' || isPartTimeOption;
                    }

                    option.disabled = disabled;
                });

                if (positionSelect.value && positionSelect.options[positionSelect.selectedIndex]?.disabled) {
                    positionSelect.value = '';
                }
            }

            const form = document.querySelector('form[action="{{ route('admin.employees.update', $employee) }}"]');

            if (!form) {
                return;
            }

            const employmentTypeSelect = form.querySelector('[data-employee-control="employment_type"]');
            const positionSelect = form.querySelector('[data-employee-control="position"]');

            if (!employmentTypeSelect || !positionSelect) {
                return;
            }

            employmentTypeSelect.addEventListener('change', () => updatePositionOptions(form));
            positionSelect.addEventListener('change', () => updatePositionOptions(form));
            positionSelect.addEventListener('focus', () => updatePositionOptions(form));
            positionSelect.addEventListener('mousedown', () => updatePositionOptions(form));
            updatePositionOptions(form);
        })();
    </script>
@endpush
