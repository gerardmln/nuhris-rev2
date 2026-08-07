@extends('employee.layout')

@section('title', 'Employee Dashboard')
@section('page_title', 'Dashboard')

@section('content')
    <div>
        <h2 class="text-3xl font-bold text-[#1f2b5d]">Welcome back, {{ auth()->user()->name }}!</h2>
        <p class="text-sm text-slate-500">Here is an overview of your HR information.</p>
    </div>

    @if ($stats['expiring_soon'] > 0)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-amber-900 shadow-sm">
            <p class="text-sm font-semibold">You have {{ $stats['expiring_soon'] }} approved credential{{ $stats['expiring_soon'] > 1 ? 's' : '' }} expiring soon.</p>
            <p class="mt-1 text-xs text-amber-800">Please visit your credentials list to review and re-upload the affected document(s) if needed.</p>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-2xl border border-slate-300 bg-white p-5 shadow-sm">
            <p class="text-xs font-medium text-slate-500">Active Credentials</p>
            <p class="mt-1 text-4xl font-extrabold">{{ $stats['active_credentials'] }}</p>
            <p class="text-xs text-slate-500">{{ $stats['pending_credentials'] }} pending review</p>
        </article>
        <article class="rounded-2xl border border-slate-300 bg-white p-5 shadow-sm">
            <p class="text-xs font-medium text-slate-500">Compliance</p>
            <p class="mt-1 text-4xl font-extrabold">{{ $stats['compliance_passed'] }}/{{ $stats['compliance_total'] }}</p>
            <p class="text-xs text-slate-500">Up to date</p>
        </article>
        <article class="rounded-2xl border border-slate-300 bg-white p-5 shadow-sm">
            <p class="text-xs font-medium text-slate-500">Leave Balance</p>
            <p class="mt-1 text-4xl font-extrabold">{{ $stats['leave_balance'] }}</p>
            <p class="text-xs text-slate-500">Total days remaining</p>
        </article>
        <article class="rounded-2xl border border-slate-300 bg-white p-5 shadow-sm">
            <p class="text-xs font-medium text-slate-500">Notifications</p>
            <p class="mt-1 text-4xl font-extrabold">{{ $stats['notifications'] }}</p>
            <p class="text-xs text-slate-500">Recent alerts</p>
        </article>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <article class="rounded-2xl border border-slate-300 bg-white p-6 shadow-sm">
                <h3 class="text-2xl font-bold text-slate-800">Compliance Status</h3>
                <div class="mt-4 space-y-3">
                    <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-5 py-3">
                        <p class="text-sm font-semibold text-slate-700">Compliant</p>
                        <p class="text-3xl font-extrabold text-emerald-500">{{ $stats['compliant'] }}</p>
                    </div>
                    <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-5 py-3">
                        <p class="text-sm font-semibold text-slate-700">Expiring Soon</p>
                        <p class="text-3xl font-extrabold text-amber-500">{{ $stats['expiring_soon'] }}</p>
                    </div>
                    <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-5 py-3">
                        <p class="text-sm font-semibold text-slate-700">Non-Compliant</p>
                        <p class="text-3xl font-extrabold text-red-500">{{ $stats['non_compliant'] }}</p>
                    </div>
                </div>
            </article>

            <article class="rounded-2xl border border-slate-300 bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-2xl font-bold text-slate-800">Recent Alerts</h3>
                    <a href="{{ route('employee.notifications') }}" class="text-sm font-semibold text-blue-800 hover:underline">View All</a>
                </div>
                <div class="space-y-3">
                    @forelse ($recentAlerts as $alert)
                        @php
                            $announcement = $alert->announcement;
                            $priorityLabel = $announcement?->priority_label ?? 'Medium';
                            $priorityBadgeClass = $announcement?->priority_badge_class ?? 'bg-blue-100 text-blue-700';
                        @endphp
                        <a href="{{ route('employee.notifications.open', $alert) }}" class="block rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 transition hover:border-slate-300 hover:bg-slate-100">
                            <div class="mb-2 flex items-start justify-between gap-2">
                                <p class="text-sm font-semibold">{{ $alert->title_text }}</p>
                                <div class="flex items-center gap-2">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $priorityBadgeClass }}">{{ $priorityLabel }}</span>
                                    @if ($announcement?->is_expired)
                                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700">Expired</span>
                                    @endif
                                </div>
                            </div>
                            <p class="text-xs text-slate-500">{{ $alert->created_at->format('M d, h:i A') }}</p>
                        </a>
                    @empty
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4">
                            <p class="text-sm font-semibold">No recent alerts</p>
                            <p class="text-xs text-slate-500">You are all caught up.</p>
                        </div>
                    @endforelse
                </div>
            </article>
        </div>

        <article class="rounded-2xl border border-slate-300 bg-white p-6 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-2xl font-bold text-slate-800">System Calendar</h3>
                    <p class="mt-3 text-sm font-semibold text-slate-700">{{ $calendar['month_label'] }}</p>
                </div>
                <button id="open-academic-calendar" type="button" class="rounded-xl border border-sky-200 bg-sky-50 px-3 py-2 text-sm font-semibold text-sky-700 transition hover:bg-sky-100">
                    Open Calendar
                </button>
            </div>
            <div class="mt-4 grid grid-cols-7 gap-2 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">
                <span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
            </div>
            <div class="mt-2 grid grid-cols-7 gap-2 text-sm">
                @foreach ($calendar['cells'] as $cell)
                    @if (! $cell)
                        <div class="min-h-24 rounded-2xl border border-transparent bg-transparent"></div>
                    @else
                        <div class="min-h-24 rounded-2xl border border-slate-200 bg-slate-50 p-2 {{ $cell['is_today'] ? 'ring-2 ring-blue-500' : '' }}">
                            <div class="flex items-center justify-between">
                                @php $hasEntries = $cell['entries']->count() > 0; @endphp
                                <span class="text-xs font-semibold {{ $hasEntries ? 'inline-flex h-6 w-6 items-center justify-center rounded-full bg-sky-50 text-sky-700' : 'text-slate-500' }}">{{ $cell['day'] }}</span>
                                @if ($hasEntries)
                                    <span class="rounded-full bg-blue-600 px-2 py-0.5 text-[10px] font-bold text-white">{{ $cell['entries']->count() }}</span>
                                @endif
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
            <div class="mt-6 space-y-2 text-sm">
                <p class="font-semibold">UPCOMING EVENTS</p>
                @forelse ($calendar['events'] as $event)
                    <p class="text-slate-600">{{ $event }}</p>
                @empty
                    <p class="text-slate-600">No upcoming events.</p>
                @endforelse
            </div>
        </article>
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
@endsection

