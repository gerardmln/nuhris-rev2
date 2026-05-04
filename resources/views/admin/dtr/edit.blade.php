@extends('admin.layout')

@section('title', 'Edit DTR Record')

@section('content')
<div class="p-8">
    <h1 class="text-4xl font-bold text-slate-900 mb-8">Edit DTR Record</h1>

    <div class="bg-white rounded-lg shadow p-8 max-w-2xl">
        <form action="{{ route('admin.dtr.update', $record->id) }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Record Date -->
            <div class="mb-6 p-4 bg-slate-50 rounded-lg">
                <p class="text-sm font-medium text-slate-600">Record Date</p>
                <p class="text-xl font-bold text-slate-900">{{ $record->record_date->format('F d, Y') }}</p>
            </div>

            <!-- Employee -->
            <div class="mb-6 p-4 bg-slate-50 rounded-lg">
                <p class="text-sm font-medium text-slate-600">Employee</p>
                <p class="text-xl font-bold text-slate-900">{{ $record->employee?->full_name ?? 'N/A' }}</p>
            </div>

            <div class="mb-6 p-4 bg-red-50 rounded-lg border border-red-200">
                <p class="text-sm font-medium text-red-600">Absences ({{ $record->record_date->format('F Y') }})</p>
                <p class="text-xl font-bold text-red-700">{{ $absenceCount ?? 0 }}</p>
            </div>

            <!-- Time In -->
            <div class="mb-6">
                <label for="time_in" class="block text-sm font-medium text-slate-900 mb-2">Time In (HH:MM)</label>
                <input type="time" id="time_in" name="time_in" value="{{ old('time_in', $record->time_in) }}" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
                @error('time_in')
                    <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <!-- Time Out -->
            <div class="mb-6">
                <label for="time_out" class="block text-sm font-medium text-slate-900 mb-2">Time Out (HH:MM)</label>
                <input type="time" id="time_out" name="time_out" value="{{ old('time_out', $record->time_out) }}" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
                @error('time_out')
                    <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <!-- Status -->
            <div class="mb-6">
                <label for="status" class="block text-sm font-medium text-slate-900 mb-2">Status</label>
                <select id="status" name="status" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="present" @selected(old('status', $record->status) === 'present')>Present</option>
                    <option value="absent" @selected(old('status', $record->status) === 'absent')>Absent</option>
                </select>
                @error('status')
                    <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <!-- Remarks -->
            <div class="mb-6">
                <label for="remarks" class="block text-sm font-medium text-slate-900 mb-2">Remarks</label>
                <textarea id="remarks" name="remarks" rows="4" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('remarks', $record->schedule_notes) }}</textarea>
                @error('remarks')
                    <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="mb-6">
                    <label for="tardiness_minutes" class="block text-sm font-medium text-slate-900 mb-2">Tardiness (minutes)</label>
                    <input type="number" id="tardiness_minutes" name="tardiness_minutes" min="0" value="{{ old('tardiness_minutes', $record->tardiness_minutes) }}" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    @error('tardiness_minutes')
                        <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-6">
                    <label for="undertime_minutes" class="block text-sm font-medium text-slate-900 mb-2">Undertime (minutes)</label>
                    <input type="number" id="undertime_minutes" name="undertime_minutes" min="0" value="{{ old('undertime_minutes', $record->undertime_minutes) }}" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    @error('undertime_minutes')
                        <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <button type="submit" class="w-full sm:w-auto rounded-lg px-6 py-3 font-semibold text-white shadow-lg transition hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-blue-300" style="background-color:#1d4ed8 !important; border:1px solid #1d4ed8 !important; color:#ffffff !important;">
                    Save Changes
                </button>
                <a href="{{ route('admin.dtr.index') }}" class="w-full sm:w-auto rounded-lg border border-slate-300 bg-white px-6 py-3 text-center font-semibold text-slate-700 transition hover:bg-slate-50">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
