@extends('admin.layout')

@section('title', 'Edit Schedule')

@php
    $pageTitle = 'Edit Schedule';
    $pageHeading = 'Edit Employee Schedule';
@endphp

@section('content')
    <div class="rounded-2xl border border-slate-300 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-slate-900">{{ $employee->full_name }}</h2>
                <p class="text-sm text-slate-600">{{ $employee->department?->name ?? 'Unassigned' }}</p>
                <p class="mt-2 text-xs text-slate-500">{{ $submission->semester_label }} {{ $submission->academic_year }}</p>
            </div>
            <a href="{{ route('admin.schedules.index') }}" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Back
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.schedules.update', $submission) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            @php
                $dayLabels = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                $daysByName = $days->keyBy('day_name');
            @endphp

            @foreach ($dayLabels as $index => $dayLabel)
                @php
                    $dayData = $daysByName->get($dayLabel);
                @endphp
                <div class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
                    <h3 class="font-semibold text-slate-900">{{ $dayLabel }}</h3>

                    <div class="mt-4 space-y-3">
                        <label class="flex items-center gap-3">
                            <input
                                type="checkbox"
                                name="days[{{ $index }}][has_work]"
                                value="1"
                                {{ $dayData && $dayData->has_work ? 'checked' : '' }}
                                class="rounded border-slate-300 text-blue-600"
                            >
                            <span class="text-sm font-medium text-slate-700">With Work</span>
                        </label>

                        <div class="space-y-2" id="day-{{ $index }}-times" style="{{ $dayData && $dayData->has_work ? '' : 'display: none;' }}">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase">Time In</label>
                                <input
                                    type="time"
                                    name="days[{{ $index }}][time_in]"
                                    value="{{ $dayData && $dayData->time_in ? $dayData->time_in->format('H:i') : '' }}"
                                    class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                                >
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase">Time Out</label>
                                <input
                                    type="time"
                                    name="days[{{ $index }}][time_out]"
                                    value="{{ $dayData && $dayData->time_out ? $dayData->time_out->format('H:i') : '' }}"
                                    class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                                >
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="days[{{ $index }}][day_name]" value="{{ $dayLabel }}">
                </div>

                <script>
                    document.querySelector('input[name="days[{{ $index }}][has_work]"]').addEventListener('change', function() {
                        const timesDiv = document.getElementById('day-{{ $index }}-times');
                        timesDiv.style.display = this.checked ? '' : 'none';
                    });
                </script>
            @endforeach
        </div>

        <div class="flex items-center justify-between rounded-xl border border-slate-300 bg-slate-50 p-4">
            <p class="text-sm text-slate-600">Changes will be saved and the employee will be notified.</p>
            <button type="submit" class="inline-flex items-center justify-center rounded-md bg-[#00386f] px-6 py-2 text-sm font-semibold text-white shadow-md ring-1 ring-inset ring-[#00386f] hover:bg-[#002f5d]">
                Save & Notify Employee
            </button>
        </div>
    </form>
@endsection
