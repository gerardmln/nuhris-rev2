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

                            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                @foreach ($actionRequiredCards as $card)
                                    @php
                                        $toneStyles = match ($card['tone']) {
                                            'amber' => 'border border-amber-200 bg-amber-50 hover:bg-amber-100 text-amber-900',
                                            'blue' => 'border border-blue-200 bg-blue-50 hover:bg-blue-100 text-blue-900',
                                            'emerald' => 'border border-emerald-200 bg-emerald-50 hover:bg-emerald-100 text-emerald-900',
                                            default => 'border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-900',
                                        };

                                        $countToneStyles = match ($card['tone']) {
                                            'amber' => 'text-amber-900',
                                            'blue' => 'text-blue-900',
                                            'emerald' => 'text-emerald-900',
                                            default => 'text-slate-900',
                                        };

                                        $descriptionToneStyles = match ($card['tone']) {
                                            'amber' => 'text-amber-700',
                                            'blue' => 'text-blue-700',
                                            'emerald' => 'text-emerald-700',
                                            default => 'text-slate-600',
                                        };
                                    @endphp

                                    <a href="{{ $card['href'] }}" class="flex items-center justify-between rounded-xl px-4 py-3 shadow-sm transition {{ $toneStyles }}">
                                        <div>
                                            <p class="font-semibold {{ $countToneStyles }}">{{ $card['count'] }} {{ $card['title'] }}</p>
                                            <p class="text-xs {{ $descriptionToneStyles }}">{{ $card['count'] > 0 ? $card['description'] : $card['empty_label'] }}</p>
                                        </div>
                                        <span class="text-xl font-light text-slate-400">&gt;</span>
                                    </a>
                                @endforeach
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
                        @php
                            $upcomingAcademicCalendarEntries = collect($academicCalendarEntries)
                                ->where('event_date', '>=', today()->toDateString())
                                ->sortBy('event_date')
                                ->take(3);
                        @endphp

                        <article class="rounded-2xl border border-slate-300 bg-white p-4 shadow-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-2xl font-bold text-slate-800">Calendar</h3>
                                    <p class="text-sm text-slate-500">Browse the academic calendar by month.</p>
                                </div>
                                <button id="open-academic-calendar" type="button" class="rounded-xl border border-sky-200 bg-sky-50 px-3 py-2 text-sm font-semibold text-sky-700 hover:bg-sky-100">
                                    Open Calendar
                                </button>
                            </div>

                            <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Upcoming academic dates</p>
                                <div class="mt-3 space-y-2">
                                    @forelse($upcomingAcademicCalendarEntries as $entry)
                                        <div class="rounded-xl border border-blue-200 bg-blue-50 px-3 py-2">
                                            <p class="text-sm font-semibold text-slate-800">{{ $entry['title'] }}</p>
                                            <p class="text-xs text-slate-500">{{ \Illuminate\Support\Carbon::parse($entry['event_date'])->format('M d, Y') }} · {{ $entry['type_label'] }}</p>
                                        </div>
                                    @empty
                                        <p class="text-sm text-slate-500">No upcoming academic calendar entries yet.</p>
                                    @endforelse
                                </div>
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
                <div class="h-8"></div>
            </section>
        </main>
    </div>

    <div id="academic-calendar-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/60 p-4">
        <div class="flex max-h-[90vh] w-full max-w-6xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl">
            <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-5">
                <div>
                    <h3 class="text-3xl font-bold text-slate-900">Academic Calendar</h3>
                    <p class="mt-1 text-sm text-slate-500">Scroll through the months to review holidays and events.</p>
                </div>
                <button id="close-academic-calendar" type="button" class="rounded-full border border-slate-200 px-3 py-1 text-2xl leading-none text-slate-500 hover:bg-slate-50 hover:text-slate-800">&times;</button>
            </div>

            <div class="flex flex-1 flex-col gap-4 overflow-hidden lg:flex-row">
                <div class="border-b border-slate-200 px-6 py-4 lg:hidden">
                    <div id="academic-calendar-nav-mobile" class="flex gap-2 overflow-x-auto pb-2"></div>
                </div>

                <aside class="hidden w-64 shrink-0 border-r border-slate-200 px-4 py-5 lg:block">
                    <p class="px-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Months</p>
                    <div id="academic-calendar-nav" class="mt-3 flex max-h-[calc(90vh-8rem)] flex-col gap-2 overflow-y-auto pr-2"></div>
                </aside>

                <div class="flex min-h-0 flex-1 flex-col overflow-hidden px-6 py-5">
                    <div id="academic-calendar-months" class="min-h-0 flex-1 space-y-5 overflow-y-auto pr-1"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const entries = @json($academicCalendarEntries);
            const modal = document.getElementById('academic-calendar-modal');
            const openButton = document.getElementById('open-academic-calendar');
            const closeButton = document.getElementById('close-academic-calendar');
            const monthsContainer = document.getElementById('academic-calendar-months');
            const navContainer = document.getElementById('academic-calendar-nav');
            const navMobileContainer = document.getElementById('academic-calendar-nav-mobile');

            if (!modal || !openButton || !closeButton || !monthsContainer) {
                return;
            }

            const parseDate = (value) => new Date(`${value}T00:00:00`);
            const formatMonthLabel = (date) => date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
            const formatDayLabel = (date) => date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            const monthKey = (date) => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;

            const calendarEntries = entries.map((entry) => ({
                ...entry,
                date: parseDate(entry.event_date),
                monthKey: entry.event_date.slice(0, 7),
            }));

            const currentMonth = new Date(new Date().getFullYear(), new Date().getMonth(), 1);
            const firstMonth = calendarEntries.length > 0
                ? new Date(calendarEntries[0].date.getFullYear(), calendarEntries[0].date.getMonth(), 1)
                : currentMonth;
            const startMonth = firstMonth < currentMonth ? firstMonth : currentMonth;

            const lastEntryMonth = calendarEntries.length > 0
                ? new Date(calendarEntries[calendarEntries.length - 1].date.getFullYear(), calendarEntries[calendarEntries.length - 1].date.getMonth(), 1)
                : currentMonth;
            const defaultEnd = new Date(startMonth.getFullYear(), startMonth.getMonth() + 11, 1);
            const endMonth = lastEntryMonth > defaultEnd ? lastEntryMonth : defaultEnd;

            const buildMonths = (start, end) => {
                const months = [];
                const cursor = new Date(start.getFullYear(), start.getMonth(), 1);

                while (cursor <= end) {
                    months.push(new Date(cursor));
                    cursor.setMonth(cursor.getMonth() + 1);
                }

                return months;
            };

            const months = buildMonths(startMonth, endMonth);
            const entriesByMonth = new Map();

            calendarEntries.forEach((entry) => {
                if (!entriesByMonth.has(entry.monthKey)) {
                    entriesByMonth.set(entry.monthKey, []);
                }

                entriesByMonth.get(entry.monthKey).push(entry);
            });

            const renderMonth = (monthDate, index) => {
                const key = monthKey(monthDate);
                const monthEntries = (entriesByMonth.get(key) || []).sort((left, right) => left.date - right.date);
                const year = monthDate.getFullYear();
                const monthIndex = monthDate.getMonth();
                const firstDayIndex = new Date(year, monthIndex, 1).getDay();
                const daysInMonth = new Date(year, monthIndex + 1, 0).getDate();
                const days = [];

                for (let empty = 0; empty < firstDayIndex; empty += 1) {
                    days.push('<div class="min-h-[4.25rem] rounded-xl border border-transparent"></div>');
                }

                for (let day = 1; day <= daysInMonth; day += 1) {
                    const dateKey = `${year}-${String(monthIndex + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                    const dayEntries = calendarEntries.filter((entry) => entry.event_date === dateKey);
                    const hasEntries = dayEntries.length > 0;

                    days.push(`
                        <div class="min-h-[4.25rem] rounded-xl border ${hasEntries ? 'border-sky-200 bg-sky-50' : 'border-slate-200 bg-white'} px-2 py-2">
                            <div class="flex items-center justify-between text-sm font-semibold ${hasEntries ? 'text-slate-900' : 'text-slate-700'}">
                                <span>${day}</span>
                                ${hasEntries ? `<span class="rounded-full bg-sky-600 px-2 py-0.5 text-[10px] font-bold text-white">${dayEntries.length}</span>` : ''}
                            </div>
                            <div class="mt-1 space-y-1">
                                ${dayEntries.slice(0, 2).map((entry) => `
                                    <div class="truncate rounded-md bg-white/80 px-1.5 py-0.5 text-[11px] font-medium text-slate-700 shadow-sm ring-1 ring-slate-200">
                                        ${entry.title}
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    `);
                }

                const eventCards = monthEntries.length > 0
                    ? monthEntries.map((entry) => `
                        <div class="rounded-xl border border-slate-200 bg-white px-3 py-3 shadow-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-slate-900">${entry.title}</p>
                                    <p class="text-xs text-slate-500">${formatDayLabel(entry.date)} · ${entry.type_label}</p>
                                    ${entry.description ? `<p class="mt-1 text-sm text-slate-600">${entry.description}</p>` : ''}
                                </div>
                                <span class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide ${entry.entry_type === 'holiday' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800'}">${entry.day_type === 'non_working' ? 'Non-Working' : 'Working'}</span>
                            </div>
                        </div>
                    `).join('')
                    : '<div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-5 text-sm text-slate-500">No academic calendar entries for this month.</div>';

                return `
                    <section id="academic-month-${index}" class="scroll-mt-6 rounded-3xl border border-slate-200 bg-slate-50 p-4 shadow-sm">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Month</p>
                                <h4 class="text-2xl font-bold text-slate-900">${formatMonthLabel(monthDate)}</h4>
                            </div>
                            <p class="text-sm text-slate-500">${monthEntries.length} event${monthEntries.length === 1 ? '' : 's'}</p>
                        </div>

                        <div class="mt-4 grid grid-cols-7 gap-2 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
                        </div>

                        <div class="mt-2 grid grid-cols-7 gap-2 text-left text-sm">
                            ${days.join('')}
                        </div>

                        <div class="mt-5 space-y-2">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Events in ${formatMonthLabel(monthDate)}</p>
                            <div class="grid grid-cols-1 gap-2 md:grid-cols-2 xl:grid-cols-3">
                                ${eventCards}
                            </div>
                        </div>
                    </section>
                `;
            };

            const renderNavButtons = (targetContainer) => {
                if (!targetContainer) {
                    return;
                }

                targetContainer.innerHTML = months.map((monthDate, index) => `
                    <button type="button" data-month-target="academic-month-${index}" class="month-nav-button rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:border-sky-300 hover:bg-sky-50">
                        ${formatMonthLabel(monthDate)}
                    </button>
                `).join('');
            };

            monthsContainer.innerHTML = months.map((monthDate, index) => renderMonth(monthDate, index)).join('');
            renderNavButtons(navContainer);
            renderNavButtons(navMobileContainer);

            document.querySelectorAll('.month-nav-button').forEach((button) => {
                button.addEventListener('click', () => {
                    const target = document.getElementById(button.dataset.monthTarget);
                    if (target) {
                        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                });
            });

            const openModal = () => {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.classList.add('overflow-hidden');
            };

            const closeModal = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
            };

            openButton.addEventListener('click', openModal);
            closeButton.addEventListener('click', closeModal);

            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeModal();
                }
            });
        })();
    </script>

    @auth
        @include('partials.logout-modal')
    @endauth
</body>
</html>
