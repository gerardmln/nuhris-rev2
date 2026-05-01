@extends('hr.layout')

@php
    $pageTitle = 'Time Keeping';
    $pageHeading = 'Time Keeping';
    $activeNav = 'timekeeping';
@endphp

@section('content')
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-sm text-slate-500">View biometric attendance records and generate DTR</p>
        </div>
        <div class="flex items-center gap-2">
            <button data-open-modal="upload-biometrics-modal" data-testid="upload-button" class="rounded-lg bg-[#00386f] px-4 py-2 text-sm font-semibold text-white hover:bg-[#002f5d] transition">Upload PDF</button>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" data-testid="success-message">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800" data-testid="error-message">
            {{ session('error') }}
        </div>
    @endif

    {{-- Import Result Modal --}}
    @if (session('unmatched_employees') || session('applied_employees'))
        @php
            $unmatchedList = session('unmatched_employees', []);
            $appliedList = session('applied_employees', []);
            $importStats = session('import_stats', []);
        @endphp
        <div id="import-result-modal" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/45 p-4 py-6 sm:items-center">
            <div class="w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-2xl">
                <div class="flex items-start justify-between border-b border-slate-300 px-6 py-4">
                    <div>
                        <h3 class="text-2xl font-bold text-[#1f2b5d]">DTR Upload Results</h3>
                        <p class="text-sm text-slate-500">Summary of biometric PDF processing</p>
                    </div>
                    <button type="button" data-close-result-modal class="text-4xl leading-none text-slate-500 hover:text-slate-700">&times;</button>
                </div>
                <div class="px-6 py-5">
                    <div class="grid grid-cols-3 gap-3 mb-5">
                        <div class="rounded-lg bg-emerald-50 border border-emerald-200 p-3 text-center">
                            <p class="text-3xl font-extrabold text-emerald-700">{{ $importStats['imported'] ?? 0 }}</p>
                            <p class="text-xs font-semibold text-emerald-700">Imported</p>
                        </div>
                        <div class="rounded-lg bg-amber-50 border border-amber-200 p-3 text-center">
                            <p class="text-3xl font-extrabold text-amber-700">{{ $importStats['skipped'] ?? 0 }}</p>
                            <p class="text-xs font-semibold text-amber-700">Skipped</p>
                        </div>
                        <div class="rounded-lg bg-blue-50 border border-blue-200 p-3 text-center">
                            <p class="text-3xl font-extrabold text-blue-700">{{ $importStats['total_records'] ?? 0 }}</p>
                            <p class="text-xs font-semibold text-blue-700">Total Records</p>
                        </div>
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
                <div class="border-t border-slate-200 px-6 py-4 flex justify-end">
                    <button type="button" data-close-result-modal class="rounded-md bg-[#00386f] px-6 py-2 text-sm font-semibold text-white hover:bg-[#002f5d]">Close</button>
                </div>
            </div>
        </div>
    @endif

    <article class="rounded-xl border border-slate-300 bg-white p-3 shadow-sm">
        <div class="grid grid-cols-1 gap-2 md:grid-cols-4">
            <form method="GET" action="{{ route('timekeeping.index') }}" class="md:col-span-4">
                <div class="flex gap-2">
                    <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search by name, email, ID, or department..."
                           class="flex-1 rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none">

                    <select name="period" onchange="
                        var parts = this.value.split('-');
                        this.form.querySelector('[name=month]').value = parts[0];
                        this.form.querySelector('[name=year]').value = parts[1];
                        this.form.submit();
                    " class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm focus:border-blue-400 focus:outline-none">
                        @if(isset($periods))
                            @foreach($periods as $p)
                                <option value="{{ $p['month'] }}-{{ $p['year'] }}" @selected($p['month'] == ($selectedMonth ?? now()->month) && $p['year'] == ($selectedYear ?? now()->year))>{{ $p['label'] }}</option>
                            @endforeach
                        @else
                            @php $current = now(); @endphp
                            <option value="{{ $current->month }}-{{ $current->year }}">{{ $current->format('F Y') }}</option>
                        @endif
                    </select>

                    <input type="hidden" name="month" value="{{ $selectedMonth ?? now()->month }}">
                    <input type="hidden" name="year" value="{{ $selectedYear ?? now()->year }}">
                </div>
            </form>
        </div>
    </article>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($employeeCards as $card)
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
                @unless ($card['has_data'])
                    <p class="mt-3 rounded-md border border-dashed border-slate-300 bg-slate-50 px-2 py-1 text-center text-[11px] text-slate-500">
                        No biometric data yet — upload a Timesheet PDF to populate.
                    </p>
                @endunless
                <p class="mt-3 text-xs text-slate-500">Schedule: {{ $card['schedule_summary'] }}</p>
                <a href="{{ route('timekeeping.dtr', ['employee' => $card['id'], 'month' => $selectedMonth, 'year' => $selectedYear]) }}" class="mt-3 block w-full rounded-md bg-[#00386f] px-3 py-2 text-center text-sm font-semibold text-white hover:bg-[#002f5d] transition">View DTR</a>
            </article>
        @empty
            <div class="col-span-full rounded-xl border border-slate-200 bg-white p-8 text-center">
                <p class="text-lg font-semibold text-slate-500">No employees found</p>
                <p class="text-sm text-slate-400">Try adjusting your search or filter criteria.</p>
            </div>
        @endforelse
    </div>

    {{-- Upload Modal --}}
    <div id="upload-biometrics-modal" class="fixed inset-0 z-50 hidden items-start justify-center overflow-y-auto bg-black/45 p-4 py-6 sm:items-center">
        <div class="w-full max-w-3xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-2xl">
            <div class="flex items-start justify-between px-7 py-6">
                <h3 class="text-4xl font-bold text-[#1f2b8b]">Upload PDF</h3>
                <button type="button" data-close-modal class="text-4xl leading-none text-slate-500 hover:text-slate-700">&times;</button>
            </div>
            <form id="upload-form" class="px-7 pb-7 js-loading-form" method="POST" action="{{ route('biometrics.upload') }}" enctype="multipart/form-data">
                @csrf
                <label class="mb-3 block text-2xl font-medium text-slate-700">Attendance File (PDF only)</label>
                <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800 mb-4">
                    <p class="font-semibold">Employee ID Format Flexible</p>
                    <p class="mt-1 text-blue-700">Accepts IDs with and without dash (e.g. <span class="font-mono">23-8061</span> = <span class="font-mono">238061</span>). Only registered employees will be matched.</p>
                </div>
                <label for="biometrics_file" id="file-drop-zone" class="block cursor-pointer rounded-lg border-2 border-dashed border-slate-300 p-8 text-center transition hover:border-[#00386f] hover:bg-blue-50/30">
                    <input id="biometrics_file" name="biometrics_file" type="file" accept="application/pdf,.pdf" class="sr-only" required>
                    <div class="mx-auto inline-flex h-14 w-14 items-center justify-center text-slate-400">
                        <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4" /><path d="m7 9 5-5 5 5" /><path d="M4 16v3a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-3" /></svg>
                    </div>
                    <p id="file-name-display" class="mt-3 text-lg text-slate-500">Click to upload or drag and drop</p>
                    <p id="file-size-display" class="text-sm text-slate-400">PDF only · up to 10 MB</p>
                </label>
                <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
                    <button type="button" data-close-modal class="rounded-md border border-slate-400 px-8 py-2.5 text-lg font-semibold text-slate-700 hover:bg-slate-50 transition">Cancel</button>
                    <button type="submit" id="submit-btn" class="inline-flex items-center justify-center gap-2 rounded-md bg-[#00386f] px-8 py-2.5 text-lg font-semibold text-white hover:bg-[#002f5d] transition">Process PDF</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Global Loading Overlay --}}
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
        // Modal logic
        document.querySelectorAll('[data-open-modal]').forEach(btn => {
            btn.addEventListener('click', () => {
                const modal = document.getElementById(btn.dataset.openModal);
                if (modal) { modal.classList.remove('hidden'); modal.classList.add('flex'); document.body.classList.add('overflow-hidden'); }
            });
        });
        document.querySelectorAll('[data-close-modal]').forEach(btn => {
            btn.addEventListener('click', () => {
                const modal = btn.closest('.fixed.inset-0');
                if (modal) { modal.classList.add('hidden'); modal.classList.remove('flex'); document.body.classList.remove('overflow-hidden'); }
            });
        });
        document.querySelectorAll('[data-close-result-modal]').forEach(btn => {
            btn.addEventListener('click', () => {
                const rm = document.getElementById('import-result-modal');
                if (rm) { rm.classList.add('hidden'); rm.classList.remove('flex'); document.body.classList.remove('overflow-hidden'); }
            });
        });
        const resultModal = document.getElementById('import-result-modal');
        if (resultModal) {
            document.body.classList.add('overflow-hidden');
            resultModal.addEventListener('click', e => { if (e.target === resultModal) { resultModal.classList.add('hidden'); resultModal.classList.remove('flex'); document.body.classList.remove('overflow-hidden'); } });
        }
        const uploadModal = document.getElementById('upload-biometrics-modal');
        if (uploadModal) {
            uploadModal.addEventListener('click', e => { if (e.target === uploadModal) { uploadModal.classList.add('hidden'); uploadModal.classList.remove('flex'); document.body.classList.remove('overflow-hidden'); } });
        }
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                [uploadModal, resultModal].forEach(m => { if (m && !m.classList.contains('hidden')) { m.classList.add('hidden'); m.classList.remove('flex'); document.body.classList.remove('overflow-hidden'); } });
            }
        });

        // File input with exact size display
        const fileInput = document.getElementById('biometrics_file');
        const fileNameDisplay = document.getElementById('file-name-display');
        const fileSizeDisplay = document.getElementById('file-size-display');
        const fileDropZone = document.getElementById('file-drop-zone');
        if (fileInput) {
            fileInput.addEventListener('change', function() {
                if (this.files.length > 0) {
                    const file = this.files[0];
                    const sizeKB = (file.size / 1024).toFixed(1);
                    const sizeMB = (file.size / 1024 / 1024).toFixed(2);
                    const sizeText = file.size > 1048576 ? sizeMB + ' MB' : sizeKB + ' KB';
                    fileNameDisplay.textContent = file.name;
                    fileNameDisplay.classList.remove('text-slate-500');
                    if (file.size > 10485760) {
                        fileNameDisplay.classList.add('text-red-600', 'font-semibold');
                        fileSizeDisplay.textContent = sizeText + ' — TOO LARGE (max 10 MB)';
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

        // Loading overlay for all forms
        const loadingOverlay = document.getElementById('loading-overlay');
        document.querySelectorAll('.js-loading-form').forEach(form => {
            form.addEventListener('submit', function() {
                if (loadingOverlay) { loadingOverlay.classList.remove('hidden'); loadingOverlay.classList.add('flex'); }
            });
        });
    </script>
@endpush
