<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Announcements | {{ config('app.name', 'NU HRIS') }}</title>
    @include('partials.favicon')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#eceef1] text-slate-900 antialiased overflow-x-hidden overflow-y-auto">
    <div class="flex min-h-screen flex-col lg:flex-row">
        @include('partials.hr-sidebar', ['activeNav' => 'announcements'])

        <main class="min-h-screen flex-1">
            <header class="border-b border-slate-300 bg-white px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-[30px] font-bold leading-none text-[#1f2b5d]">Announcements</h1>
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

                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="text-3xl font-bold text-[#1f2b5d]">Announcements</h2>
                        <p class="text-sm text-slate-500">Create and manage official HR announcements</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('announcements.clear-all') }}" onsubmit="return confirm('Clear all announcements? This will permanently remove them and their notifications.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-lg border border-red-300 bg-white px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">Clear All</button>
                        </form>
                        <button data-open-modal="new-announcement-modal" class="rounded-lg bg-[#00386f] px-4 py-2 text-sm font-semibold text-white hover:bg-[#002f5d]">+ New Announcement</button>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
                        <p class="text-xs font-medium text-slate-500">Total</p>
                        <p class="mt-1 text-4xl font-extrabold">{{ $stats['total'] }}</p>
                    </article>
                    <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
                        <p class="text-xs font-medium text-slate-500">Active</p>
                        <p class="mt-1 text-4xl font-extrabold">{{ $stats['active'] }}</p>
                    </article>
                    <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
                        <p class="text-xs font-medium text-slate-500">Urgent</p>
                        <p class="mt-1 text-4xl font-extrabold">{{ $stats['urgent'] }}</p>
                    </article>
                    <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
                        <p class="text-xs font-medium text-slate-500">Current Month</p>
                        <p class="mt-1 text-4xl font-extrabold">{{ $stats['current_month'] }}</p>
                    </article>
                </div>

                <article class="rounded-xl border border-slate-300 bg-white p-3 shadow-sm">
                    <form method="GET" action="{{ route('announcements.index') }}" class="grid grid-cols-1 gap-2 md:grid-cols-3">
                        <div class="md:col-span-2">
                            <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Search announcements..." class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-400 focus:outline-none">
                        </div>
                        <div class="flex gap-2">
                            <select name="priority" class="w-full rounded-md border border-slate-300 px-2 py-2 text-sm focus:border-blue-400 focus:outline-none">
                                <option value="">All Types</option>
                                <option value="high" @selected($filters['priority'] === 'high')>Urgent</option>
                                <option value="medium" @selected($filters['priority'] === 'medium')>General</option>
                                <option value="low" @selected($filters['priority'] === 'low')>Reminder</option>
                            </select>
                            <button type="submit" class="rounded-md bg-[#00386f] px-3 py-2 text-xs font-semibold text-white hover:bg-[#002f5d]">Filter</button>
                        </div>
                    </form>
                </article>

                <div class="space-y-4">
                    @forelse ($announcements as $announcement)
                        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
                            <div class="mb-2 flex items-start justify-between gap-3">
                                <div class="flex flex-wrap items-center gap-1">
                                    <span class="rounded border border-red-200 bg-red-50 px-2 py-0.5 text-[10px] font-semibold text-red-700">{{ ucfirst($announcement->priority) }}</span>
                                    <span class="rounded border border-slate-200 bg-slate-50 px-2 py-0.5 text-[10px] font-semibold text-slate-700">{{ $announcement->audience_label }}</span>
                                </div>
                                <details class="relative">
                                    <summary class="cursor-pointer list-none rounded-md px-2 py-1 text-lg leading-none text-slate-500 hover:bg-slate-100">...</summary>
                                    <div class="absolute right-0 z-20 mt-2 w-32 rounded-xl border border-slate-200 bg-white p-1.5 shadow-lg">
                                        <form method="POST" action="{{ route('announcements.destroy', $announcement) }}" onsubmit="return confirm('Delete this announcement?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="block w-full rounded-lg border border-red-300 px-3 py-1.5 text-center text-xs font-semibold text-red-600 hover:bg-red-50">Delete</button>
                                        </form>
                                    </div>
                                </details>
                            </div>
                            <h3 class="text-xl font-bold text-slate-800">{{ $announcement->title }}</h3>
                            <p class="mt-2 text-sm text-slate-600">{{ $announcement->content }}</p>
                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $announcement->priority_badge_class }}">{{ $announcement->priority_label }}</span>
                                @if ($announcement->is_expired)
                                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">Expired</span>
                                @endif
                            </div>
                            <div class="mt-3 flex flex-wrap gap-4 text-xs text-slate-500">
                                <span>{{ $announcement->published_at?->format('M d, Y') ?? $announcement->created_at->format('M d, Y') }}</span>
                                @if ($announcement->expires_at)
                                    <span>Expires: {{ $announcement->expires_at->format('M d, Y') }}</span>
                                @endif
                            </div>
                        </article>
                    @empty
                        <article class="rounded-xl border border-slate-300 bg-white p-8 text-center text-sm text-slate-500 shadow-sm">
                            No announcements yet.
                        </article>
                    @endforelse
                </div>

                <div>
                    {{ $announcements->links() }}
                </div>

                <div class="h-8"></div>
            </section>
        </main>
    </div>

    <div id="new-announcement-modal" class="fixed inset-0 z-50 hidden items-start justify-center overflow-y-auto bg-black/45 p-4 py-6 sm:items-center">
        <div class="w-full max-w-4xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-2xl">
            <div class="flex items-start justify-between px-8 py-6">
                <div>
                    <h3 class="text-4xl font-bold text-[#1f2b8b]">New Announcement</h3>
                    <p class="text-xl text-slate-500">Create a new announcement for employees</p>
                </div>
                <button type="button" data-close-modal class="text-4xl leading-none text-slate-500 hover:text-slate-700">&times;</button>
            </div>

            <form method="POST" action="{{ route('announcements.store') }}" class="px-8 pb-8">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="mb-1 block text-lg font-semibold text-[#1f2b8b]">Title *</label>
                        <input name="title" type="text" placeholder="Announcement title" class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-lg focus:border-blue-400 focus:outline-none" required>
                    </div>

                    <div>
                        <label class="mb-1 block text-lg font-semibold text-[#1f2b8b]">Content *</label>
                        <textarea name="content" rows="4" placeholder="Write your announcement here..." class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-lg focus:border-blue-400 focus:outline-none" required></textarea>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-lg font-semibold text-[#1f2b8b]">Priority</label>
                            <select name="priority" class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-lg focus:border-blue-400 focus:outline-none">
                                <option value="medium">Medium</option>
                                <option value="low">Low</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-lg font-semibold text-[#1f2b8b]">Target Employee</label>
                            <select name="target_employee_type" data-target-control="employee_type" class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-lg focus:border-blue-400 focus:outline-none">
                                <option value="">All Employees</option>
                                <option value="faculty">Faculty</option>
                                <option value="admin_support">Admin Support Personnel</option>
                            </select>
                            <p class="mt-1 text-xs text-slate-500">Used to show the correct position, department, and ranking filters.</p>
                        </div>
                        <div data-target-field="department" class="hidden">
                            <label class="mb-1 block text-lg font-semibold text-[#1f2b8b]">Target Department</label>
                            <select name="target_department_id" data-target-control="department" class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-lg focus:border-blue-400 focus:outline-none">
                                <option value="">All Departments</option>
                                @foreach ($facultyDepartments as $department)
                                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div data-target-field="filters" class="hidden space-y-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div>
                            <label class="mb-1 block text-lg font-semibold text-[#1f2b8b]">Target Position / Office</label>
                            <select name="target_office" data-target-control="position" class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-lg focus:border-blue-400 focus:outline-none">
                                <option value="">All Positions / Offices</option>
                                @foreach ($facultyPositions as $position)
                                    <option value="{{ $position }}" data-target-employee-type="faculty">{{ $position }}</option>
                                @endforeach
                                @foreach ($adminSupportOffices as $office)
                                    <option value="{{ $office }}" data-target-employee-type="admin_support">{{ $office }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div data-target-field="ranking" class="hidden">
                            <label class="mb-1 block text-lg font-semibold text-[#1f2b8b]">Target Faculty Ranking</label>
                            <select name="target_ranking" data-target-control="ranking" class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-lg focus:border-blue-400 focus:outline-none">
                                <option value="">All Rankings</option>
                                @foreach ($facultyRankings as $ranking)
                                    <option value="{{ $ranking }}">{{ $ranking }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-lg font-semibold text-[#1f2b8b]">Expiry Date</label>
                        <input name="expires_at" type="date" class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-lg focus:border-blue-400 focus:outline-none" required>
                    </div>
                </div>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-end">
                    <button type="button" data-close-modal class="rounded-md border border-slate-400 px-6 py-2 text-lg font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="rounded-md bg-[#00386f] px-6 py-2 text-lg font-semibold text-white hover:bg-[#002f5d]">Post announcement</button>
                </div>
            </form>
        </div>
    </div>

    <div id="edit-announcement-modal" class="fixed inset-0 z-50 hidden items-start justify-center overflow-y-auto bg-black/45 p-4 py-6 sm:items-center">
        <div class="w-full max-w-4xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-2xl">
            <div class="flex items-start justify-between px-8 py-6">
                <div>
                    <h3 class="text-4xl font-bold text-[#1f2b8b]">Edit Announcement</h3>
                    <p class="text-xl text-slate-500">Update the announcement</p>
                </div>
                <button type="button" data-close-modal class="text-4xl leading-none text-slate-500 hover:text-slate-700">&times;</button>
            </div>

            <div class="px-8 pb-8">
                <div class="space-y-4">
                    <div>
                        <label class="mb-1 block text-lg font-semibold text-[#1f2b8b]">Title *</label>
                        <input type="text" value="CHED Compliance Deadline Reminder" class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-lg focus:border-blue-400 focus:outline-none">
                    </div>

                    <div>
                        <label class="mb-1 block text-lg font-semibold text-[#1f2b8b]">Content *</label>
                        <textarea rows="4" class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-lg focus:border-blue-400 focus:outline-none">This is a reminder that all faculty members must submit their updated curriculum vitae and credentials for the upcoming CHED compliance review. Please ensure your resume is updated in the system by January 31, 2025.</textarea>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-lg font-semibold text-[#1f2b8b]">Type</label>
                            <select class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-lg focus:border-blue-400 focus:outline-none">
                                <option>Urgent</option>
                                <option>General</option>
                                <option>Reminder</option>
                                <option>Event</option>
                                <option>Policy Update</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-lg font-semibold text-[#1f2b8b]">Priority</label>
                            <select class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-lg focus:border-blue-400 focus:outline-none">
                                <option>High</option>
                                <option>Medium</option>
                                <option>Low</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-lg font-semibold text-[#1f2b8b]">Target Department</label>
                        <select class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-lg focus:border-blue-400 focus:outline-none">
                            <option>All</option>
                            <option>College of Engineering</option>
                            <option>College of Business</option>
                            <option>College of Education</option>
                            <option>College of Arts &amp; Sciences</option>
                            <option>College of Computing</option>
                            <option>College of Allied Health</option>
                            <option>Administration</option>
                            <option>Human Resources</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-lg font-semibold text-[#1f2b8b]">Publish Date</label>
                            <input type="date" value="2025-01-10" class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-lg focus:border-blue-400 focus:outline-none">
                        </div>
                        <div>
                            <label class="mb-1 block text-lg font-semibold text-[#1f2b8b]">Expiry Date</label>
                            <input type="date" value="2025-01-31" class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-lg focus:border-blue-400 focus:outline-none">
                        </div>
                    </div>

                    <div class="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-2">
                        <label for="publish-now-edit" class="text-xl font-semibold text-[#1f2b8b]">Publish immediately</label>
                        <input id="publish-now-edit" type="checkbox" checked class="h-5 w-10 cursor-pointer accent-[#1f2b8b]">
                    </div>
                </div>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-end">
                    <button type="button" data-close-modal class="rounded-md border border-slate-400 px-6 py-2 text-lg font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="button" data-close-modal class="rounded-md bg-[#00386f] px-6 py-2 text-lg font-semibold text-white hover:bg-[#002f5d]">Update</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const modalOpeners = document.querySelectorAll('[data-open-modal]');
        const modalClosers = document.querySelectorAll('[data-close-modal]');
        const modalElements = document.querySelectorAll('#new-announcement-modal, #edit-announcement-modal');

        const targetEmployeeTypeControl = document.querySelector('[name="target_employee_type"]');
        const targetPositionControl = document.querySelector('[name="target_office"]');
        const targetRankingControl = document.querySelector('[name="target_ranking"]');
        const targetDepartmentField = document.querySelector('[data-target-field="department"]');
        const targetFiltersField = document.querySelector('[data-target-field="filters"]');
        const targetRankingField = document.querySelector('[data-target-field="ranking"]');

        function normalizeTargetValue(value) {
            return String(value ?? '').trim().toLowerCase();
        }

        function rankingPrefixForPosition(position) {
            const normalized = normalizeTargetValue(position);

            if (normalized.includes('assistant professor')) return 'assistant professor';
            if (normalized.includes('associate professor')) return 'associate professor';
            if (normalized.includes('full professor')) return 'full professor';
            if (normalized.includes('instructor')) return 'instructor';

            return '';
        }

        function filterAnnouncementPositionOptions(employeeType) {
            if (!targetPositionControl) return;

            const normalizedType = normalizeTargetValue(employeeType);
            const options = Array.from(targetPositionControl.options);
            let selectedStillVisible = false;

            options.forEach((option) => {
                if (option.value === '') {
                    option.hidden = false;
                    option.disabled = false;
                    return;
                }

                const optionType = normalizeTargetValue(option.dataset.targetEmployeeType);
                const matches = !normalizedType || optionType === normalizedType;

                option.hidden = !matches;
                option.disabled = !matches;

                if (matches && option.value === targetPositionControl.value) {
                    selectedStillVisible = true;
                }
            });

            if (!selectedStillVisible && normalizedType) {
                targetPositionControl.value = '';
            }
        }

        function filterAnnouncementRankingOptions(position) {
            if (!targetRankingControl) return;

            const prefix = rankingPrefixForPosition(position);
            const options = Array.from(targetRankingControl.options);
            let selectedStillVisible = false;

            options.forEach((option) => {
                if (option.value === '') {
                    option.hidden = false;
                    option.disabled = false;
                    return;
                }

                const matches = !prefix || normalizeTargetValue(option.value).startsWith(prefix);

                option.hidden = !matches;
                option.disabled = !matches;

                if (matches && option.value === targetRankingControl.value) {
                    selectedStillVisible = true;
                }
            });

            if (!selectedStillVisible && prefix) {
                targetRankingControl.value = '';
            }
        }

        function updateAnnouncementTargetFields() {
            const employeeType = targetEmployeeTypeControl?.value ?? '';
            const isFaculty = employeeType === 'faculty';
            const isAdminSupport = employeeType === 'admin_support';

            if (targetFiltersField) {
                targetFiltersField.classList.toggle('hidden', !employeeType);
            }

            if (targetDepartmentField) {
                targetDepartmentField.classList.toggle('hidden', !isFaculty);
                if (!isFaculty) {
                    const departmentSelect = targetDepartmentField.querySelector('[name="target_department_id"]');
                    if (departmentSelect) departmentSelect.value = '';
                }
            }

            if (targetRankingField) {
                const shouldShowRanking = isFaculty && rankingPrefixForPosition(targetPositionControl?.value ?? '') !== '';
                targetRankingField.classList.toggle('hidden', !shouldShowRanking);
                if (!shouldShowRanking && targetRankingControl) {
                    targetRankingControl.value = '';
                }
            }

            filterAnnouncementPositionOptions(employeeType);
            filterAnnouncementRankingOptions(targetPositionControl?.value ?? '');

            if (isAdminSupport && targetRankingControl) {
                targetRankingControl.value = '';
            }
        }

        if (targetEmployeeTypeControl) {
            targetEmployeeTypeControl.addEventListener('change', updateAnnouncementTargetFields);
        }

        if (targetPositionControl) {
            targetPositionControl.addEventListener('change', updateAnnouncementTargetFields);
        }

        updateAnnouncementTargetFields();

        function showModal(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) return;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }

        function hideModal(modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');

            const hasOpenModal = Array.from(modalElements).some((item) => !item.classList.contains('hidden'));
            if (!hasOpenModal) {
                document.body.classList.remove('overflow-hidden');
            }
        }

        modalOpeners.forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                showModal(button.dataset.openModal);
            });
        });

        modalClosers.forEach((button) => {
            button.addEventListener('click', () => {
                const modal = button.closest('.fixed.inset-0');
                if (modal) hideModal(modal);
            });
        });

        modalElements.forEach((modal) => {
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    hideModal(modal);
                }
            });
        });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') return;

            modalElements.forEach((modal) => {
                if (!modal.classList.contains('hidden')) {
                    hideModal(modal);
                }
            });
        });
    </script>

    @auth
        @include('partials.logout-modal')
    @endauth
</body>
</html>
