@extends('employee.layout')

@section('title', 'Upload Credential')
@section('page_title', 'Credentials')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4">
        <p class="text-sm text-slate-600">Upload and manage your credentials. HR will review and verify submissions.</p>
        <a href="{{ route('employee.credentials') }}" class="rounded-xl bg-[#242b34] px-5 py-2 text-sm font-semibold text-white hover:bg-[#1b222b]" data-testid="credential-cancel">Cancel</a>
    </div>

    @if (session('error'))
        <div class="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800" data-testid="credential-upload-error">{{ session('error') }}</div>
    @endif

    <article class="rounded-2xl border border-slate-300 bg-white p-6 shadow-sm">
        <h2 class="text-3xl font-bold text-slate-900">Profile Information</h2>

        <form id="credential-upload-form"
              class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-3"
              method="POST"
              action="{{ route('employee.credentials.upload.store') }}"
              enctype="multipart/form-data"
              data-testid="credential-upload-form">
            @csrf

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Credential Type</label>
                <select name="credential_type" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" required data-testid="credential-type-input">
                    <option value="">Select type</option>
                    <option value="resume" @selected(old('credential_type') === 'resume')>Resume</option>
                    <option value="prc" @selected(old('credential_type') === 'prc')>PRC License</option>
                    <option value="seminars" @selected(old('credential_type') === 'seminars')>Seminar / Training</option>
                    <option value="degrees" @selected(old('credential_type') === 'degrees')>Academic Degree</option>
                    <option value="ranking" @selected(old('credential_type') === 'ranking')>Ranking File</option>
                </select>
                @error('credential_type')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Title</label>
                <input name="title" type="text" value="{{ old('title') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="e.g., PRC License 2025" required data-testid="credential-title-input">
                @error('title')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Department</label>
                <select name="department_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" data-testid="credential-department-input">
                    <option value="">Select department</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}"
                            @selected(old('department_id') ? old('department_id') == $department->id : ($employee && $employee->department && $employee->department->id === $department->id))
                        >{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Expiration Date</label>
                <input name="expires_at" type="date" value="{{ old('expires_at') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" data-testid="credential-expires-input">
            </div>

            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Description</label>
                <input name="description" type="text" value="{{ old('description') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Additional details" data-testid="credential-description-input">
            </div>

            {{-- File Upload with preview + loading --}}
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">File Upload</label>

                {{-- Dropzone + hidden input --}}
                <label id="credential-dropzone"
                       for="credential_file"
                       class="group relative block cursor-pointer rounded-lg border border-dashed border-slate-300 bg-slate-50 px-3 py-5 text-center text-sm text-slate-500 transition hover:border-[#00386f] hover:bg-slate-100">
                    {{-- Idle state --}}
                    <div id="dropzone-idle" class="flex flex-col items-center gap-1">
                        <svg class="h-6 w-6 text-slate-400 group-hover:text-[#00386f]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.9 5 5 0 019.9-1A5.5 5.5 0 0118.5 16H17m-5-5v8m0 0l-3-3m3 3l3-3"/>
                        </svg>
                        <span>Click to upload <span class="text-slate-400">(PDF, Image, DOC · max 10&nbsp;MB)</span></span>
                    </div>

                    {{-- Reading state --}}
                    <div id="dropzone-reading" class="hidden flex-col items-center gap-2">
                        <svg class="h-6 w-6 animate-spin text-[#00386f]" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.3A8 8 0 014 12H0c0 3 1.1 5.8 3 7.9l3-2.6z"/>
                        </svg>
                        <span class="font-medium text-[#00386f]">Reading file…</span>
                    </div>

                    {{-- Selected state --}}
                    <div id="dropzone-selected" class="hidden items-center gap-3 text-left">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#00386f]/10 text-[#00386f]">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-slate-900" id="file-name" data-testid="credential-file-name">—</p>
                            <p class="text-xs text-slate-500"><span id="file-size">—</span> · <span class="text-emerald-600 font-medium">Ready to submit</span></p>
                        </div>
                        <button type="button"
                                id="file-clear"
                                class="shrink-0 rounded-lg border border-slate-300 bg-white p-1.5 text-slate-500 hover:bg-red-50 hover:text-red-600 hover:border-red-300"
                                aria-label="Remove selected file"
                                data-testid="credential-file-clear">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <input id="credential_file" name="credential_file" type="file" class="hidden" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" data-testid="credential-file-input">
                </label>

                {{-- Client-side error --}}
                <p id="file-client-error" class="mt-1 hidden text-xs text-red-600" data-testid="credential-file-client-error"></p>

                @error('credential_file')
                    <p class="mt-1 text-xs text-red-600" data-testid="credential-file-server-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="lg:col-span-3">
                <button type="submit"
                        id="credential-submit-btn"
                        class="float-right inline-flex items-center gap-2 rounded-xl bg-[#003a78] px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#002f61] disabled:cursor-not-allowed disabled:opacity-80"
                        data-testid="credential-submit-btn">
                    <svg id="credential-submit-spinner" class="hidden h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.3A8 8 0 014 12H0c0 3 1.1 5.8 3 7.9l3-2.6z"/>
                    </svg>
                    <span id="credential-submit-label">Submit Credential</span>
                </button>
            </div>
        </form>
    </article>

    {{-- Upload-in-progress overlay --}}
    <div id="credential-upload-overlay"
         class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 backdrop-blur-sm"
         data-testid="credential-upload-overlay">
        <div class="w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-2xl">
            <svg class="mx-auto h-10 w-10 animate-spin text-[#00386f]" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.3A8 8 0 014 12H0c0 3 1.1 5.8 3 7.9l3-2.6z"/>
            </svg>
            <p class="mt-3 text-base font-semibold text-slate-900">Uploading your credential…</p>
            <p class="mt-1 text-xs text-slate-500">Please don't close this tab. This may take a few seconds for larger files.</p>
        </div>
    </div>

    @push('scripts')
    <script>
        (() => {
            const MAX_BYTES = 10 * 1024 * 1024; // 10 MB
            const ALLOWED = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            const ALLOWED_EXTS = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];

            const form = document.getElementById('credential-upload-form');
            const fileInput = document.getElementById('credential_file');
            const idle = document.getElementById('dropzone-idle');
            const reading = document.getElementById('dropzone-reading');
            const selected = document.getElementById('dropzone-selected');
            const fileName = document.getElementById('file-name');
            const fileSize = document.getElementById('file-size');
            const fileClear = document.getElementById('file-clear');
            const clientErr = document.getElementById('file-client-error');
            const submitBtn = document.getElementById('credential-submit-btn');
            const submitSpinner = document.getElementById('credential-submit-spinner');
            const submitLabel = document.getElementById('credential-submit-label');
            const overlay = document.getElementById('credential-upload-overlay');

            const show = (el) => { if (!el) return; el.classList.remove('hidden'); el.classList.add('flex'); };
            const hide = (el) => { if (!el) return; el.classList.add('hidden'); el.classList.remove('flex'); };
            const setState = (name) => {
                hide(idle); hide(reading); hide(selected);
                if (name === 'idle') show(idle);
                if (name === 'reading') show(reading);
                if (name === 'selected') show(selected);
            };

            const fmtBytes = (b) => {
                if (b < 1024) return b + ' B';
                if (b < 1024 * 1024) return (b / 1024).toFixed(1) + ' KB';
                return (b / 1024 / 1024).toFixed(2) + ' MB';
            };

            const showClientError = (msg) => {
                clientErr.textContent = msg;
                clientErr.classList.remove('hidden');
            };
            const clearClientError = () => {
                clientErr.textContent = '';
                clientErr.classList.add('hidden');
            };

            const resetFile = () => {
                try { fileInput.value = ''; } catch (e) {}
                fileName.textContent = '—';
                fileSize.textContent = '—';
                clearClientError();
                setState('idle');
            };

            fileInput.addEventListener('change', () => {
                clearClientError();
                const f = fileInput.files && fileInput.files[0];
                if (!f) { setState('idle'); return; }

                // Validate extension (MIME can be unreliable on Windows for .doc)
                const ext = (f.name.split('.').pop() || '').toLowerCase();
                if (!ALLOWED_EXTS.includes(ext)) {
                    showClientError('Unsupported file type. Allowed: PDF, JPG, PNG, DOC, DOCX.');
                    resetFile();
                    return;
                }
                if (f.size > MAX_BYTES) {
                    showClientError('File is too large. Maximum allowed size is 10 MB (your file is ' + fmtBytes(f.size) + ').');
                    resetFile();
                    return;
                }

                // Reading state (brief, but perceived loading for larger PDFs)
                setState('reading');

                // Use FileReader to genuinely read the file so the loader reflects work.
                const reader = new FileReader();
                reader.onload = () => {
                    fileName.textContent = f.name;
                    fileSize.textContent = fmtBytes(f.size);
                    setState('selected');
                };
                reader.onerror = () => {
                    showClientError('Could not read the selected file. Please try again.');
                    resetFile();
                };
                // Slice only head for speed — we just need the reader to fire
                reader.readAsArrayBuffer(f.slice(0, Math.min(f.size, 64 * 1024)));
            });

            fileClear.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                resetFile();
            });

            // Submit loading state
            form.addEventListener('submit', (e) => {
                if (!form.checkValidity()) return;

                submitBtn.disabled = true;
                submitSpinner.classList.remove('hidden');
                submitLabel.textContent = 'Submitting…';

                // Show overlay only if a file is attached (true upload)
                if (fileInput.files && fileInput.files[0]) {
                    show(overlay);
                }
            });
        })();
    </script>
    @endpush
@endsection
