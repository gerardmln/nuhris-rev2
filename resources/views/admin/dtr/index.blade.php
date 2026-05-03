@extends('admin.layout')

@section('title', 'DTR Management')

@php
    $pageTitle = 'DTR Management';
    $pageHeading = 'DTR Management';
@endphp

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-sm text-slate-500">View biometric attendance records, print DTR, and upload DTR files</p>
        </div>
        <div class="flex items-center gap-2">
            <button data-open-modal="upload-dtr-modal" class="rounded-lg bg-[#00386f] px-4 py-2 text-sm font-semibold text-white hover:bg-[#002f5d] transition">Upload DTR PDF</button>
        </div>
    </div>

    @if (session('unmatched_employees') || session('applied_employees'))
        @php
            $unmatchedList = session('unmatched_employees', []);
            $appliedList = session('applied_employees', []);
            $importStats = session('import_stats', []);
        @endphp
        <div id="dtr-import-result-modal" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/45 p-4 py-6 sm:items-center">
            <div class="w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-2xl">
                <div class="flex items-start justify-between border-b border-slate-300 px-6 py-4">
                    <div>
                        <h3 class="text-2xl font-bold text-[#1f2b5d]">DTR Upload Results</h3>
                        <p class="text-sm text-slate-500">Summary of biometric PDF processing</p>
                    </div>
                    <button type="button" data-close-dtr-result-modal class="text-4xl leading-none text-slate-500 hover:text-slate-700">&times;</button>
                </div>
                <div class="px-6 py-5">
                    <div class="grid grid-cols-3 gap-3 mb-5">
                        <div class="rounded-lg bg-emerald-50 border border-emerald-200 p-3 text-center"><p class="text-3xl font-extrabold text-emerald-700">{{ $importStats['imported'] ?? 0 }}</p><p class="text-xs font-semibold text-emerald-700">Imported</p></div>
                        <div class="rounded-lg bg-amber-50 border border-amber-200 p-3 text-center"><p class="text-3xl font-extrabold text-amber-700">{{ $importStats['skipped'] ?? 0 }}</p><p class="text-xs font-semibold text-amber-700">Skipped</p></div>
                        <div class="rounded-lg bg-blue-50 border border-blue-200 p-3 text-center"><p class="text-3xl font-extrabold text-blue-700">{{ $importStats['total_records'] ?? 0 }}</p><p class="text-xs font-semibold text-blue-700">Total Records</p></div>
                    </div>
                    @if (count($unmatchedList) > 0)
                        <div class="mb-4">
                            <h4 class="text-sm font-bold text-red-700 mb-2">Employees NOT Found in HRIS ({{ count($unmatchedList) }})</h4>
                            <p class="text-xs text-slate-500 mb-2">These employees from the DTR were not matched. Their attendance was <strong>NOT</strong> applied.</p>
                            <div class="max-h-48 overflow-y-auto rounded-lg border border-red-200 bg-red-50">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-red-100 text-xs uppercase text-red-800"><tr><th class="px-3 py-2 text-left">#</th><th class="px-3 py-2 text-left">Employee (ID)</th></tr></thead>
                                    <tbody class="divide-y divide-red-100">
                                        @foreach ($unmatchedList as $idx => $label)
                                            <tr><td class="px-3 py-1.5 text-red-700">{{ $idx + 1 }}</td><td class="px-3 py-1.5 text-red-800 font-medium">{{ $label }}</td></tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                    @if (count($appliedList) > 0)
                        <div>
                            <h4 class="text-sm font-bold text-emerald-700 mb-2">Successfully Applied ({{ count($appliedList) }})</h4>
                            <div class="max-h-40 overflow-y-auto rounded-lg border border-emerald-200 bg-emerald-50">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-emerald-100 text-xs uppercase text-emerald-800"><tr><th class="px-3 py-2 text-left">#</th><th class="px-3 py-2 text-left">Employee (ID)</th></tr></thead>
                                    <tbody class="divide-y divide-emerald-100">
                                        @foreach ($appliedList as $idx => $label)
                                            <tr><td class="px-3 py-1.5 text-emerald-700">{{ $idx + 1 }}</td><td class="px-3 py-1.5 text-emerald-800">{{ $label }}</td></tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="border-t border-slate-200 px-6 py-4 flex justify-end"><button type="button" data-close-dtr-result-modal class="rounded-md bg-[#00386f] px-6 py-2 text-sm font-semibold text-white hover:bg-[#002f5d]">Close</button></div>
            </div>
        </div>
    @endif

    @if ($employee)
        <article class="rounded-xl border border-slate-300 bg-[#cfe1f5] p-4 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-[#1f2b5d]">{{ $employee?->full_name ?? 'Employee' }}</h1>
                    <p class="text-sm text-slate-600">{{ $employee?->department?->name ?? 'Unassigned' }} | Period: {{ $dateFrom->format('F Y') }}</p>
                    <p class="text-xs text-slate-600">Approved Schedule: {{ $scheduleSummary ?? 'N/A' }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <form method="GET" action="{{ route('admin.dtr.index') }}" class="flex items-center gap-2">
                        <input type="hidden" name="employee_id" value="{{ $employee?->id }}">
                        <select name="period" onchange="this.form.querySelector('[name=month]').value=this.value.split('-')[0]; this.form.querySelector('[name=year]').value=this.value.split('-')[1]; this.form.submit();" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-400 focus:outline-none">
                            @foreach ($periods as $period)
                                <option value="{{ $period['month'] }}-{{ $period['year'] }}" {{ $period['selected'] ? 'selected' : '' }}>{{ $period['label'] }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" name="month" value="{{ $selectedMonth }}">
                        <input type="hidden" name="year" value="{{ $selectedYear }}">
                    </form>
                </div>
            </div>
        </article>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
                <p class="text-xs text-slate-500">Present Days</p>
                <p class="text-3xl font-extrabold text-emerald-700">{{ $summary['present_days'] ?? 0 }}</p>
            </article>
            <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
                <p class="text-xs text-slate-500">Absent Days</p>
                <p class="text-3xl font-extrabold text-red-600">{{ $summary['absent_days'] ?? 0 }}</p>
            </article>
            <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
                <p class="text-xs text-slate-500">Total Tardiness</p>
                <p class="text-3xl font-extrabold text-amber-600">{{ $summary['tardiness_total'] ?? 0 }} min</p>
            </article>
            <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
                <p class="text-xs text-slate-500">Total Undertime</p>
                <p class="text-3xl font-extrabold text-violet-600">{{ $summary['undertime_total'] ?? 0 }} min</p>
            </article>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.dtr.export-pdf', ['employee_id' => $employee?->id, 'month' => $selectedMonth, 'year' => $selectedYear]) }}" class="inline-flex items-center gap-2 rounded-lg border border-red-300 bg-white px-4 py-2 text-sm font-semibold text-red-700 shadow-sm transition hover:bg-red-50">Export PDF</a>
            <a href="{{ route('admin.dtr.export-excel', ['employee_id' => $employee?->id, 'month' => $selectedMonth, 'year' => $selectedYear]) }}" class="inline-flex items-center gap-2 rounded-lg border border-emerald-300 bg-white px-4 py-2 text-sm font-semibold text-emerald-700 shadow-sm transition hover:bg-emerald-50">Export Excel</a>
            <a href="{{ route('admin.dtr.index', ['month' => $selectedMonth, 'year' => $selectedYear]) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">Back to All Employees</a>
        </div>

        <article class="overflow-x-auto rounded-xl border border-slate-300 bg-white p-4 shadow-sm" data-testid="admin-dtr-table-container">
            <table class="min-w-full text-left text-sm" data-testid="admin-dtr-table">
                <thead class="bg-slate-100 text-slate-600">
                    <tr>
                        <th class="px-3 py-2">Date</th>
                        <th class="px-3 py-2">Day</th>
                        <th class="px-3 py-2">Time In</th>
                        <th class="px-3 py-2">Time Out</th>
                        <th class="px-3 py-2">Tardiness</th>
                        <th class="px-3 py-2">Undertime</th>
                        <th class="px-3 py-2">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($records as $record)
                        <tr class="{{ $record['status'] === 'Weekend' ? 'bg-slate-50 text-slate-400' : ($record['status'] === 'Non-working day' ? 'bg-amber-50/40' : ($record['status'] === 'Not Present' ? 'bg-red-50/40' : '')) }}">
                            <td class="px-3 py-2">{{ $record['date'] }}</td>
                            <td class="px-3 py-2">{{ $record['day'] }}</td>
                            <td class="px-3 py-2">{{ $record['time_in'] }}</td>
                            <td class="px-3 py-2">{{ $record['time_out'] }}</td>
                            <td class="px-3 py-2">{{ $record['tardiness_minutes'] ? $record['tardiness_minutes'].' min' : '-' }}</td>
                            <td class="px-3 py-2">{{ $record['undertime_minutes'] ? $record['undertime_minutes'].' min' : '-' }}</td>
                            <td class="px-3 py-2">
                                @if ($record['status'] === 'Present')
                                    <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700">Present</span>
                                @elseif ($record['status'] === 'Not Present')
                                    <span class="inline-flex rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700">Not Present</span>
                                @elseif ($record['status'] === 'Non-working day')
                                    <span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700">Non-working day</span>
                                @elseif ($record['status'] === 'Weekend')
                                    <span class="inline-flex rounded-full bg-slate-200 px-2 py-0.5 text-xs font-semibold text-slate-500">Weekend</span>
                                @else
                                    <span class="text-slate-400">{{ $record['status'] }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-8 text-center text-sm text-slate-500">No DTR records found for this period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </article>
    @endif

    <article class="rounded-xl border border-slate-300 bg-white p-3 shadow-sm {{ $employee ? 'mt-6' : 'mt-4' }}">
        <div class="grid grid-cols-1 gap-2 md:grid-cols-4">
            <form method="GET" action="{{ route('admin.dtr.index') }}" class="md:col-span-4">
                <div class="flex flex-col gap-2 lg:flex-row">
                    <input type="text" name="search" value="{{ request('search') ?? '' }}" placeholder="Search by name, email, ID, or department..." class="flex-1 rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none">
                    <select name="period" onchange="this.form.querySelector('[name=month]').value=this.value.split('-')[0]; this.form.querySelector('[name=year]').value=this.value.split('-')[1]; this.form.submit();" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm focus:border-blue-400 focus:outline-none lg:w-56">
                        @foreach ($periods as $period)
                            <option value="{{ $period['month'] }}-{{ $period['year'] }}" @selected($period['selected'])>{{ $period['label'] }}</option>
                        @endforeach
                    </select>
                    <select name="employee_class" onchange="this.form.submit()" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm focus:border-blue-400 focus:outline-none lg:min-w-[14rem]">
                        <option value="all">All Employee Types</option>
                        <option value="regular" @selected(request('employee_class') === 'regular')>Regular Employees</option>
                        <option value="irregular" @selected(request('employee_class') === 'irregular')>Non-Regular Employee</option>
                    </select>
                    <input type="hidden" name="month" value="{{ $selectedMonth }}">
                    <input type="hidden" name="year" value="{{ $selectedYear }}">
                </div>
            </form>
        </div>
    </article>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3 mt-4">
        @forelse($employeeCards as $card)
            <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm transition hover:shadow-md">
                <div class="mb-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-[#00386f] text-sm font-semibold text-white">{{ $card['initials'] }}</span>
                        <div>
                            <p class="text-xl font-bold text-[#1f2b5d]">{{ $card['name'] }}</p>
                            <p class="text-sm text-slate-500">{{ $card['department'] }}</p>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-lg bg-emerald-100 p-3 text-center">
                        <p class="text-4xl font-extrabold text-emerald-700">{{ $card['present'] }}</p>
                        <p class="text-xs font-semibold text-emerald-700">Present</p>
                    </div>
                    <div class="rounded-lg bg-amber-100 p-3 text-center">
                        <p class="text-4xl font-extrabold text-amber-700">{{ $card['tardiness'] }}</p>
                        <p class="text-xs font-semibold text-amber-700">Tardiness(min)</p>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-1 text-[11px] font-semibold text-red-800">Absences: {{ $card['absences'] }}</span>
                </div>
                @unless($card['has_data'])
                    <p class="mt-3 rounded-md border border-dashed border-slate-300 bg-slate-50 px-2 py-1 text-center text-[11px] text-slate-500">No biometric data yet for this period.</p>
                @endunless
                <p class="mt-3 text-xs text-slate-500">Schedule: {{ $card['schedule_summary'] }}</p>
                <a href="{{ route('admin.dtr.index', ['employee_id' => $card['id'], 'month' => $selectedMonth, 'year' => $selectedYear]) }}" class="mt-3 block w-full rounded-md bg-[#00386f] px-3 py-2 text-center text-sm font-semibold text-white hover:bg-[#002f5d] transition">View DTR</a>
            </article>
        @empty
            <div class="col-span-full rounded-xl border border-slate-200 bg-white p-8 text-center">
                <p class="text-lg font-semibold text-slate-500">No employees found</p>
                <p class="text-sm text-slate-400">Try adjusting your search or filter criteria.</p>
            </div>
        @endforelse
    </div>

    <div id="upload-dtr-modal" class="fixed inset-0 z-50 hidden items-start justify-center overflow-y-auto bg-black/45 p-4 py-6 sm:items-center">
        <div class="w-full max-w-3xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-2xl">
            <div class="flex items-start justify-between px-7 py-6">
                <div>
                    <h3 class="text-3xl font-bold text-[#1f2b8b]">Upload DTR PDF</h3>
                    <p class="mt-1 text-sm text-slate-500">Upload a biometric Timesheet Report for DTR processing.</p>
                </div>
                <button type="button" data-close-modal class="text-4xl leading-none text-slate-500 hover:text-slate-700">&times;</button>
            </div>
            <form id="upload-dtr-form" class="px-7 pb-7 js-loading-form" method="POST" action="{{ route('admin.dtr.upload') }}" enctype="multipart/form-data">
                @csrf
                <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800 mb-4">
                    <p class="font-semibold">Employee ID Format Flexible</p>
                    <p class="mt-1 text-blue-700">Accepts IDs with and without dashes. Only registered employees will be matched.</p>
                </div>
                <label class="mb-2 block text-sm font-medium text-slate-700">DTR file (.pdf)</label>
                <label for="biometrics_file" id="dtr-drop-zone" class="block cursor-pointer rounded-lg border-2 border-dashed border-slate-300 p-8 text-center transition hover:border-[#00386f] hover:bg-blue-50/30">
                    <input id="biometrics_file" name="biometrics_file" type="file" accept="application/pdf,.pdf" class="sr-only" required>
                    <div class="mx-auto inline-flex h-14 w-14 items-center justify-center text-slate-400">
                        <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4" /><path d="m7 9 5-5 5 5" /><path d="M4 16v3a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-3" /></svg>
                    </div>
                    <p id="dtr-file-name" class="mt-3 text-lg text-slate-500">Click to upload or drag and drop</p>
                    <p id="dtr-file-size" class="text-sm text-slate-400">PDF only · up to 10 MB</p>
                </label>
                <input type="hidden" name="month" value="{{ $selectedMonth }}">
                <input type="hidden" name="year" value="{{ $selectedYear }}">
                @if ($employee)
                    <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                @endif
                <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
                    <button type="button" data-close-modal class="rounded-md border border-slate-400 px-8 py-2.5 text-lg font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-md bg-[#00386f] px-8 py-2.5 text-lg font-semibold text-white hover:bg-[#002f5d]">Process PDF</button>
                </div>
            </form>
        </div>
    </div>

    <div id="loading-overlay" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/50">
        <div class="rounded-2xl bg-white px-8 py-6 shadow-2xl text-center">
            <svg class="mx-auto h-10 w-10 animate-spin text-[#00386f]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="mt-3 text-lg font-semibold text-[#1f2b5d]">Processing...</p>
            <p class="text-sm text-slate-500">Please wait, this may take a moment.</p>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-open-modal]').forEach((button) => {
            button.addEventListener('click', () => {
                const modal = document.getElementById(button.dataset.openModal);
                if (modal) {
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    document.body.classList.add('overflow-hidden');
                }
            });
        });

        document.querySelectorAll('[data-close-modal]').forEach((button) => {
            button.addEventListener('click', () => {
                const modal = button.closest('.fixed.inset-0');
                if (modal) {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    document.body.classList.remove('overflow-hidden');
                }
            });
        });

        document.querySelectorAll('[data-close-dtr-result-modal]').forEach((button) => {
            button.addEventListener('click', () => {
                const modal = document.getElementById('dtr-import-result-modal');
                if (modal) {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    document.body.classList.remove('overflow-hidden');
                }
            });
        });

        const resultModal = document.getElementById('dtr-import-result-modal');
        if (resultModal) {
            document.body.classList.add('overflow-hidden');
            resultModal.addEventListener('click', (event) => {
                if (event.target === resultModal) {
                    resultModal.classList.add('hidden');
                    resultModal.classList.remove('flex');
                    document.body.classList.remove('overflow-hidden');
                }
            });
        }

        const uploadModal = document.getElementById('upload-dtr-modal');
        if (uploadModal) {
            uploadModal.addEventListener('click', (event) => {
                if (event.target === uploadModal) {
                    uploadModal.classList.add('hidden');
                    uploadModal.classList.remove('flex');
                    document.body.classList.remove('overflow-hidden');
                }
            });
        }

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                [uploadModal, resultModal].forEach((modal) => {
                    if (modal && !modal.classList.contains('hidden')) {
                        modal.classList.add('hidden');
                        modal.classList.remove('flex');
                        document.body.classList.remove('overflow-hidden');
                    }
                });
            }
        });

        const fileInput = document.getElementById('biometrics_file');
        const fileNameDisplay = document.getElementById('dtr-file-name');
        const fileSizeDisplay = document.getElementById('dtr-file-size');
        const fileDropZone = document.getElementById('dtr-drop-zone');

        if (fileInput) {
            fileInput.addEventListener('change', function () {
                if (this.files.length > 0) {
                    const file = this.files[0];
                    const sizeKB = (file.size / 1024).toFixed(1);
                    const sizeMB = (file.size / 1024 / 1024).toFixed(2);
                    const sizeText = file.size > 1048576 ? `${sizeMB} MB` : `${sizeKB} KB`;
                    fileNameDisplay.textContent = file.name;
                    fileNameDisplay.classList.remove('text-slate-500');

                    if (file.size > 10485760) {
                        fileNameDisplay.classList.add('text-red-600', 'font-semibold');
                        fileSizeDisplay.textContent = `${sizeText} — TOO LARGE (max 10 MB)`;
                        fileDropZone.classList.add('border-red-400', 'bg-red-50/30');
                        fileDropZone.classList.remove('border-slate-300', 'border-emerald-400', 'bg-emerald-50/30');
                    } else {
                        fileNameDisplay.classList.add('text-emerald-700', 'font-semibold');
                        fileSizeDisplay.textContent = sizeText;
                        fileDropZone.classList.add('border-emerald-400', 'bg-emerald-50/30');
                        fileDropZone.classList.remove('border-slate-300', 'border-red-400', 'bg-red-50/30');
                    }
                }
            });
        }

        const loadingOverlay = document.getElementById('loading-overlay');
        document.querySelectorAll('.js-loading-form').forEach((form) => {
            form.addEventListener('submit', function () {
                if (loadingOverlay) {
                    loadingOverlay.classList.remove('hidden');
                    loadingOverlay.classList.add('flex');
                }
            });
        });
    </script>
@endpush
