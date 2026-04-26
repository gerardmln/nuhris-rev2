@extends('employee.layout')

@section('title', 'Account')
@section('page_title', 'Account')

@section('content')
    @if (session('success'))
        <div class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if (session('password_success'))
        <div class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('password_success') }}</div>
    @endif

    <article class="overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm">
        <div class="h-20 bg-[#003a78]"></div>
        <div class="flex flex-wrap items-end gap-4 px-6 pb-6">
            <div class="-mt-10 grid h-24 w-24 place-content-center rounded-2xl border-4 border-white bg-slate-200 text-2xl font-bold text-slate-700">{{ str(auth()->user()->name)->explode(' ')->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->join('') }}</div>
            <div>
                <p class="text-2xl font-bold text-slate-900">{{ auth()->user()->name }}</p>
                <p class="text-sm text-slate-500">{{ auth()->user()->email }}</p>
            </div>
        </div>
    </article>

    <article class="rounded-2xl border border-slate-300 bg-white p-6 shadow-sm">
        <h2 class="text-3xl font-bold text-slate-900">Profile Information</h2>
        <form class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-3 js-loading-form" method="POST" action="{{ route('employee.account.update') }}">
            @csrf
            <input type="hidden" name="name" value="{{ auth()->user()->name }}">
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Employee Type</label>
                <select name="employee_type" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Select type</option>
                    @foreach ($employeeTypes as $type)
                        <option value="{{ $type }}" @selected($employee && str_contains(strtolower($employee->employment_type ?? ''), strtolower($type)))>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Employee ID</label>
                <input name="employee_id" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" value="{{ $employee->employee_id ?? '' }}" placeholder="e.g., NU-2025-001">
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Department</label>
                <select name="department_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Select department</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected(($employee->department_id ?? null) === $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Phone</label>
                <input name="phone" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" value="{{ $employee->phone ?? '' }}" placeholder="e.g., 09171234567">
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Position</label>
                <input name="position" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" value="{{ $employee->position ?? '' }}" placeholder="e.g., Instructor I">
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Date Hired</label>
                <input name="hire_date" type="date" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" value="{{ optional($employee?->hire_date)->format('Y-m-d') }}">
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Address</label>
                <input name="address" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" value="{{ $employee->address ?? '' }}" placeholder="Home address">
            </div>
            <div class="lg:col-span-3">
                <button type="submit" class="float-right rounded-xl bg-[#003a78] px-6 py-2 text-sm font-semibold text-white hover:bg-[#002f61]">Save Changes</button>
            </div>
        </form>
    </article>

    {{-- Change Password Section --}}
    <article class="rounded-2xl border border-slate-300 bg-white p-6 shadow-sm">
        <h2 class="text-3xl font-bold text-slate-900">Change Password</h2>
        <p class="mt-1 text-sm text-slate-500">Update your account password. Use your current temporary password or existing password.</p>

        @if ($errors->has('current_password'))
            <div class="mt-3 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first('current_password') }}</div>
        @endif
        @if ($errors->has('new_password'))
            <div class="mt-3 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first('new_password') }}</div>
        @endif

        <form class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-3 js-loading-form" method="POST" action="{{ route('employee.account.change-password') }}">
            @csrf
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Current Password *</label>
                <div class="relative">
                    <input id="current_password" name="current_password" type="password" class="w-full rounded-lg border border-slate-300 px-3 py-2 pr-10 text-sm @error('current_password') border-red-400 @enderror" placeholder="Enter current/temporary password" required>
                    <button type="button" onclick="togglePassword('current_password', this)" class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5 eye-show" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                        <svg class="w-5 h-5 eye-hide hidden" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                    </button>
                </div>
                <p class="mt-1 text-xs text-slate-500">This is your temporary password from HR or your current password.</p>
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">New Password *</label>
                <div class="relative">
                    <input id="new_password" name="new_password" type="password" class="w-full rounded-lg border border-slate-300 px-3 py-2 pr-10 text-sm @error('new_password') border-red-400 @enderror" placeholder="Min 6 characters" required minlength="6">
                    <button type="button" onclick="togglePassword('new_password', this)" class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5 eye-show" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                        <svg class="w-5 h-5 eye-hide hidden" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                    </button>
                </div>
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold text-slate-700">Confirm New Password *</label>
                <div class="relative">
                    <input id="new_password_confirmation" name="new_password_confirmation" type="password" class="w-full rounded-lg border border-slate-300 px-3 py-2 pr-10 text-sm" placeholder="Re-enter new password" required>
                    <button type="button" onclick="togglePassword('new_password_confirmation', this)" class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5 eye-show" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                        <svg class="w-5 h-5 eye-hide hidden" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                    </button>
                </div>
            </div>
            <div class="lg:col-span-3">
                <button type="submit" class="float-right rounded-xl bg-[#003a78] px-6 py-2 text-sm font-semibold text-white hover:bg-[#002f61]">Change Password</button>
            </div>
        </form>
    </article>

    {{-- Global Loading Overlay --}}
    <div id="loading-overlay" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/50">
        <div class="rounded-2xl bg-white px-8 py-6 shadow-2xl text-center">
            <svg class="mx-auto h-10 w-10 animate-spin text-[#003a78]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="mt-3 text-lg font-semibold text-blue-900">Saving...</p>
            <p class="text-sm text-slate-500">Please wait.</p>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function togglePassword(inputId, button) {
            const input = document.getElementById(inputId);
            const showIcon = button.querySelector('.eye-show');
            const hideIcon = button.querySelector('.eye-hide');
            if (input.type === 'password') {
                input.type = 'text';
                showIcon.classList.add('hidden');
                hideIcon.classList.remove('hidden');
            } else {
                input.type = 'password';
                showIcon.classList.remove('hidden');
                hideIcon.classList.add('hidden');
            }
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
