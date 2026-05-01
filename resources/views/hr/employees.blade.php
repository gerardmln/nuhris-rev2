<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Employees | {{ config('app.name', 'NU HRIS') }}</title>
    @include('partials.favicon')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#eceef1] text-slate-900 antialiased overflow-x-hidden overflow-y-auto">
    <div class="flex min-h-screen flex-col lg:flex-row">
        @include('partials.hr-sidebar', ['activeNav' => 'employees'])

        <main class="min-h-screen flex-1">
            <header class="border-b border-slate-300 bg-white px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-[30px] font-bold leading-none text-[#1f2b5d]">Employees</h1>
                        <p class="text-sm text-slate-500">National University HRIS</p>
                    </div>

                    @include('partials.header-actions')
                </div>
            </header>

            <section class="space-y-5 px-5 py-5 sm:px-6 sm:py-6">
                @if (session('success'))
                    <div class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
                        {{ session('error') }}
                    </div>
                @endif

                @if (session('credential_notice'))
                    @php($notice = session('credential_notice'))
                    @php($emailStatus = $notice['email_status'] ?? null)
                    <article class="rounded-xl border border-blue-300 bg-blue-50 p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-blue-700">
                                    {{ ($notice['is_resend'] ?? false) ? 'Credentials Regenerated' : 'New Account Credentials' }}
                                </p>
                                <h3 class="mt-1 text-xl font-bold text-[#1f2b5d]">
                                    Share these sign-in details with {{ $notice['employee_name'] }}
                                </h3>
                                @if ($emailStatus && ($emailStatus['sent'] ?? false))
                                    <p class="mt-1 text-sm text-emerald-700">
                                        <span class="font-semibold">Email sent</span> to {{ $notice['email'] }} via Resend. The password below is shown once here in case the email bounces.
                                    </p>
                                @elseif ($emailStatus && ! ($emailStatus['sent'] ?? false))
                                    <p class="mt-1 text-sm text-amber-700">
                                        <span class="font-semibold">Email delivery failed.</span> Please share the credentials manually. Reason: {{ $emailStatus['message'] ?? 'Unknown error.' }}
                                    </p>
                                @else
                                    <p class="mt-1 text-sm text-blue-800">
                                        These credentials will only be shown <span class="font-semibold">once</span>. Copy and hand them off through your preferred channel.
                                    </p>
                                @endif
                            </div>
                            <button
                                type="button"
                                data-copy-credentials
                                data-credential-email="{{ $notice['email'] }}"
                                data-credential-password="{{ $notice['temp_password'] }}"
                                class="shrink-0 rounded-md border border-blue-300 bg-white px-3 py-2 text-xs font-semibold text-blue-800 hover:bg-blue-100">
                                Copy to clipboard
                            </button>
                        </div>
                        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div class="rounded-md border border-blue-200 bg-white px-3 py-2">
                                <p class="text-[11px] font-semibold uppercase text-slate-500">Employee ID</p>
                                <p class="text-sm font-mono text-slate-800">{{ $notice['employee_id'] }}</p>
                            </div>
                            <div class="rounded-md border border-blue-200 bg-white px-3 py-2">
                                <p class="text-[11px] font-semibold uppercase text-slate-500">Email / Username</p>
                                <p class="text-sm font-mono text-slate-800">{{ $notice['email'] }}</p>
                            </div>
                            <div class="rounded-md border border-blue-200 bg-white px-3 py-2">
                                <p class="text-[11px] font-semibold uppercase text-slate-500">Temporary Password</p>
                                <p class="text-sm font-mono font-semibold text-slate-800">{{ $notice['temp_password'] }}</p>
                            </div>
                        </div>
                    </article>
                @endif

                @if ($errors->any())
                    <div class="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
                        <p class="font-semibold">Unable to save employee. Please check the fields below.</p>
                        <ul class="mt-2 list-disc ps-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-sm text-slate-500">Manage faculty and staff records</p>
                    </div>
                    <button data-open-modal="employee-add-existing-modal" class="rounded-lg bg-[#00386f] px-4 py-2 text-sm font-semibold text-white hover:bg-[#002f5d]">+ Add Employee</button>
                </div>

                <article class="rounded-xl border border-slate-300 bg-white p-3 shadow-sm">
                    <form method="GET" action="{{ route('employees.index') }}" class="grid grid-cols-1 gap-2 md:grid-cols-6">
                        <div class="md:col-span-2">
                            <input
                                type="text"
                                name="search"
                                value="{{ $filters['search'] }}"
                                placeholder="Search by name, email, ID, or department..."
                                class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none"
                            >
                        </div>
                        <div class="md:col-span-2">
                            <select name="department_id" onchange="this.form.submit()" class="w-full rounded-md border border-slate-300 px-2 py-2 text-sm focus:border-blue-400 focus:outline-none">
                                <option value="">All Departments</option>
                                <option value="asp" @selected($filters['department_id'] === 'asp')>Admin Support Personnel</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}" @selected($filters['department_id'] == $department->id)>{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <select name="employee_class" onchange="this.form.submit()" class="w-full min-w-[14rem] rounded-md border border-slate-300 px-2 py-2 text-sm focus:border-blue-400 focus:outline-none">
                                <option value="all" @selected(($filters['employee_class'] ?? 'all') === 'all')>All Employee Types</option>
                                <option value="regular" @selected(($filters['employee_class'] ?? '') === 'regular')>Regular Employees</option>
                                <option value="irregular" @selected(($filters['employee_class'] ?? '') === 'irregular')>Irregular Employees</option>
                            </select>
                        </div>
                    </form>
                </article>

                <article class="overflow-x-auto rounded-xl border border-slate-300 bg-white shadow-sm">
                    <table class="min-w-full text-left text-sm">
                        <thead class="border-b border-slate-300 bg-slate-50 text-xs uppercase tracking-wide text-slate-600">
                            <tr>
                                <th class="px-5 py-3">Employee</th>
                                <th class="px-4 py-3">Department</th>
                                <th class="px-4 py-3">Position</th>
                                <th class="px-4 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @forelse ($employees as $employee)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-[#00386f] text-xs font-semibold text-white">
                                                {{ strtoupper(substr($employee->first_name, 0, 1).substr($employee->last_name, 0, 1)) }}
                                            </span>
                                            <div>
                                                <p class="font-semibold text-slate-800">{{ $employee->full_name }}</p>
                                                <p class="text-xs text-slate-500">{{ $employee->email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-slate-700">{{ $employee->department?->name }}</td>
                                    <td class="px-4 py-4 text-slate-700">{{ $employee->position }}</td>
                                    <td class="px-4 py-4 text-slate-600">
                                        <div
                                            x-data="{
                                                open: false,
                                                x: 0,
                                                y: 0,
                                                triggerRect: null,
                                                deleting: false,
                                                resending: false,
                                                toggle(event) {
                                                    if (this.open) { this.open = false; return; }
                                                    this.triggerRect = event.currentTarget.getBoundingClientRect();
                                                    this.x = Math.max(8, Math.min(window.innerWidth - 188, this.triggerRect.right - 180));
                                                    this.y = this.triggerRect.bottom + 6;
                                                    this.open = true;
                                                    this.$nextTick(() => this.repositionMenu());
                                                },
                                                repositionMenu() {
                                                    const menu = this.$refs.menu;
                                                    if (!menu || !this.triggerRect) {
                                                        return;
                                                    }

                                                    const menuHeight = menu.offsetHeight;
                                                    const menuWidth = menu.offsetWidth;
                                                    const viewportHeight = window.innerHeight;
                                                    const viewportWidth = window.innerWidth;

                                                    const preferredX = this.triggerRect.right - menuWidth;
                                                    this.x = Math.max(8, Math.min(viewportWidth - menuWidth - 8, preferredX));

                                                    const belowY = this.triggerRect.bottom + 6;
                                                    const aboveY = this.triggerRect.top - menuHeight - 6;
                                                    const canOpenBelow = belowY + menuHeight <= viewportHeight - 8;

                                                    this.y = canOpenBelow ? belowY : Math.max(8, aboveY);
                                                },
                                                confirmDelete(event) {
                                                    if (!confirm('Permanently delete {{ $employee->full_name }} and all related records (login account, credentials, attendance, leave balances & leave requests)? This action cannot be undone.')) {
                                                        event.preventDefault();
                                                        return;
                                                    }
                                                    this.deleting = true;
                                                    this.open = false;
                                                },
                                                confirmResend(event) {
                                                    if (!confirm('Regenerate a new temporary password for {{ $employee->full_name }}? The old password will stop working.')) {
                                                        event.preventDefault();
                                                        return;
                                                    }
                                                    this.resending = true;
                                                    this.open = false;
                                                },
                                            }"
                                            @keydown.escape.window="open = false"
                                            @resize.window="open = false"
                                            @scroll.window="open = false"
                                            class="relative inline-block"
                                        >
                                            <button type="button" @click="toggle($event)" aria-haspopup="true" :aria-expanded="open" class="cursor-pointer rounded-md px-2 py-1 text-lg leading-none text-slate-600 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-300">
                                                <span aria-hidden="true">&hellip;</span>
                                                <span class="sr-only">Open actions for {{ $employee->full_name }}</span>
                                            </button>

                                            <template x-teleport="body">
                                                <div
                                                    x-show="open"
                                                    x-cloak
                                                    x-transition.opacity.duration.100ms
                                                    @click.outside="open = false"
                                                    x-ref="menu"
                                                    :style="`position: fixed; top: ${y}px; left: ${x}px; z-index: 80;`"
                                                    class="w-44 max-h-[calc(100vh-1rem)] overflow-y-auto rounded-xl border border-slate-200 bg-white p-2 shadow-xl"
                                                    role="menu"
                                                >
                                                    <a href="#"
                                                        data-open-modal="employee-details-modal"
                                                        data-employee-id="{{ $employee->id }}"
                                                        data-employee-employee-id="{{ $employee->employee_id }}"
                                                        data-employee-first-name="{{ $employee->first_name }}"
                                                        data-employee-last-name="{{ $employee->last_name }}"
                                                        data-employee-full-name="{{ $employee->full_name }}"
                                                        data-employee-email="{{ $employee->email }}"
                                                        data-employee-phone="{{ $employee->phone }}"
                                                        data-employee-address="{{ $employee->address }}"
                                                        data-employee-department-id="{{ $employee->department_id }}"
                                                        data-employee-department-name="{{ $employee->department?->name }}"
                                                        data-employee-position="{{ $employee->position }}"
                                                        data-employee-employment-type="{{ $employee->employment_type }}"
                                                        data-employee-ranking="{{ $employee->ranking }}"
                                                        data-employee-status="{{ $employee->status }}"
                                                        data-employee-hire-date="{{ $employee->hire_date?->format('Y-m-d') }}"
                                                        @click="open = false"
                                                        class="mb-1 block rounded-lg border border-slate-300 px-3 py-2 text-center text-sm font-semibold text-slate-800 hover:bg-slate-50">View Details</a>
                                                    <a href="#"
                                                        data-open-modal="employee-edit-modal"
                                                        data-employee-id="{{ $employee->id }}"
                                                        data-employee-employee-id="{{ $employee->employee_id }}"
                                                        data-employee-first-name="{{ $employee->first_name }}"
                                                        data-employee-last-name="{{ $employee->last_name }}"
                                                        data-employee-full-name="{{ $employee->full_name }}"
                                                        data-employee-email="{{ $employee->email }}"
                                                        data-employee-phone="{{ $employee->phone }}"
                                                        data-employee-address="{{ $employee->address }}"
                                                        data-employee-department-id="{{ $employee->department_id }}"
                                                        data-employee-department-name="{{ $employee->department?->name }}"
                                                        data-employee-position="{{ $employee->position }}"
                                                        data-employee-employment-type="{{ $employee->employment_type }}"
                                                        data-employee-ranking="{{ $employee->ranking }}"
                                                        data-employee-status="{{ $employee->status }}"
                                                        data-employee-hire-date="{{ $employee->hire_date?->format('Y-m-d') }}"
                                                        @click="open = false"
                                                        class="mb-1 block rounded-lg border border-slate-300 px-3 py-2 text-center text-sm font-semibold text-slate-800 hover:bg-slate-50">Edit</a>
                                                    <a href="{{ route('employees.profile') }}" @click="open = false" class="mb-1 block rounded-lg border border-slate-300 px-3 py-2 text-center text-sm font-semibold text-slate-800 hover:bg-slate-50">View Profile</a>
                                                    <form method="POST" action="{{ route('employees.resend-credentials', $employee) }}" @submit="confirmResend($event)">
                                                        @csrf
                                                        <button type="submit" :disabled="resending" class="mb-1 flex w-full items-center justify-center gap-2 rounded-lg border border-blue-300 px-3 py-2 text-center text-sm font-semibold text-blue-700 hover:bg-blue-50 disabled:cursor-not-allowed disabled:opacity-60">
                                                            <svg x-show="resending" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                                                            <span x-text="resending ? 'Sending…' : 'Resend Credentials'"></span>
                                                        </button>
                                                    </form>
                                                    <form method="POST" action="{{ route('employees.destroy', $employee) }}" @submit="confirmDelete($event)">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" :disabled="deleting" class="flex w-full items-center justify-center gap-2 rounded-lg border border-red-300 px-3 py-2 text-center text-sm font-semibold text-red-600 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-60">
                                                            <svg x-show="deleting" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                                                            <span x-text="deleting ? 'Deleting…' : 'Delete'"></span>
                                                        </button>
                                                    </form>
                                                </div>
                                            </template>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-6 text-center text-sm text-slate-500">No employee records found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </article>

                <div>
                    {{ $employees->links() }}
                </div>

                <div class="h-8"></div>
            </section>
        </main>
    </div>
    <div id="employee-add-modal" class="modal-overlay fixed inset-0 z-50 hidden items-start justify-center overflow-y-auto bg-black/45 p-4 py-6 sm:items-center">
        <div class="w-full max-w-4xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-2xl">
            <div class="flex items-start justify-between px-8 py-6">
                <div>
                    <h3 class="text-4xl font-bold text-[#1f2b5d]">Add New Employee</h3>
                    <p class="text-2xl text-slate-500">Enter the details of the new employee</p>
                </div>
                <button type="button" data-close-modal class="text-5xl leading-none text-slate-500 hover:text-slate-700">&times;</button>
            </div>

            <form method="POST" action="{{ route('employees.store') }}" data-employee-form class="grid grid-cols-1 gap-4 px-8 pb-8 md:grid-cols-2">
                @csrf
                <div class="md:col-span-2 rounded-md border border-dashed border-emerald-300 bg-emerald-50 px-3 py-3">
                    <p class="text-sm font-semibold text-emerald-800">Employee ID - Auto Generated</p>
                    <p class="text-sm text-emerald-700">Will be automatically generated based on the <strong>year of hire</strong>. Format: <span class="font-mono font-semibold">{{ date('Y') }}-XXX</span> (e.g. {{ date('y') }}-001, {{ date('y') }}-002...).</p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1f2b8b]">Email *</label>
                    <input name="email" type="email" placeholder="email@nu.edu.ph" class="w-full rounded-md border border-slate-300 px-4 py-2 text-lg" required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1f2b8b]">First Name *</label>
                    <input name="first_name" type="text" class="w-full rounded-md border border-slate-300 px-4 py-2 text-lg" required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1f2b8b]">Last Name *</label>
                    <input name="last_name" type="text" class="w-full rounded-md border border-slate-300 px-4 py-2 text-lg" required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1f2b8b]">Mobile Number</label>
                    <div class="flex w-full overflow-hidden rounded-md border border-slate-300 focus-within:border-blue-400">
                        <span class="inline-flex items-center border-r border-slate-300 bg-slate-50 px-3 text-base font-semibold text-slate-600">+63</span>
                        <input
                            name="phone"
                            type="tel"
                            inputmode="numeric"
                            pattern="\d{10}"
                            maxlength="10"
                            autocomplete="tel-national"
                            placeholder="9987654321"
                            oninput="this.value = this.value.replace(/\D/g, '').slice(0, 10);"
                            class="w-full border-0 px-4 py-2 text-lg focus:outline-none"
                        >
                    </div>
                    <p class="mt-1 text-xs text-slate-500">Enter 10 digits only. Example: +63 998 765 4321</p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1f2b8b]">Address</label>
                    <input name="address" type="text" placeholder="Complete address" class="w-full rounded-md border border-slate-300 px-4 py-2 text-lg">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1f2b8b]">Employee Type *</label>
                    <select name="employment_type" data-employee-control="employment_type" class="w-full rounded-md border border-slate-300 px-4 py-2 text-lg text-slate-700" required>
                        <option value="">Select type</option>
                        @foreach ($employmentTypes as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1f2b8b]">Position *</label>
                    <select name="position" data-employee-control="position" class="w-full rounded-md border border-slate-300 px-4 py-2 text-lg text-slate-700" required>
                        <option value="">Select Position / Office</option>
                        @foreach ($facultyPositions as $position)
                            <option value="{{ $position }}" data-employment-category="faculty">{{ $position }}</option>
                        @endforeach
                        @foreach ($aspPositions as $position)
                            <option value="{{ $position }}" data-employment-category="asp">{{ $position }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Options are filtered by the selected Employee Type.</p>
                </div>
                <div data-employee-field="department">
                    <label class="mb-1 block text-sm font-semibold text-[#1f2b8b]">Department *</label>
                    <select name="department_id" data-employee-control="department" class="w-full rounded-md border border-slate-300 px-4 py-2 text-lg text-slate-500">
                        <option value="">Select Department</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div data-employee-field="ranking">
                    <label class="mb-1 block text-sm font-semibold text-[#1f2b8b]">Faculty Ranking</label>
                    <select name="ranking" data-employee-control="ranking" class="w-full rounded-md border border-slate-300 px-4 py-2 text-lg text-slate-700">
                        <option value="">N/A</option>
                        @foreach ($facultyRankings as $ranking)
                            <option value="{{ $ranking }}">{{ $ranking }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1f2b8b]">Hire Date</label>
                    <input name="hire_date" type="date" class="w-full rounded-md border border-slate-300 px-4 py-2 text-lg">
                </div>

                <div class="md:col-span-2 rounded-md border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                    <p class="font-semibold">Login account will be auto-created</p>
                    <p class="mt-1 text-blue-700">After saving, NU HRIS will generate a temporary password for this employee and show it here so you can share it with them.</p>
                </div>

                <div class="md:col-span-2 mt-2 flex justify-end gap-3">
                    <button type="button" data-close-modal class="rounded-md border border-slate-300 px-5 py-2 text-lg font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="rounded-md bg-[#00386f] px-5 py-2 text-lg font-semibold text-white hover:bg-[#002f5d]">Add Employee</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ADD EXISTING EMPLOYEE MODAL --}}
    <div id="employee-add-existing-modal" class="modal-overlay fixed inset-0 z-50 hidden items-start justify-center overflow-y-auto bg-black/45 p-4 py-6 sm:items-center">
        <div class="w-full max-w-4xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-2xl">
            <div class="flex items-start justify-between px-8 py-6">
                <div>
                    <h3 class="text-4xl font-bold text-[#1f2b5d]">Add Existing Employee</h3>
                    <p class="text-2xl text-slate-500">Register an employee using their existing ID</p>
                </div>
                <button type="button" data-close-modal class="text-5xl leading-none text-slate-500 hover:text-slate-700">&times;</button>
            </div>

            <form method="POST" action="{{ route('employees.store') }}" data-employee-form class="grid grid-cols-1 gap-4 px-8 pb-8 md:grid-cols-2">
                @csrf
                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-semibold text-[#1f2b8b]">Employee ID *</label>
                    <input name="employee_id" type="text" placeholder="e.g. 23-8035 or 238035" class="w-full rounded-md border-2 border-blue-300 bg-blue-50 px-4 py-2 text-lg font-mono" required>
                    <p class="mt-1 text-xs text-slate-500">Enter the existing Employee ID. Format: <span class="font-mono font-medium">YY-XXXXX</span> (e.g. 23-8035, 24-08052). The system accepts both with and without dash.</p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1f2b8b]">Email *</label>
                    <input name="email" type="email" placeholder="email@nu.edu.ph" class="w-full rounded-md border border-slate-300 px-4 py-2 text-lg" required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1f2b8b]">First Name *</label>
                    <input name="first_name" type="text" class="w-full rounded-md border border-slate-300 px-4 py-2 text-lg" required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1f2b8b]">Last Name *</label>
                    <input name="last_name" type="text" class="w-full rounded-md border border-slate-300 px-4 py-2 text-lg" required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1f2b8b]">Mobile Number</label>
                    <div class="flex w-full overflow-hidden rounded-md border border-slate-300 focus-within:border-blue-400">
                        <span class="inline-flex items-center border-r border-slate-300 bg-slate-50 px-3 text-base font-semibold text-slate-600">+63</span>
                        <input
                            name="phone"
                            type="tel"
                            inputmode="numeric"
                            pattern="\d{10}"
                            maxlength="10"
                            autocomplete="tel-national"
                            placeholder="9987654321"
                            oninput="this.value = this.value.replace(/\D/g, '').slice(0, 10);"
                            class="w-full border-0 px-4 py-2 text-lg focus:outline-none"
                        >
                    </div>
                    <p class="mt-1 text-xs text-slate-500">Enter 10 digits only. Example: +63 998 765 4321</p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1f2b8b]">Address</label>
                    <input name="address" type="text" placeholder="Complete address" class="w-full rounded-md border border-slate-300 px-4 py-2 text-lg">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1f2b8b]">Employee Type *</label>
                    <select name="employment_type" data-employee-control="employment_type" class="w-full rounded-md border border-slate-300 px-4 py-2 text-lg text-slate-700" required>
                        <option value="">Select type</option>
                        @foreach ($employmentTypes as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1f2b8b]">Position *</label>
                    <select name="position" data-employee-control="position" class="w-full rounded-md border border-slate-300 px-4 py-2 text-lg text-slate-700" required>
                        <option value="">Select Position / Office</option>
                        @foreach ($facultyPositions as $position)
                            <option value="{{ $position }}" data-employment-category="faculty">{{ $position }}</option>
                        @endforeach
                        @foreach ($aspPositions as $position)
                            <option value="{{ $position }}" data-employment-category="asp">{{ $position }}</option>
                        @endforeach
                    </select>
                </div>
                <div data-employee-field="department">
                    <label class="mb-1 block text-sm font-semibold text-[#1f2b8b]">Department *</label>
                    <select name="department_id" data-employee-control="department" class="w-full rounded-md border border-slate-300 px-4 py-2 text-lg text-slate-500">
                        <option value="">Select Department</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div data-employee-field="ranking">
                    <label class="mb-1 block text-sm font-semibold text-[#1f2b8b]">Faculty Ranking</label>
                    <select name="ranking" data-employee-control="ranking" class="w-full rounded-md border border-slate-300 px-4 py-2 text-lg text-slate-700">
                        <option value="">N/A</option>
                        @foreach ($facultyRankings as $ranking)
                            <option value="{{ $ranking }}">{{ $ranking }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1f2b8b]">Hire Date</label>
                    <input name="hire_date" type="date" class="w-full rounded-md border border-slate-300 px-4 py-2 text-lg">
                </div>

                <div class="md:col-span-2 rounded-md border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                    <p class="font-semibold">Login account will be auto-created</p>
                    <p class="mt-1 text-blue-700">After saving, NU HRIS will generate a temporary password for this employee.</p>
                </div>

                <div class="md:col-span-2 mt-2 flex justify-end gap-3">
                    <button type="button" data-close-modal class="rounded-md border border-slate-300 px-5 py-2 text-lg font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="rounded-md bg-[#00386f] px-5 py-2 text-lg font-semibold text-white hover:bg-[#002f5d]">Add Existing Employee</button>
                </div>
            </form>
        </div>
    </div>

    <div id="employee-details-modal" class="modal-overlay fixed inset-0 z-50 hidden items-start justify-center overflow-y-auto bg-black/45 p-4 py-6 sm:items-center">
        <div class="w-full max-w-3xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-2xl">
            <div class="flex items-start justify-between border-b border-slate-300 px-6 py-4">
                <div>
                    <h3 class="text-2xl font-bold text-[#1f2b5d]">Employee Details</h3>
                </div>
                <button type="button" data-close-modal class="text-4xl leading-none text-slate-500 hover:text-slate-700">&times;</button>
            </div>
            <div class="border-b border-slate-300 px-6 py-5">
                <div class="flex items-center gap-4">
                    <span id="details-initials" class="inline-flex h-14 w-14 items-center justify-center rounded-full bg-[#00386f] text-2xl text-white">MS</span>
                    <div>
                        <p id="details-full-name" class="text-4xl font-bold text-[#1f2b8b]">Maria Santos</p>
                        <p id="details-position" class="text-2xl text-slate-600">Associate Professor</p>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-5 px-6 py-6 text-slate-800 md:grid-cols-2">
                <div>
                    <p class="text-base text-slate-500">Employee ID</p>
                    <p id="details-employee-id" class="text-2xl">NU-2021-001</p>
                </div>
                <div>
                    <p class="text-base text-slate-500">Department</p>
                    <p id="details-department" class="text-2xl">College of Computing</p>
                </div>
                <div>
                    <p class="text-base text-slate-500">Email</p>
                    <p id="details-email" class="text-2xl">maria.santos@nu.edu.ph</p>
                </div>
                <div>
                    <p class="text-base text-slate-500">Phone</p>
                    <p id="details-phone" class="text-2xl">+63 917 123 4567</p>
                </div>
                <div>
                    <p class="text-base text-slate-500">Address</p>
                    <p id="details-address" class="text-2xl">N/A</p>
                </div>
                <div>
                    <p class="text-base text-slate-500">Employment Type</p>
                    <p id="details-employment-type" class="text-2xl">Full-time Faculty</p>
                </div>
                <div>
                    <p class="text-base text-slate-500">Ranking</p>
                    <p id="details-ranking" class="text-2xl">Associate Professor II</p>
                </div>
                <div>
                    <p class="text-base text-slate-500">Hire Date</p>
                    <p id="details-hire-date" class="text-2xl">Jun 15, 2021</p>
                </div>
                <div>
                    <p class="text-base text-slate-500">Official Time</p>
                    <p id="details-official-time" class="text-2xl">08:30 - 17:30</p>
                </div>
                <div>
                    <p class="text-base text-slate-500">Status</p>
                    <p id="details-status" class="text-2xl">Active</p>
                </div>
            </div>
        </div>
    </div>

    <div id="employee-edit-modal" class="modal-overlay fixed inset-0 z-50 hidden items-start justify-center overflow-y-auto bg-black/45 p-4 py-6 sm:items-center">
        <div class="w-full max-w-4xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-2xl">
            <div class="flex items-start justify-between px-8 py-6">
                <div>
                    <h3 class="text-4xl font-bold text-[#1f2b5d]">Edit Employee</h3>
                    <p class="text-2xl text-slate-500">Update employee information</p>
                </div>
                <button type="button" data-close-modal class="text-5xl leading-none text-slate-500 hover:text-slate-700">&times;</button>
            </div>

            <form id="employee-edit-form" method="POST" data-employee-form class="grid grid-cols-1 gap-4 px-8 pb-8 md:grid-cols-2">
                @csrf
                @method('PUT')
                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1f2b8b]">Employee ID</label>
                    <input id="edit-employee-id" name="employee_id" type="text" value="NU-2021-001" class="w-full rounded-md border border-slate-300 px-4 py-2 text-lg" required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1f2b8b]">Email *</label>
                    <input id="edit-email" name="email" type="email" value="maria.santos@nu.edu.ph" class="w-full rounded-md border border-slate-300 px-4 py-2 text-lg" required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1f2b8b]">First Name *</label>
                    <input id="edit-first-name" name="first_name" type="text" value="Maria" class="w-full rounded-md border border-slate-300 px-4 py-2 text-lg" required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1f2b8b]">Last Name *</label>
                    <input id="edit-last-name" name="last_name" type="text" value="Santos" class="w-full rounded-md border border-slate-300 px-4 py-2 text-lg" required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1f2b8b]">Phone</label>
                    <input id="edit-phone" name="phone" type="text" value="+63 917 123 4567" class="w-full rounded-md border border-slate-300 px-4 py-2 text-lg">
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-semibold text-[#1f2b8b]">Address</label>
                    <input id="edit-address" name="address" type="text" value="" class="w-full rounded-md border border-slate-300 px-4 py-2 text-lg">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1f2b8b]">Employee Type *</label>
                    <select id="edit-employment-type" name="employment_type" data-employee-control="employment_type" class="w-full rounded-md border border-slate-300 px-4 py-2 text-lg text-slate-700" required>
                        @foreach ($employmentTypes as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1f2b8b]">Position *</label>
                    <select id="edit-position" name="position" data-employee-control="position" class="w-full rounded-md border border-slate-300 px-4 py-2 text-lg text-slate-700" required>
                        @foreach ($facultyPositions as $position)
                            <option value="{{ $position }}" data-employment-category="faculty">{{ $position }}</option>
                        @endforeach
                        @foreach ($aspPositions as $position)
                            <option value="{{ $position }}" data-employment-category="asp">{{ $position }}</option>
                        @endforeach
                    </select>
                </div>
                <div data-employee-field="department">
                    <label class="mb-1 block text-sm font-semibold text-[#1f2b8b]">Department *</label>
                    <select id="edit-department-id" name="department_id" data-employee-control="department" class="w-full rounded-md border border-slate-300 px-4 py-2 text-lg text-slate-500">
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div data-employee-field="ranking">
                    <label class="mb-1 block text-sm font-semibold text-[#1f2b8b]">Faculty Ranking</label>
                    <select id="edit-ranking" name="ranking" data-employee-control="ranking" class="w-full rounded-md border border-slate-300 px-4 py-2 text-lg text-slate-700">
                        <option value="">N/A</option>
                        @foreach ($facultyRankings as $ranking)
                            <option value="{{ $ranking }}">{{ $ranking }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-[#1f2b8b]">Hire Date</label>
                    <input id="edit-hire-date" name="hire_date" type="date" value="2021-06-15" class="w-full rounded-md border border-slate-300 px-4 py-2 text-lg">
                </div>

                <div class="md:col-span-2 rounded-md border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                    <p class="font-semibold">Password will stay as-is</p>
                    <p class="mt-1 text-blue-700">Editing employee details here will not overwrite the employee's current password. Password changes only happen when HR explicitly clicks <strong>Resend Credentials</strong>.</p>
                </div>

                <div class="md:col-span-2 mt-2 flex justify-end gap-3">
                    <button type="button" data-close-modal class="rounded-md border border-slate-300 px-5 py-2 text-lg font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="rounded-md bg-[#00386f] px-5 py-2 text-lg font-semibold text-white hover:bg-[#002f5d]">Edit Employee</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const hasValidationErrors = {{ $errors->any() ? 'true' : 'false' }};
        const closers = document.querySelectorAll('[data-close-modal]');
        const modals = document.querySelectorAll('.modal-overlay');

        const employeeEditForm = document.getElementById('employee-edit-form');
        const detailsInitials = document.getElementById('details-initials');
        const detailsFullName = document.getElementById('details-full-name');
        const detailsPosition = document.getElementById('details-position');
        const detailsEmployeeId = document.getElementById('details-employee-id');
        const detailsDepartment = document.getElementById('details-department');
        const detailsEmail = document.getElementById('details-email');
        const detailsPhone = document.getElementById('details-phone');
        const detailsAddress = document.getElementById('details-address');
        const detailsEmploymentType = document.getElementById('details-employment-type');
        const detailsRanking = document.getElementById('details-ranking');
        const detailsHireDate = document.getElementById('details-hire-date');
        const detailsOfficialTime = document.getElementById('details-official-time');
        const detailsStatus = document.getElementById('details-status');
        const editEmployeeId = document.getElementById('edit-employee-id');
        const editEmail = document.getElementById('edit-email');
        const editFirstName = document.getElementById('edit-first-name');
        const editLastName = document.getElementById('edit-last-name');
        const editPhone = document.getElementById('edit-phone');
        const editAddress = document.getElementById('edit-address');
        const editDepartmentId = document.getElementById('edit-department-id');
        const editPosition = document.getElementById('edit-position');
        const editEmploymentType = document.getElementById('edit-employment-type');
        const editRanking = document.getElementById('edit-ranking');
        const editHireDate = document.getElementById('edit-hire-date');
        const editOfficialTimeIn = document.getElementById('edit-official-time-in');
        const editOfficialTimeOut = document.getElementById('edit-official-time-out');

        function formatDateForDetails(dateValue) {
            if (!dateValue) return 'N/A';
            const date = new Date(dateValue);
            if (Number.isNaN(date.getTime())) return 'N/A';
            return date.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
        }

        function populateEmployeeModals(trigger) {
            const employeeId = trigger.dataset.employeeId || '';
            const employeeCode = trigger.dataset.employeeEmployeeId || '';
            const fullName = trigger.dataset.employeeFullName || '';
            const firstName = trigger.dataset.employeeFirstName || '';
            const lastName = trigger.dataset.employeeLastName || '';
            const email = trigger.dataset.employeeEmail || '';
            const phone = trigger.dataset.employeePhone || 'N/A';
            const address = trigger.dataset.employeeAddress || 'N/A';
            const departmentName = trigger.dataset.employeeDepartmentName || 'N/A';
            const departmentId = trigger.dataset.employeeDepartmentId || '';
            const position = trigger.dataset.employeePosition || '';
            const employmentType = trigger.dataset.employeeEmploymentType || 'N/A';
            const ranking = trigger.dataset.employeeRanking || 'N/A';
            const status = trigger.dataset.employeeStatus || 'active';
            const hireDate = trigger.dataset.employeeHireDate || '';
            const officialTimeIn = trigger.dataset.employeeOfficialTimeIn || '';
            const officialTimeOut = trigger.dataset.employeeOfficialTimeOut || '';

            const initials = `${(firstName[0] || '').toUpperCase()}${(lastName[0] || '').toUpperCase()}`;

            if (detailsInitials) detailsInitials.textContent = initials || 'NA';
            if (detailsFullName) detailsFullName.textContent = fullName || 'N/A';
            if (detailsPosition) detailsPosition.textContent = position || 'N/A';
            if (detailsEmployeeId) detailsEmployeeId.textContent = employeeCode || 'N/A';
            if (detailsDepartment) detailsDepartment.textContent = departmentName;
            if (detailsEmail) detailsEmail.textContent = email || 'N/A';
            if (detailsPhone) detailsPhone.textContent = phone || 'N/A';
            if (detailsAddress) detailsAddress.textContent = address || 'N/A';
            if (detailsEmploymentType) detailsEmploymentType.textContent = employmentType || 'N/A';
            if (detailsRanking) detailsRanking.textContent = ranking || 'N/A';
            if (detailsHireDate) detailsHireDate.textContent = formatDateForDetails(hireDate);
            if (detailsOfficialTime) {
                detailsOfficialTime.textContent = officialTimeIn && officialTimeOut
                    ? `${officialTimeIn} - ${officialTimeOut}`
                    : 'N/A';
            }
            if (detailsStatus) detailsStatus.textContent = status ? status.replaceAll('_', ' ').replace(/\b\w/g, (char) => char.toUpperCase()) : 'N/A';
            if (employeeEditForm && employeeId) {
                employeeEditForm.action = `/hr/employees/${employeeId}`;
            }
            if (editEmployeeId) editEmployeeId.value = employeeCode;
            if (editEmail) editEmail.value = email;
            if (editFirstName) editFirstName.value = firstName;
            if (editLastName) editLastName.value = lastName;
            if (editPhone) editPhone.value = phone === 'N/A' ? '' : phone;
            if (editAddress) editAddress.value = address === 'N/A' ? '' : address;
            if (editDepartmentId) editDepartmentId.value = departmentId;
            if (editEmploymentType) editEmploymentType.value = employmentType === 'N/A' ? '' : employmentType;
            if (editEmploymentType) {
                editEmploymentType.dispatchEvent(new Event('change', { bubbles: true }));
            }
            if (editPosition) editPosition.value = position;
            if (editPosition) {
                editPosition.dispatchEvent(new Event('change', { bubbles: true }));
            }
            if (editRanking) editRanking.value = ranking === 'N/A' ? '' : ranking;
            if (editHireDate) editHireDate.value = hireDate;
            if (editOfficialTimeIn) editOfficialTimeIn.value = officialTimeIn;
            if (editOfficialTimeOut) editOfficialTimeOut.value = officialTimeOut;
            if (editPosition) {
                editPosition.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        function closeAllModals() {
            modals.forEach((modal) => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            });
            document.body.classList.remove('overflow-hidden');
        }

        document.addEventListener('click', (event) => {
            const trigger = event.target.closest('[data-open-modal]');

            if (!trigger) {
                return;
            }

            event.preventDefault();

            const modalId = trigger.getAttribute('data-open-modal');
            const modal = document.getElementById(modalId);

            if (!modal) {
                return;
            }

            if (trigger.dataset.employeeId) {
                populateEmployeeModals(trigger);
            }

            closeAllModals();
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        });

        closers.forEach((trigger) => {
            trigger.addEventListener('click', closeAllModals);
        });

        modals.forEach((modal) => {
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeAllModals();
                }
            });
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeAllModals();
            }
        });

        if (hasValidationErrors) {
            const addModal = document.getElementById('employee-add-modal');

            if (addModal) {
                addModal.classList.remove('hidden');
                addModal.classList.add('flex');
                document.body.classList.add('overflow-hidden');
            }
        }

        document.querySelectorAll('[data-copy-credentials]').forEach((button) => {
            button.addEventListener('click', async () => {
                const email = button.dataset.credentialEmail || '';
                const password = button.dataset.credentialPassword || '';
                const text = `Email: ${email}\nTemporary Password: ${password}`;

                try {
                    if (navigator.clipboard && window.isSecureContext) {
                        await navigator.clipboard.writeText(text);
                    } else {
                        const textarea = document.createElement('textarea');
                        textarea.value = text;
                        textarea.style.position = 'fixed';
                        textarea.style.opacity = '0';
                        document.body.appendChild(textarea);
                        textarea.select();
                        document.execCommand('copy');
                        document.body.removeChild(textarea);
                    }
                    const original = button.textContent;
                    button.textContent = 'Copied!';
                    setTimeout(() => { button.textContent = original; }, 1500);
                } catch (error) {
                    alert('Unable to copy. Please copy the credentials manually.');
                }
            });
        });
    </script>

    @auth
        @include('partials.logout-modal')
    @endauth
</body>
</html>
