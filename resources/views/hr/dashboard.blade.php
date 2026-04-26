<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>HR Dashboard | {{ config('app.name', 'NU HRIS') }}</title>
    @include('partials.favicon')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#eceef1] text-slate-900 antialiased overflow-x-hidden overflow-y-auto">
    <div class="flex min-h-screen flex-col lg:flex-row">
        @include('partials.hr-sidebar', ['activeNav' => 'dashboard'])

        <main class="min-h-screen flex-1">
            <header class="border-b border-slate-300 bg-white px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-[30px] font-bold leading-none text-[#1f2b5d]">National University HRIS</h1>
                    </div>

                    @include('partials.header-actions')
                </div>
            </header>

            <section class="space-y-5 px-5 py-5 sm:px-6 sm:py-6">
                <div>
                    <h2 class="text-3xl font-bold text-[#1f2b5d]">HR Dashboard</h2>
                    <p class="text-sm text-slate-500">Welcome back! Here is your HR overview.</p>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <article class="rounded-2xl border border-slate-300 bg-white p-4 shadow-sm">
                        <p class="text-xs font-medium text-slate-500">Total Employees</p>
                        <p class="mt-1 text-4xl font-extrabold">{{ $stats['total_employees'] }}</p>
                    </article>
                    <article class="rounded-2xl border border-slate-300 bg-white p-4 shadow-sm">
                        <p class="text-xs font-medium text-slate-500">Pending Credentials</p>
                        <p class="mt-1 text-4xl font-extrabold">{{ $stats['pending_credentials'] }}</p>
                    </article>
                    <article class="rounded-2xl border border-slate-300 bg-white p-4 shadow-sm">
                        <p class="text-xs font-medium text-slate-500">Present Today</p>
                        <p class="mt-1 text-4xl font-extrabold">{{ $stats['present_today'] }}</p>
                    </article>
                    <article class="rounded-2xl border border-slate-300 bg-white p-4 shadow-sm">
                        <p class="text-xs font-medium text-slate-500">Expiring Licenses</p>
                        <p class="mt-1 text-4xl font-extrabold">{{ $stats['expiring_licenses'] }}</p>
                    </article>
                </div>

                <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
                    <div class="space-y-4 xl:col-span-2">
                        <article class="rounded-2xl border border-slate-300 bg-white p-4 shadow-sm">
                            <h3 class="mb-3 text-2xl font-bold text-slate-800">Action Required</h3>

                            <div class="space-y-2">
                                <div class="flex items-center justify-between rounded-xl border border-yellow-200 bg-yellow-50 px-4 py-3">
                                    <div>
                                        <p class="font-semibold text-slate-800">4 Resume(s) Need Update</p>
                                        <p class="text-xs text-slate-500">Outdated records</p>
                                    </div>
                                    <span class="text-slate-400">></span>
                                </div>

                                <div class="flex items-center justify-between rounded-xl border border-blue-200 bg-blue-50 px-4 py-3">
                                    <div>
                                        <p class="font-semibold text-slate-800">1 Credential pending review</p>
                                        <p class="text-xs text-slate-500">Requires verification</p>
                                    </div>
                                    <span class="text-slate-400">></span>
                                </div>
                            </div>
                        </article>

                        <article class="rounded-2xl border border-slate-300 bg-white p-4 shadow-sm">
                            <h3 class="mb-2 text-2xl font-bold text-slate-800">Records Overview</h3>
                            <p class="mb-3 text-sm text-slate-500">Latest HR updates and metrics.</p>

                            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                    <p class="text-sm font-semibold text-slate-700">Onboarding Queue</p>
                                    <p class="mt-2 text-3xl font-extrabold">3</p>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                    <p class="text-sm font-semibold text-slate-700">Payroll Pending</p>
                                    <p class="mt-2 text-3xl font-extrabold">2</p>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                    <p class="text-sm font-semibold text-slate-700">Leaves for Approval</p>
                                    <p class="mt-2 text-3xl font-extrabold">6</p>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                    <p class="text-sm font-semibold text-slate-700">Policy Drafts</p>
                                    <p class="mt-2 text-3xl font-extrabold">1</p>
                                </div>
                            </div>
                        </article>
                    </div>

                    <div class="space-y-4">
                        <article class="rounded-2xl border border-slate-300 bg-white p-4 shadow-sm">
                            <h3 class="text-2xl font-bold text-slate-800">Calendar</h3>
                            <p class="mb-3 text-sm text-slate-500">February 2026</p>

                            <div class="grid grid-cols-7 gap-1 text-center text-xs text-slate-500">
                                <span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
                            </div>

                            <div class="mt-2 grid grid-cols-7 gap-1 text-center text-sm">
                                <span class="rounded py-1">1</span><span class="rounded py-1">2</span><span class="rounded py-1">3</span><span class="rounded py-1">4</span><span class="rounded py-1">5</span><span class="rounded py-1">6</span><span class="rounded py-1">7</span>
                                <span class="rounded py-1">8</span><span class="rounded py-1">9</span><span class="rounded py-1">10</span><span class="rounded py-1">11</span><span class="rounded py-1">12</span><span class="rounded bg-sky-600 py-1 font-semibold text-white">13</span><span class="rounded py-1">14</span>
                                <span class="rounded py-1">15</span><span class="rounded py-1">16</span><span class="rounded py-1">17</span><span class="rounded py-1">18</span><span class="rounded py-1">19</span><span class="rounded py-1">20</span><span class="rounded py-1">21</span>
                                <span class="rounded py-1">22</span><span class="rounded py-1">23</span><span class="rounded py-1">24</span><span class="rounded py-1">25</span><span class="rounded py-1">26</span><span class="rounded py-1">27</span><span class="rounded py-1">28</span>
                            </div>

                            <div class="mt-4 space-y-2">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Upcoming Events</p>
                                <div class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-sm">National Heroes Day</div>
                                <div class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-sm">PRC License Renewal Period</div>
                            </div>
                        </article>

                        <article class="rounded-2xl border border-slate-300 bg-white p-4 shadow-sm">
                            <div class="mb-2 flex items-center justify-between">
                                <h3 class="text-2xl font-bold text-slate-800">Announcements</h3>
                                <a href="{{ route('announcements.index') }}" class="text-sm font-semibold text-blue-700 hover:underline">View all</a>
                            </div>

                            <div class="space-y-3">
                                @forelse($announcements as $announcement)
                                    <div class="rounded-lg border border-slate-200 px-3 py-2">
                                        <p class="text-sm font-semibold">{{ $announcement->title }}</p>
                                        <p class="text-xs text-slate-500">{{ $announcement->published_at->format('M d, Y') }}</p>
                                    </div>
                                @empty
                                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-4 text-center">
                                        <p class="text-sm text-slate-500">No announcements yet</p>
                                    </div>
                                @endforelse
                            </div>
                        </article>
                    </div>
                </div>

                <article class="rounded-2xl bg-[#00386f] px-5 py-4 text-white shadow-md">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h3 class="text-2xl font-bold">Quick Actions</h3>
                            <p class="text-sm text-blue-100">Commonly used HR functions</p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button data-open-modal="quick-add-employee-modal" class="rounded-lg bg-[#ffdc00] px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-yellow-300">Add Employee</button>
                            <button data-open-modal="quick-upload-credentials-modal" class="rounded-lg bg-[#ffdc00] px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-yellow-300">Upload Credentials</button>
                            <button data-open-modal="quick-post-announcement-modal" class="rounded-lg bg-[#ffdc00] px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-yellow-300">Post Announcement</button>
                        </div>
                    </div>
                </article>

                <div class="h-8"></div>
            </section>
        </main>
    </div>

    <div id="quick-add-employee-modal" class="fixed inset-0 z-50 hidden items-start justify-center overflow-y-auto bg-black/45 p-4 py-6 sm:items-center">
        <div class="w-full max-w-4xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-2xl">
            <div class="flex items-start justify-between px-8 py-6">
                <h3 class="text-4xl font-bold text-[#1f2b8b]">Add Employee</h3>
                <button type="button" data-close-modal class="text-4xl leading-none text-slate-500 hover:text-slate-700">&times;</button>
            </div>

            <form method="POST" action="{{ route('employees.store') }}" data-employee-form class="px-8 pb-8">
                @csrf
                <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3">
                    <p class="text-xs text-blue-800">Fields marked with * are required.</p>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="rounded-md border border-dashed border-slate-300 bg-slate-50 px-3 py-3 sm:col-span-2">
                        <p class="text-sm font-semibold text-slate-700">Employee ID</p>
                        <p class="text-sm text-slate-500">Automatically generated on save using the format <span class="font-medium text-slate-700">YYYY-001</span>.</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">First Name *</label>
                        <input name="first_name" type="text" placeholder="Juan" class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-400 focus:outline-none" required>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Last Name *</label>
                        <input name="last_name" type="text" placeholder="Dela Cruz" class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-400 focus:outline-none" required>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Email *</label>
                        <input name="email" type="email" placeholder="name@nu.edu.ph" class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-400 focus:outline-none" required>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Phone Number</label>
                        <input name="phone" type="text" placeholder="09xxxxxxxxx" class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-400 focus:outline-none">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Employee Type *</label>
                        <select name="employment_type" data-employee-control="employment_type" class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-400 focus:outline-none" required>
                            <option value="">Select Type</option>
                            @foreach ($employmentTypes as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Position *</label>
                        <select name="position" data-employee-control="position" class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-400 focus:outline-none" required>
                            <option value="">Select Position / Office</option>
                            @foreach ($employeePositions as $position)
                                <option value="{{ $position }}">{{ $position }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div data-employee-field="department">
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Department *</label>
                        <select name="department_id" data-employee-control="department" class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-400 focus:outline-none">
                            <option value="">Select Department</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div data-employee-field="ranking">
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Faculty Ranking</label>
                        <select name="ranking" data-employee-control="ranking" class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-400 focus:outline-none">
                            <option value="">N/A</option>
                            @foreach ($facultyRankings as $ranking)
                                <option value="{{ $ranking }}">{{ $ranking }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Date Hired</label>
                        <input name="hire_date" type="date" class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-400 focus:outline-none">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Status *</label>
                        <select name="status" class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-400 focus:outline-none" required>
                            <option value="active">Active</option>
                            <option value="on_leave">On Leave</option>
                            <option value="resigned">Resigned</option>
                            <option value="terminated">Terminated</option>
                        </select>
                    </div>
                </div>

                <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
                    <button type="button" data-close-modal class="rounded-md border border-slate-400 px-6 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="rounded-md bg-[#00386f] px-6 py-2 text-sm font-semibold text-white hover:bg-[#002f5d]">Save Employee</button>
                </div>
            </form>
        </div>
    </div>

    <div id="quick-upload-credentials-modal" class="fixed inset-0 z-50 hidden items-start justify-center overflow-y-auto bg-black/45 p-4 py-6 sm:items-center">
        <div class="w-full max-w-3xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-2xl">
            <div class="flex items-start justify-between px-8 py-6">
                <h3 class="text-4xl font-bold text-[#1f2b8b]">Upload Credentials PDF</h3>
                <button type="button" data-close-modal class="text-4xl leading-none text-slate-500 hover:text-slate-700">&times;</button>
            </div>

            <form method="POST" action="{{ route('biometrics.upload') }}" enctype="multipart/form-data" class="px-8 pb-8">
                @csrf
                <label class="mb-2 block text-sm font-semibold text-slate-700">Credential File (PDF)</label>

                <div class="rounded-lg border border-dashed border-slate-400 p-8 text-center">
                    <div class="mx-auto inline-flex h-14 w-14 items-center justify-center text-slate-400">
                        <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 16V4" />
                            <path d="m7 9 5-5 5 5" />
                            <path d="M4 16v3a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-3" />
                        </svg>
                    </div>
                    <p class="mt-3 text-sm text-slate-500">Select a PDF file to upload</p>
                    <p class="text-xs text-slate-400">Accepted format: PDF</p>
                    <input name="biometrics_file" type="file" accept="application/pdf,.pdf" class="mt-4 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-700" required>
                </div>

                <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
                    <button type="button" data-close-modal class="rounded-md border border-slate-400 px-6 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-md bg-[#00386f] px-6 py-2 text-sm font-semibold text-white hover:bg-[#002f5d]">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="9"></circle>
                            <path d="m9 12 2 2 4-4"></path>
                        </svg>
                        Process File
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="quick-post-announcement-modal" class="fixed inset-0 z-50 hidden items-start justify-center overflow-y-auto bg-black/45 p-4 py-6 sm:items-center">
        <div class="w-full max-w-4xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-2xl">
            <div class="flex items-start justify-between px-8 py-6">
                <div>
                    <h3 class="text-4xl font-bold text-[#1f2b8b]">New Announcement</h3>
                    <p class="text-sm text-slate-500">Create a new announcement for employees</p>
                </div>
                <button type="button" data-close-modal class="text-4xl leading-none text-slate-500 hover:text-slate-700">&times;</button>
            </div>

            <form method="POST" action="{{ route('announcements.store') }}" class="px-8 pb-8">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-[#1f2b8b]">Title *</label>
                        <input name="title" type="text" placeholder="Announcement title" class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-400 focus:outline-none" required>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-semibold text-[#1f2b8b]">Content *</label>
                        <textarea name="content" rows="4" placeholder="Write your announcement here..." class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-400 focus:outline-none" required></textarea>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-[#1f2b8b]">Priority</label>
                            <select name="priority" class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-400 focus:outline-none">
                                <option value="medium">Medium</option>
                                <option value="low">Low</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-[#1f2b8b]">Target Role</label>
                            <select name="target_user_type" class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-400 focus:outline-none">
                                <option value="">Everyone</option>
                                <option value="1">Admin</option>
                                <option value="2">HR</option>
                                <option value="3">Employee</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-semibold text-[#1f2b8b]">Target Office (Admin Support Personnel)</label>
                        <select name="target_office" class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-400 focus:outline-none">
                            <option value="">All Offices</option>
                            @foreach ($officeAudiences as $office)
                                <option value="{{ $office }}">{{ $office }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-semibold text-[#1f2b8b]">Publish Date</label>
                        <input name="published_at" type="date" class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-400 focus:outline-none">
                    </div>

                    <div class="rounded-lg bg-slate-50 px-4 py-2">
                        <label class="flex items-center justify-between text-sm font-semibold text-[#1f2b8b]">
                            Publish immediately
                            <input name="is_published" type="checkbox" checked value="1" class="h-5 w-10 cursor-pointer accent-[#1f2b8b]">
                        </label>
                    </div>
                </div>

                <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
                    <button type="button" data-close-modal class="rounded-md border border-slate-400 px-6 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="rounded-md bg-[#00386f] px-6 py-2 text-sm font-semibold text-white hover:bg-[#002f5d]">Post announcement</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modalOpenButtons = document.querySelectorAll('[data-open-modal]');
        const modalCloseButtons = document.querySelectorAll('[data-close-modal]');
        const allQuickModals = document.querySelectorAll('#quick-add-employee-modal, #quick-upload-credentials-modal, #quick-post-announcement-modal');

        function openQuickModal(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) return;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }

        function closeQuickModal(modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');

            const hasOpen = Array.from(allQuickModals).some((item) => !item.classList.contains('hidden'));
            if (!hasOpen) {
                document.body.classList.remove('overflow-hidden');
            }
        }

        modalOpenButtons.forEach((button) => {
            button.addEventListener('click', () => {
                openQuickModal(button.dataset.openModal);
            });
        });

        modalCloseButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const modal = button.closest('.fixed.inset-0');
                if (modal) closeQuickModal(modal);
            });
        });

        allQuickModals.forEach((modal) => {
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeQuickModal(modal);
                }
            });
        });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') return;
            allQuickModals.forEach((modal) => {
                if (!modal.classList.contains('hidden')) {
                    closeQuickModal(modal);
                }
            });
        });
    </script>

    @auth
        @include('partials.logout-modal')
    @endauth
</body>
</html>
