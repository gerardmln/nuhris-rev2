@extends('admin.layout')

@php
    $pageTitle = 'Leave Management';
    $pageHeading = 'Leave Management';
@endphp

@section('title', 'Leave Management')

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-sm text-slate-500">Import leave applications (.xlsx) and view employee leave data</p>
        </div>
        <div class="flex items-center gap-2">
            <button
                type="button"
                onclick="if(confirm('Reset the used leave balances for all employees? This will set all used leave counters to 0 (VL, SL, EL). Leave credits will remain intact.')) { document.getElementById('reset-all-leaves-form').submit(); }"
                class="rounded-lg border border-red-300 bg-red-50 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-100"
            >
                Reset All Used Leave
            </button>
            <button data-open-modal="upload-leaves-modal" class="rounded-lg bg-[#00386f] px-4 py-2 text-sm font-semibold text-white hover:bg-[#002f5d]">Upload Leave File</button>
        </div>
    </div>

    <form id="reset-all-leaves-form" action="{{ route('admin.leave.clear-all') }}" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>

    @if (session('unmatched_employees') || session('applied_employees'))
        @php
            $unmatchedList = session('unmatched_employees', []);
            $appliedList = session('applied_employees', []);
            $importStats = session('import_stats', []);
        @endphp
        <div id="leave-import-result-modal" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/45 p-4 py-6 sm:items-center">
            <div class="w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-2xl">
                <div class="flex items-start justify-between border-b border-slate-300 px-6 py-4">
                    <div><h3 class="text-2xl font-bold text-[#1f2b5d]">Leave Import Results</h3><p class="text-sm text-slate-500">Summary of leave file processing</p></div>
                    <button type="button" data-close-leave-result class="text-4xl leading-none text-slate-500 hover:text-slate-700">&times;</button>
                </div>
                <div class="px-6 py-5">
                    <div class="grid grid-cols-3 gap-3 mb-5">
                        <div class="rounded-lg bg-emerald-50 border border-emerald-200 p-3 text-center"><p class="text-3xl font-extrabold text-emerald-700">{{ $importStats['imported'] ?? 0 }}</p><p class="text-xs font-semibold text-emerald-700">Imported</p></div>
                        <div class="rounded-lg bg-amber-50 border border-amber-200 p-3 text-center"><p class="text-3xl font-extrabold text-amber-700">{{ $importStats['skipped'] ?? 0 }}</p><p class="text-xs font-semibold text-amber-700">Skipped</p></div>
                        <div class="rounded-lg bg-blue-50 border border-blue-200 p-3 text-center"><p class="text-3xl font-extrabold text-blue-700">{{ $importStats['total_records'] ?? 0 }}</p><p class="text-xs font-semibold text-blue-700">Total Rows</p></div>
                    </div>
                    @if (count($unmatchedList) > 0)
                        <div class="mb-4">
                            <h4 class="text-sm font-bold text-red-700 mb-2">Employees NOT Found in HRIS ({{ count($unmatchedList) }})</h4>
                            <div class="max-h-48 overflow-y-auto rounded-lg border border-red-200 bg-red-50">
                                <table class="min-w-full text-sm"><thead class="bg-red-100 text-xs uppercase text-red-800"><tr><th class="px-3 py-2 text-left">#</th><th class="px-3 py-2 text-left">Employee (ID)</th></tr></thead>
                                <tbody class="divide-y divide-red-100">@foreach ($unmatchedList as $idx => $label)<tr><td class="px-3 py-1.5 text-red-700">{{ $idx + 1 }}</td><td class="px-3 py-1.5 text-red-800 font-medium">{{ $label }}</td></tr>@endforeach</tbody></table>
                            </div>
                        </div>
                    @endif
                    @if (count($appliedList) > 0)
                        <div>
                            <h4 class="text-sm font-bold text-emerald-700 mb-2">Successfully Applied ({{ count($appliedList) }})</h4>
                            <div class="max-h-40 overflow-y-auto rounded-lg border border-emerald-200 bg-emerald-50">
                                <table class="min-w-full text-sm"><thead class="bg-emerald-100 text-xs uppercase text-emerald-800"><tr><th class="px-3 py-2 text-left">#</th><th class="px-3 py-2 text-left">Employee (ID)</th></tr></thead>
                                <tbody class="divide-y divide-emerald-100">@foreach ($appliedList as $idx => $label)<tr><td class="px-3 py-1.5 text-emerald-700">{{ $idx + 1 }}</td><td class="px-3 py-1.5 text-emerald-800">{{ $label }}</td></tr>@endforeach</tbody></table>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="border-t border-slate-200 px-6 py-4 flex justify-end"><button type="button" data-close-leave-result class="rounded-md bg-[#00386f] px-6 py-2 text-sm font-semibold text-white hover:bg-[#002f5d]">Close</button></div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm"><p class="text-xs font-medium text-slate-500">Total Employees</p><p class="mt-1 text-4xl font-extrabold">{{ $stats['total_employees'] }}</p></article>
        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm"><p class="text-xs font-medium text-slate-500">Vacation Used</p><p class="mt-1 text-4xl font-extrabold">{{ $stats['vacation_used'] }}</p></article>
        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm"><p class="text-xs font-medium text-slate-500">Sick Leave Used</p><p class="mt-1 text-4xl font-extrabold">{{ $stats['sick_used'] }}</p></article>
        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm"><p class="text-xs font-medium text-slate-500">Current Year</p><p class="mt-1 text-4xl font-extrabold">{{ $stats['current_year'] }}</p></article>
    </div>

    <article class="rounded-xl border border-slate-300 bg-white p-3 shadow-sm">
        <div class="grid grid-cols-1 gap-2 md:grid-cols-3">
            <form method="GET" action="{{ route('admin.leave.index') }}" class="md:col-span-3 grid grid-cols-1 gap-2 md:grid-cols-4">
                <input
                    type="text"
                    name="search"
                    value="{{ $filters['search'] ?? '' }}"
                    placeholder="Search by name, email, ID, or department..."
                    class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none"
                >
                <select name="department_id" onchange="this.form.submit()" class="rounded-md border border-slate-300 px-2 py-2 text-sm focus:border-blue-400 focus:outline-none">
                    <option value="">All Departments</option>
                    <option value="asp" @selected(($filters['department_id'] ?? '') === 'asp')>Admin Support Personnel</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected(($filters['department_id'] ?? '') == $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
                <select name="employee_class" onchange="this.form.submit()" class="rounded-md border border-slate-300 px-2 py-2 text-sm focus:border-blue-400 focus:outline-none min-w-[14rem]">
                    <option value="all" @selected(($filters['employee_class'] ?? 'all') === 'all')>All Employee Types</option>
                    <option value="regular" @selected(($filters['employee_class'] ?? '') === 'regular')>Full - Time Employees</option>
                    <option value="irregular" @selected(($filters['employee_class'] ?? '') === 'irregular')>Probationary Employees</option>
                </select>
                <select name="leave_type" onchange="this.form.submit()" class="rounded-md border border-slate-300 px-2 py-2 text-sm focus:border-blue-400 focus:outline-none min-w-[14rem]">
                    <option value="all" @selected(($filters['leave_type'] ?? 'all') === 'all')>All Leave Types</option>
                    <option value="vacation leave" @selected(($filters['leave_type'] ?? '') === 'vacation leave')>Vacation Leave</option>
                    <option value="sick leave" @selected(($filters['leave_type'] ?? '') === 'sick leave')>Sick Leave</option>
                    <option value="emergency leave" @selected(($filters['leave_type'] ?? '') === 'emergency leave')>Emergency Leave</option>
                    <option value="bereavement leave" @selected(($filters['leave_type'] ?? '') === 'bereavement leave')>Bereavement Leave</option>
                    <option value="training leave" @selected(($filters['leave_type'] ?? '') === 'training leave')>Training Leave</option>
                    <option value="official business" @selected(($filters['leave_type'] ?? '') === 'official business')>Official Business</option>
                </select>
            </form>
        </div>
    </article>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        @forelse ($leaveCards as $card)
            <article data-faculty-card data-name="{{ $card['name'] }}" data-department="{{ $card['department'] }}" data-remaining="{{ $card['remaining'] }}" data-used="{{ $card['used'] }}" data-vacation-remaining="{{ $card['vacation_remaining'] ?? 0 }}" data-vacation-used="{{ $card['vacation_used'] ?? 0 }}" data-sick-remaining="{{ $card['sick_remaining'] ?? 0 }}" data-sick-used="{{ $card['sick_used'] ?? 0 }}" data-emergency-remaining="{{ $card['emergency_remaining'] ?? 0 }}" data-emergency-used="{{ $card['emergency_used'] ?? 0 }}" data-absences="{{ $card['absences'] ?? 0 }}" class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm transition hover:shadow-md">
                <div class="mb-3 flex items-center justify-between">
                    <div class="flex items-center gap-3 cursor-pointer" data-open-leave-detail>
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-[#00386f] text-sm font-semibold text-white">{{ $card['initials'] }}</span>
                        <div><p class="text-xl font-bold text-[#1f2b5d]">{{ $card['name'] }}</p><p class="text-sm text-slate-500">{{ $card['department'] }}</p></div>
                    </div>
                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ ($card['employee_status'] ?? 'non-regular') === 'regular' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                        {{ $card['employee_status_label'] ?? 'Probationary' }}
                    </span>
                </div>
                <div class="mb-3 flex flex-wrap gap-2">
                    @if (($card['vacation_used'] ?? 0) > 0)
                        <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-semibold text-emerald-800">VL: {{ rtrim(rtrim(number_format($card['vacation_used'], 2), '0'), '.') }}</span>
                    @endif
                    @if (($card['sick_used'] ?? 0) > 0)
                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-semibold text-amber-800">SL: {{ rtrim(rtrim(number_format($card['sick_used'], 2), '0'), '.') }}</span>
                    @endif
                    @if (($card['emergency_used'] ?? 0) > 0)
                        <span class="inline-flex items-center rounded-full bg-violet-100 px-2.5 py-1 text-[11px] font-semibold text-violet-800">EL: {{ rtrim(rtrim(number_format($card['emergency_used'], 2), '0'), '.') }}</span>
                    @endif
                    @if (($card['absences'] ?? 0) > 0)
                        <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-1 text-[11px] font-semibold text-red-800">Absences: {{ $card['absences'] }}</span>
                    @endif
                    @if (($card['employee_status'] ?? 'non-regular') === 'non-regular' && (($card['vacation_used'] ?? 0) > 0 || ($card['sick_used'] ?? 0) > 0 || ($card['emergency_used'] ?? 0) > 0))
                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-700">Tracked for probationary employee</span>
                    @endif
                </div>
                <div class="grid grid-cols-3 gap-2 text-center">
                    <div class="rounded-lg bg-emerald-50 p-2">
                        <p class="text-2xl font-extrabold text-emerald-700">{{ rtrim(rtrim(number_format($card['vacation_used'], 2), '0'), '.') }}</p>
                        <p class="text-[10px] font-semibold text-emerald-700">Vacation Used</p>
                    </div>
                    <div class="rounded-lg bg-amber-50 p-2">
                        <p class="text-2xl font-extrabold text-amber-700">{{ rtrim(rtrim(number_format($card['sick_used'], 2), '0'), '.') }}</p>
                        <p class="text-[10px] font-semibold text-amber-700">Sick Used</p>
                    </div>
                    <div class="rounded-lg bg-violet-50 p-2">
                        <p class="text-2xl font-extrabold text-violet-700">{{ rtrim(rtrim(number_format($card['emergency_used'], 2), '0'), '.') }}</p>
                        <p class="text-[10px] font-semibold text-violet-700">Emergency Used</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center justify-between text-xs text-slate-500">
                    <span>Total used: <span class="font-semibold text-slate-700">{{ rtrim(rtrim(number_format($card['used'], 2), '0'), '.') }}</span> day(s)</span>
                    <span>Remaining: <span class="font-semibold text-slate-700">{{ rtrim(rtrim(number_format($card['remaining'], 2), '0'), '.') }}</span></span>
                </div>
            </article>
        @empty
            <div class="col-span-full rounded-xl border border-slate-200 bg-white p-8 text-center"><p class="text-lg font-semibold text-slate-500">No employees to show</p></div>
        @endforelse
    </div>

    <div id="upload-leaves-modal" class="fixed inset-0 z-50 hidden items-start justify-center overflow-y-auto bg-black/45 p-4 py-6 sm:items-center">
        <div class="w-full max-w-3xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-2xl">
            <div class="flex items-start justify-between px-7 py-6">
                <div><h3 class="text-3xl font-bold text-[#1f2b8b]">Upload Leave Applications</h3><p class="mt-1 text-sm text-slate-500">Excel export from the HR system. Max 10 MB.</p></div>
                <button type="button" data-close-modal class="text-4xl leading-none text-slate-500 hover:text-slate-700">&times;</button>
            </div>
            <form id="upload-leaves-form" class="px-7 pb-7 js-loading-form" method="POST" action="{{ route('admin.leave.upload') }}" enctype="multipart/form-data">
                @csrf
                <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800 mb-4">
                    <p class="font-semibold">Employee ID Format Flexible</p>
                    <p class="mt-1 text-blue-700">Accepts IDs with and without dash. Leave will only be applied to registered employees.</p>
                </div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Leave applications file (.xlsx / .xls)</label>
                <label for="leaves_file" id="leaves-drop-zone" class="block cursor-pointer rounded-lg border-2 border-dashed border-slate-300 p-8 text-center transition hover:border-[#00386f] hover:bg-blue-50/30">
                    <input id="leaves_file" name="leaves_file" type="file" accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" class="sr-only" required>
                    <div class="mx-auto inline-flex h-14 w-14 items-center justify-center text-slate-400">
                        <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4" /><path d="m7 9 5-5 5 5" /><path d="M4 16v3a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-3" /></svg>
                    </div>
                    <p id="leaves-file-name" class="mt-3 text-lg text-slate-500">Click to upload or drag and drop</p>
                    <p id="leaves-file-size" class="text-sm text-slate-400">.xlsx or .xls · up to 10 MB</p>
                </label>
                <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
                    <button type="button" data-close-modal class="rounded-md border border-slate-400 px-8 py-2.5 text-lg font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-md bg-[#00386f] px-8 py-2.5 text-lg font-semibold text-white hover:bg-[#002f5d]">Import Leave File</button>
                </div>
            </form>
        </div>
    </div>

    <div id="leave-details-modal" class="fixed inset-0 z-50 hidden items-start justify-center overflow-y-auto bg-black/45 p-4 py-6 sm:items-center">
        <div class="w-full max-w-3xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-2xl">
            <div class="flex items-start justify-between px-7 py-6 border-b border-slate-200">
                <h3 class="text-3xl font-bold text-[#1f2b8b]">Leave Summary - <span id="leave-details-name">Employee</span></h3>
                <button type="button" data-close-modal class="text-4xl leading-none text-slate-500 hover:text-slate-700">&times;</button>
            </div>
            <div class="px-7 py-7">
                <h4 class="text-lg font-bold text-slate-900 mb-4">Leave Balance Breakdown</h4>
                <div class="grid grid-cols-1 gap-4 mb-6 md:grid-cols-3">
                    <article class="rounded-xl border border-emerald-300 bg-emerald-50 p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700 mb-3">Vacation Leave</p>
                        <div class="space-y-2">
                            <div>
                                <p class="text-sm text-emerald-600">Remaining</p>
                                <p id="leave-vacation-remaining" class="text-3xl font-extrabold text-emerald-700">0</p>
                            </div>
                            <div>
                                <p class="text-sm text-emerald-600">Used</p>
                                <p id="leave-vacation-used" class="text-2xl font-bold text-emerald-600">0</p>
                            </div>
                        </div>
                    </article>
                    <article class="rounded-xl border border-amber-300 bg-amber-50 p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-amber-700 mb-3">Sick Leave</p>
                        <div class="space-y-2">
                            <div>
                                <p class="text-sm text-amber-600">Remaining</p>
                                <p id="leave-sick-remaining" class="text-3xl font-extrabold text-amber-700">0</p>
                            </div>
                            <div>
                                <p class="text-sm text-amber-600">Used</p>
                                <p id="leave-sick-used" class="text-2xl font-bold text-amber-600">0</p>
                            </div>
                        </div>
                    </article>
                    <article class="rounded-xl border border-violet-300 bg-violet-50 p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-violet-700 mb-3">Emergency Leave</p>
                        <div class="space-y-2">
                            <div>
                                <p class="text-sm text-violet-600">Remaining</p>
                                <p id="leave-emergency-remaining" class="text-3xl font-extrabold text-violet-700">0</p>
                            </div>
                            <div>
                                <p class="text-sm text-violet-600">Used</p>
                                <p id="leave-emergency-used" class="text-2xl font-bold text-violet-600">0</p>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
            <div class="px-7 pb-7">
                <p class="text-sm text-slate-500">Absences (current year): <span id="leave-absences" class="font-semibold text-slate-700">0</span></p>
            </div>
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
        const allModals = document.querySelectorAll('#upload-leaves-modal, #leave-details-modal');
        document.querySelectorAll('[data-open-modal]').forEach(btn => { btn.addEventListener('click', () => { const m = document.getElementById(btn.dataset.openModal); if(m){m.classList.remove('hidden');m.classList.add('flex');document.body.classList.add('overflow-hidden');} }); });
        document.querySelectorAll('[data-close-modal]').forEach(btn => { btn.addEventListener('click', () => { const m = btn.closest('.fixed.inset-0'); if(m){m.classList.add('hidden');m.classList.remove('flex');document.body.classList.remove('overflow-hidden');} }); });
        document.querySelectorAll('[data-close-leave-result]').forEach(btn => { btn.addEventListener('click', () => { const m = document.getElementById('leave-import-result-modal'); if(m){m.classList.add('hidden');m.classList.remove('flex');document.body.classList.remove('overflow-hidden');} }); });
        const leaveResultModal = document.getElementById('leave-import-result-modal');
        if (leaveResultModal) { document.body.classList.add('overflow-hidden'); leaveResultModal.addEventListener('click', e => { if(e.target===leaveResultModal){leaveResultModal.classList.add('hidden');leaveResultModal.classList.remove('flex');document.body.classList.remove('overflow-hidden');} }); }
        allModals.forEach(m => { m.addEventListener('click', e => { if(e.target===m){m.classList.add('hidden');m.classList.remove('flex');document.body.classList.remove('overflow-hidden');} }); });
        document.addEventListener('keydown', e => { if(e.key==='Escape'){[...allModals, leaveResultModal].filter(Boolean).forEach(m => { if(!m.classList.contains('hidden')){m.classList.add('hidden');m.classList.remove('flex');document.body.classList.remove('overflow-hidden');} });} });

        const leavesInput = document.getElementById('leaves_file');
        const leavesFileName = document.getElementById('leaves-file-name');
        const leavesFileSize = document.getElementById('leaves-file-size');
        const leavesDropZone = document.getElementById('leaves-drop-zone');
        if (leavesInput) {
            leavesInput.addEventListener('change', function () {
                if (!this.files.length) { leavesFileName.textContent = 'Click to upload or drag and drop'; leavesFileSize.textContent = '.xlsx or .xls · up to 10 MB'; return; }
                const file = this.files[0];
                const sizeKB = (file.size / 1024).toFixed(1);
                const sizeMB = (file.size / 1024 / 1024).toFixed(2);
                const sizeText = file.size > 1048576 ? sizeMB + ' MB' : sizeKB + ' KB';
                leavesFileName.textContent = file.name;
                leavesFileName.classList.add('font-semibold');
                if (file.size > 10485760) { leavesFileSize.textContent = sizeText + ' — TOO LARGE'; leavesFileName.classList.add('text-red-600'); leavesDropZone.classList.add('border-red-400','bg-red-50/30'); }
                else { leavesFileSize.textContent = sizeText; leavesFileName.classList.add('text-emerald-700'); leavesDropZone.classList.add('border-emerald-400','bg-emerald-50/30'); leavesDropZone.classList.remove('border-slate-300'); }
            });
        }

        document.querySelectorAll('[data-open-leave-detail]').forEach(trigger => {
            trigger.addEventListener('click', () => {
                const card = trigger.closest('[data-faculty-card]');
                const formatNumber = (val) => {
                    const num = parseFloat(val) || 0;
                    return num === 0 ? '0' : num.toString();
                };
                document.getElementById('leave-details-name').textContent = card.dataset.name || 'Employee';
                document.getElementById('leave-vacation-remaining').textContent = formatNumber(card.dataset.vacationRemaining);
                document.getElementById('leave-vacation-used').textContent = formatNumber(card.dataset.vacationUsed);
                document.getElementById('leave-sick-remaining').textContent = formatNumber(card.dataset.sickRemaining);
                document.getElementById('leave-sick-used').textContent = formatNumber(card.dataset.sickUsed);
                document.getElementById('leave-emergency-remaining').textContent = formatNumber(card.dataset.emergencyRemaining);
                document.getElementById('leave-emergency-used').textContent = formatNumber(card.dataset.emergencyUsed);
                const abs = card.dataset.absences || 0;
                const absEl = document.getElementById('leave-absences');
                if (absEl) { absEl.textContent = abs; }
                const m = document.getElementById('leave-details-modal'); m.classList.remove('hidden'); m.classList.add('flex'); document.body.classList.add('overflow-hidden');
            });
        });

        const loadingOverlay = document.getElementById('loading-overlay');
        document.querySelectorAll('.js-loading-form').forEach(form => { form.addEventListener('submit', () => { if(loadingOverlay){loadingOverlay.classList.remove('hidden');loadingOverlay.classList.add('flex');} }); });
    </script>
@endpush
