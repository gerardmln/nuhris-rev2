@extends('admin.layout')

@section('title', 'Employees')
@section('page_title', 'Employees')

@section('content')
    <div class="space-y-5 px-5 py-5 sm:px-6 sm:py-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-sm text-slate-500">Manage faculty and staff records</p>
            </div>
            <a href="{{ route('admin.employees.create') }}" class="rounded-lg bg-[#00386f] px-4 py-2 text-sm font-semibold text-white hover:bg-[#002f5d]">+ Add Employee</a>
        </div>

        <article class="rounded-xl border border-slate-300 bg-white p-3 shadow-sm">
            <form method="GET" action="{{ route('admin.employees.index') }}" class="grid grid-cols-1 gap-2 md:grid-cols-8">
                <div class="md:col-span-3">
                    <input
                        type="text"
                        name="search"
                        value="{{ $filters['search'] ?? '' }}"
                        placeholder="Search by name, email, ID, or department..."
                        class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none"
                    >
                </div>
                <div class="md:col-span-2">
                    <select name="department_id" onchange="this.form.submit()" class="w-full rounded-md border border-slate-300 px-2 py-2 text-sm focus:border-blue-400 focus:outline-none">
                        <option value="">All Departments</option>
                        <option value="asp" @selected(($filters['department_id'] ?? '') === 'asp')>Admin Support Personnel</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected(($filters['department_id'] ?? '') == $department->id)>{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select name="employee_class" onchange="this.form.submit()" class="w-full min-w-[14rem] rounded-md border border-slate-300 px-2 py-2 text-sm focus:border-blue-400 focus:outline-none">
                        <option value="all" @selected(($filters['employee_class'] ?? 'all') === 'all')>All Employee Types</option>
                        <option value="regular" @selected(($filters['employee_class'] ?? '') === 'regular')>Full - Time Employees</option>
                        <option value="irregular" @selected(($filters['employee_class'] ?? '') === 'irregular')>Probationary Employees</option>
                    </select>
                </div>
                <div>
                    <select name="status" onchange="this.form.submit()" class="w-full rounded-md border border-slate-300 px-2 py-2 text-sm focus:border-blue-400 focus:outline-none">
                        <option value="all" @selected(($filters['status'] ?? 'all') === 'all')>All Statuses</option>
                        <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                        <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div>
                    <input type="text" name="position" value="{{ $filters['position'] ?? '' }}" placeholder="Position" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none">
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
                        <th class="px-4 py-3">Status</th>
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
                            <td class="px-4 py-4 text-slate-700">{{ $employee->department?->name ?? 'Unassigned' }}</td>
                            <td class="px-4 py-4 text-slate-700">{{ $employee->position ?? '—' }}</td>
                            <td class="px-4 py-4 text-slate-700">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $employee->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                                    {{ ucfirst($employee->status) }}
                                </span>
                            </td>
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
                                            if (!confirm('Permanently delete {{ $employee->full_name }} and all related records? This action cannot be undone.')) {
                                                event.preventDefault();
                                                return;
                                            }
                                            this.deleting = true;
                                            this.open = false;
                                        },
                                        confirmResend(event) {
                                            if (!confirm('Regenerate credentials for {{ $employee->full_name }}? The old password will stop working.')) {
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
                                            class="w-48 max-h-[calc(100vh-1rem)] overflow-y-auto rounded-xl border border-slate-200 bg-white p-2 shadow-xl"
                                            role="menu"
                                        >
                                            <a href="{{ route('admin.employees.show', $employee) }}" @click="open = false" class="mb-1 block rounded-lg border border-slate-300 px-3 py-2 text-center text-sm font-semibold text-slate-800 hover:bg-slate-50">View Profile</a>
                                            <a href="{{ route('admin.employees.edit', $employee) }}" @click="open = false" class="mb-1 block rounded-lg border border-blue-300 bg-blue-50 px-3 py-2 text-center text-sm font-semibold text-blue-800 hover:bg-blue-100">Edit Details</a>
                                            <form method="POST" action="{{ route('admin.employees.resend-credentials', $employee) }}" @submit="confirmResend($event)" class="mb-1">
                                                @csrf
                                                <button type="submit" :disabled="resending" class="w-full rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-center text-sm font-semibold text-amber-800 hover:bg-amber-100 disabled:opacity-50">
                                                    <span x-show="!resending">Resend Credentials</span>
                                                    <span x-show="resending">Sending...</span>
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.employees.destroy', $employee) }}" @submit="confirmDelete($event)">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" :disabled="deleting" class="w-full rounded-lg border border-red-300 bg-red-50 px-3 py-2 text-center text-sm font-semibold text-red-800 hover:bg-red-100 disabled:opacity-50">
                                                    <span x-show="!deleting">Delete Employee</span>
                                                    <span x-show="deleting">Deleting...</span>
                                                </button>
                                            </form>
                                        </div>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-6 text-center text-sm text-slate-500">No employee records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </article>

        <div>
            {{ $employees->links() }}
        </div>

        <div class="h-8"></div>
    </div>
@endsection
