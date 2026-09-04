@extends('admin.layout')

@section('title', 'Create Employee')
@section('page_title', '')

@section('content')
    @php
        $pageSubtitle = 'Register an employee using their existing ID';
        $panelTitle = 'Add Existing Employee';
        $panelSubtitle = 'Register an employee using their existing ID';
        $cancelRoute = route('admin.employees.index');
        $showEmployeeIdInput = true;
        $submitLabel = 'Add Existing Employee';
    @endphp

    <div class="mx-auto max-w-5xl py-2 sm:py-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-[0_20px_50px_rgba(15,23,42,0.12)] sm:p-6">
            <div class="mb-5 flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                <div>
                    <h2 class="text-3xl font-extrabold leading-tight text-[#1f2b5d] sm:text-4xl">{{ $panelTitle }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $panelSubtitle }}</p>
                </div>

                <a href="{{ $cancelRoute }}" class="inline-flex h-10 w-10 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round">
                        <path d="M6 6l12 12" />
                        <path d="M18 6 6 18" />
                    </svg>
                </a>
            </div>

            <form method="POST" action="{{ route('admin.employees.store') }}" data-employee-form>
                @csrf
                @include('hr.employees._form')
            </form>
        </div>
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

                if (!employmentTypeSelect || !positionSelect) {
                    return;
                }

                const mode = getMode(employmentTypeSelect.value);
                const options = Array.from(positionSelect.options);

                options.forEach((option) => {
                    const category = (option.dataset.employmentCategory || '').toLowerCase();
                    const value = (option.value || '').trim().toLowerCase();
                    const isPartTimeOption = value === 'part-time faculty';

                    let disabled = false;

                    if (mode === 'part-time-faculty') {
                        disabled = !isPartTimeOption;
                    } else if (mode === 'faculty') {
                        disabled = category === 'asp' || isPartTimeOption;
                    } else if (mode === 'admin-support') {
                        disabled = category === 'faculty' || isPartTimeOption;
                    }

                    option.disabled = disabled;
                    if (option.value !== '') {
                        option.hidden = disabled;
                    }
                });

                if (positionSelect.value && positionSelect.options[positionSelect.selectedIndex]?.disabled) {
                    positionSelect.value = '';
                }
            }

            const form = document.querySelector('[data-employee-form]');

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