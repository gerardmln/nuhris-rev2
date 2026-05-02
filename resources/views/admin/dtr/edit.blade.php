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
                    <option value="late" @selected(old('status', $record->status) === 'late')>Late</option>
                    <option value="undertime" @selected(old('status', $record->status) === 'undertime')>Undertime</option>
                    <option value="overtime" @selected(old('status', $record->status) === 'overtime')>Overtime</option>
                </select>
                @error('status')
                    <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <!-- Remarks -->
            <div class="mb-6">
                <label for="remarks" class="block text-sm font-medium text-slate-900 mb-2">Remarks</label>
                <textarea id="remarks" name="remarks" rows="4" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('remarks', $record->remarks) }}</textarea>
                @error('remarks')
                    <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <!-- Actions -->
            <div class="flex gap-4">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium">
                    Save Changes
                </button>
                <a href="{{ route('admin.dtr.index') }}" class="bg-slate-300 hover:bg-slate-400 text-slate-900 px-6 py-2 rounded-lg font-medium">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