@push('scripts')
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

                        <div class="mt-2 grid grid-cols-7 gap-2 text-left text-sm">${days.join('')}</div>

                        <div class="mt-5 space-y-2">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Events in ${formatMonthLabel(monthDate)}</p>
                            <div class="grid grid-cols-1 gap-2 md:grid-cols-2 xl:grid-cols-3">${eventCards}</div>
                        </div>
                    </section>
                `;
            };

            const renderNavButtons = (targetContainer) => {
                if (!targetContainer) {
                    return;
                }

                targetContainer.innerHTML = months.map((monthDate, index) => `
                    <button type="button" data-month-target="academic-month-${index}" class="academic-month-nav rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:border-sky-300 hover:bg-sky-50">
                        ${formatMonthLabel(monthDate)}
                    </button>
                `).join('');
            };

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

            monthsContainer.innerHTML = months.map((monthDate, index) => renderMonth(monthDate, index)).join('');
            renderNavButtons(navContainer);
            renderNavButtons(navMobileContainer);

            openButton.addEventListener('click', openModal);
            closeButton.addEventListener('click', closeModal);
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeModal();
                }
            });

            document.querySelectorAll('.academic-month-nav').forEach((button) => {
                button.addEventListener('click', () => {
                    const targetId = button.getAttribute('data-month-target');
                    const target = document.getElementById(targetId);

                    if (target) {
                        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                });
            });
        })();
    </script>
@endpush