@extends('admin.layout')

@section('title', 'Academic Calendar')
@section('page_title', 'Academic Calendar')
@section('page_subtitle', 'Manage holidays and system events used by the calendar views.')

@section('content')
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
            <p class="text-xs text-slate-500">Total Dates</p>
            <p class="text-4xl font-extrabold">{{ $stats['total'] }}</p>
        </article>
        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
            <p class="text-xs text-slate-500">Holidays</p>
            <p class="text-4xl font-extrabold">{{ $stats['holidays'] }}</p>
        </article>
        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
            <p class="text-xs text-slate-500">Events</p>
            <p class="text-4xl font-extrabold">{{ $stats['events'] }}</p>
        </article>
        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
            <p class="text-xs text-slate-500">Upcoming</p>
            <p class="text-4xl font-extrabold">{{ $stats['upcoming'] }}</p>
        </article>
    </div>

    @php
        $upcomingAcademicCalendarEntries = collect($academicCalendarEntries)
            ->where('event_date', '>=', today()->toDateString())
            ->sortBy('event_date')
            ->take(3);
    @endphp

    <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h3 class="text-2xl font-bold text-[#24358a]">Calendar View</h3>
                <p class="text-sm text-slate-500">Open a scrollable month view of academic dates and upcoming events.</p>
            </div>
            <button id="open-calendar-view" type="button" class="rounded-lg border border-sky-200 bg-sky-50 px-4 py-2 text-sm font-semibold text-sky-700 hover:bg-sky-100">
                View Calendar
            </button>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-3 lg:grid-cols-[1.3fr_0.7fr]">
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Upcoming academic dates</p>
                <div class="mt-3 space-y-2">
                    @forelse($upcomingAcademicCalendarEntries as $entry)
                        <div class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2">
                            <p class="text-sm font-semibold text-slate-800">{{ $entry['title'] }}</p>
                            <p class="text-xs text-slate-500">{{ \Illuminate\Support\Carbon::parse($entry['event_date'])->format('M d, Y') }} · {{ $entry['type_label'] }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No upcoming academic calendar entries yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">What the calendar shows</p>
                <ul class="mt-3 space-y-2 text-sm text-slate-600">
                    <li>• Scroll month by month through the academic year.</li>
                    <li>• See holidays, events, and working-day status.</li>
                    <li>• Jump directly to a specific month from the side list.</li>
                </ul>
            </div>
        </div>
    </article>

    <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
        <div class="mb-4 flex items-center justify-between gap-3">
            <div>
                <h3 class="text-2xl font-bold text-[#24358a]">Academic Dates</h3>
                <p class="text-sm text-slate-500">Create, update, or remove holidays, events, and their working status.</p>
            </div>
            <button id="open-entry-modal" class="rounded-lg bg-[#242b34] px-4 py-2 text-sm font-semibold text-white">+ Add Date</button>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-100 text-slate-700">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Date</th>
                        <th class="px-4 py-3 font-semibold">Title</th>
                        <th class="px-4 py-3 font-semibold">Type</th>
                        <th class="px-4 py-3 font-semibold">Day Status</th>
                        <th class="px-4 py-3 font-semibold">Description</th>
                        <th class="px-4 py-3 font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($entries as $entry)
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $entry->event_date->format('M d, Y') }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $entry->title }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $entry->badge_class }}">{{ $entry->type_label }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $entry->day_type === 'non_working' ? 'bg-slate-100 text-slate-800' : 'bg-emerald-100 text-emerald-800' }}">{{ $entry->day_type === 'non_working' ? 'Non-Working Day' : 'Working Day' }}</span>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $entry->description ?: '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        class="edit-entry-btn rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700"
                                        data-entry-id="{{ $entry->id }}"
                                        data-entry-title="{{ e($entry->title) }}"
                                        data-entry-type="{{ $entry->entry_type }}"
                                        data-entry-day-type="{{ $entry->day_type }}"
                                        data-entry-date="{{ $entry->event_date->format('Y-m-d') }}"
                                        data-entry-description="{{ e($entry->description ?? '') }}"
                                    >
                                        Edit
                                    </button>
                                    <form method="POST" action="{{ route('admin.academic-calendar.destroy', $entry) }}" onsubmit="return confirm('Delete this calendar date?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg border border-red-300 px-3 py-1.5 text-xs font-semibold text-red-700">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">No academic calendar dates added yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $entries->links() }}
        </div>
    </article>

    <div id="calendar-view-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/60 p-4">
        <div class="flex max-h-[90vh] w-full max-w-6xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl">
            <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-5">
                <div>
                    <h4 class="text-3xl font-bold text-[#24358a]">Academic Calendar</h4>
                    <p class="mt-1 text-sm text-slate-500">Scroll through each month to review upcoming academic events.</p>
                </div>
                <button id="close-calendar-view" type="button" class="rounded-full border border-slate-200 px-3 py-1 text-2xl leading-none text-slate-500 hover:bg-slate-50 hover:text-slate-800">&times;</button>
            </div>

            <div class="flex flex-1 flex-col gap-4 overflow-hidden lg:flex-row">
                <div class="border-b border-slate-200 px-6 py-4 lg:hidden">
                    <div id="calendar-view-nav-mobile" class="flex gap-2 overflow-x-auto pb-2"></div>

                </div>

                <aside class="hidden w-64 shrink-0 border-r border-slate-200 px-4 py-5 lg:block">
                    <p class="px-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Months</p>
                    <div id="calendar-view-nav" class="mt-3 flex max-h-[calc(90vh-8rem)] flex-col gap-2 overflow-y-auto pr-2"></div>
                </aside>

                <div class="flex min-h-0 flex-1 flex-col overflow-hidden px-6 py-5">
                    <div id="calendar-view-months" class="min-h-0 flex-1 space-y-5 overflow-y-auto pr-1"></div>
                </div>
            </div>
        </div>
    </div>

    <div id="entry-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">
        <div class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-2xl">
            <div class="mb-4 flex items-start justify-between gap-4">
                <div>
                    <h4 id="entry-modal-title" class="text-3xl font-bold text-[#24358a]">Add Academic Date</h4>
                    <p id="entry-modal-subtitle" class="text-sm text-slate-500">Record a holiday or event, then mark whether the date is working or non-working.</p>
                </div>
                <button id="close-entry-modal" type="button" class="text-2xl leading-none text-slate-500 hover:text-slate-800">&times;</button>
            </div>

            <form id="entry-form" method="POST" action="{{ route('admin.academic-calendar.store') }}" class="space-y-4">
                @csrf
                <input id="entry-form-method" type="hidden" name="_method" value="POST">
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Title</label>
                    <input id="entry-title" name="title" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2" placeholder="e.g. Foundation Day" required>
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Type</label>
                            <select id="entry-type" name="entry_type" class="w-full rounded-xl border border-slate-300 px-3 py-2" required>
                                <option value="holiday">Holiday</option>
                                <option value="event">Event</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Working Status</label>
                        <select id="entry-day-type" name="day_type" class="w-full rounded-xl border border-slate-300 px-3 py-2" required>
                            <option value="non_working">Non-Working Day</option>
                            <option value="working">Working Day</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Date</label>
                        <input id="entry-date" name="event_date" type="date" class="w-full rounded-xl border border-slate-300 px-3 py-2" required>
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Description</label>
                    <textarea id="entry-description" name="description" rows="4" class="w-full rounded-xl border border-slate-300 px-3 py-2" placeholder="Optional notes for this date"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button id="cancel-entry-modal" type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Cancel</button>
                    <button id="entry-submit" type="submit" class="rounded-lg bg-[#242b34] px-4 py-2 text-sm font-semibold text-white">+ Add Date</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const modal = document.getElementById('entry-modal');
            const form = document.getElementById('entry-form');
            const title = document.getElementById('entry-modal-title');
            const subtitle = document.getElementById('entry-modal-subtitle');
            const submit = document.getElementById('entry-submit');
            const methodInput = document.getElementById('entry-form-method');

            const fields = {
                title: document.getElementById('entry-title'),
                type: document.getElementById('entry-type'),
                dayType: document.getElementById('entry-day-type'),
                date: document.getElementById('entry-date'),
                description: document.getElementById('entry-description'),
            };

            const academicEntries = @json($academicCalendarEntries);
            const calendarModal = document.getElementById('calendar-view-modal');
            const openCalendarButton = document.getElementById('open-calendar-view');
            const closeCalendarButton = document.getElementById('close-calendar-view');
            const calendarMonthsContainer = document.getElementById('calendar-view-months');
            const calendarNavContainer = document.getElementById('calendar-view-nav');
            const calendarNavMobileContainer = document.getElementById('calendar-view-nav-mobile');

            if (calendarModal && openCalendarButton && closeCalendarButton && calendarMonthsContainer) {
                const parseDate = (value) => new Date(`${value}T00:00:00`);
                const formatMonthLabel = (date) => date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
                const formatDayLabel = (date) => date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                const monthKey = (date) => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;

                const calendarData = academicEntries.map((entry) => ({
                    ...entry,
                    date: parseDate(entry.event_date),
                    monthKey: entry.event_date.slice(0, 7),
                }));

                const currentMonth = new Date(new Date().getFullYear(), new Date().getMonth(), 1);
                const firstMonth = calendarData.length > 0
                    ? new Date(calendarData[0].date.getFullYear(), calendarData[0].date.getMonth(), 1)
                    : currentMonth;
                const startMonth = firstMonth < currentMonth ? firstMonth : currentMonth;
                const lastMonth = calendarData.length > 0
                    ? new Date(calendarData[calendarData.length - 1].date.getFullYear(), calendarData[calendarData.length - 1].date.getMonth(), 1)
                    : currentMonth;
                const defaultEnd = new Date(startMonth.getFullYear(), startMonth.getMonth() + 11, 1);
                const endMonth = lastMonth > defaultEnd ? lastMonth : defaultEnd;

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

                calendarData.forEach((entry) => {
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
                        const dayEntries = calendarData.filter((entry) => entry.event_date === dateKey);
                        const hasEntries = dayEntries.length > 0;

                        days.push(`
                            <div class="min-h-[4.25rem] rounded-xl border ${hasEntries ? 'border-sky-200 bg-sky-50' : 'border-slate-200 bg-white'} px-2 py-2">
                                <div class="flex items-center justify-between text-sm font-semibold ${hasEntries ? 'text-slate-900' : 'text-slate-700'}">
                                    <span>${day}</span>
                                    ${hasEntries ? `<span class="rounded-full bg-sky-600 px-2 py-0.5 text-[10px] font-bold text-white">${dayEntries.length}</span>` : ''}
                                </div>
                                <div class="mt-1 space-y-1">
                                    ${dayEntries.slice(0, 2).map((entry) => `
                                        <div class="truncate rounded-md bg-white/80 px-1.5 py-0.5 text-[11px] font-medium text-slate-700 shadow-sm ring-1 ring-slate-200">${entry.title}</div>
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
                        <section id="calendar-month-${index}" class="scroll-mt-6 rounded-3xl border border-slate-200 bg-slate-50 p-4 shadow-sm">
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
                        <button type="button" data-month-target="calendar-month-${index}" class="calendar-month-nav rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:border-sky-300 hover:bg-sky-50">
                            ${formatMonthLabel(monthDate)}
                        </button>
                    `).join('');
                };

                calendarMonthsContainer.innerHTML = months.map((monthDate, index) => renderMonth(monthDate, index)).join('');
                renderNavButtons(calendarNavContainer);
                renderNavButtons(calendarNavMobileContainer);

                document.querySelectorAll('.calendar-month-nav').forEach((button) => {
                    button.addEventListener('click', () => {
                        const target = document.getElementById(button.dataset.monthTarget);
                        if (target) {
                            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    });
                });

                const openCalendar = () => {
                    calendarModal.classList.remove('hidden');
                    calendarModal.classList.add('flex');
                    document.body.classList.add('overflow-hidden');
                };

                const closeCalendar = () => {
                    calendarModal.classList.add('hidden');
                    calendarModal.classList.remove('flex');
                    document.body.classList.remove('overflow-hidden');
                };

                openCalendarButton.addEventListener('click', openCalendar);
                closeCalendarButton.addEventListener('click', closeCalendar);

                calendarModal.addEventListener('click', (event) => {
                    if (event.target === calendarModal) {
                        closeCalendar();
                    }
                });

                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && !calendarModal.classList.contains('hidden')) {
                        closeCalendar();
                    }
                });
            }

            const openModal = (mode) => {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.classList.add('overflow-hidden');

                if (mode === 'edit') {
                    title.textContent = 'Edit Academic Date';
                    subtitle.textContent = 'Update the selected holiday, event, or working status.';
                    submit.textContent = 'Save Changes';
                    methodInput.value = 'PUT';
                } else {
                    title.textContent = 'Add Academic Date';
                    subtitle.textContent = 'Record a holiday or event, then mark whether the date is working or non-working.';
                    submit.textContent = '+ Add Date';
                    methodInput.value = 'POST';
                }
            };

            const closeModal = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
            };

            document.getElementById('open-entry-modal').addEventListener('click', () => {
                form.action = '{{ route('admin.academic-calendar.store') }}';
                form.dataset.entryId = '';
                form.querySelectorAll('input[name="_method"]')[0].value = 'POST';
                form.reset();
                openModal('create');
            });

            document.querySelectorAll('.edit-entry-btn').forEach((button) => {
                button.addEventListener('click', () => {
                    form.action = `{{ url('/admin/academic-calendar') }}/${button.dataset.entryId}`;
                    methodInput.value = 'PUT';
                    fields.title.value = button.dataset.entryTitle || '';
                    fields.type.value = button.dataset.entryType || 'event';
                    fields.dayType.value = button.dataset.entryDayType || 'non_working';
                    fields.date.value = button.dataset.entryDate || '';
                    fields.description.value = button.dataset.entryDescription || '';
                    openModal('edit');
                });
            });

            document.getElementById('close-entry-modal').addEventListener('click', closeModal);
            document.getElementById('cancel-entry-modal').addEventListener('click', closeModal);
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });
        })();
    </script>
@endpush