@extends('admin.layout')

@section('title', 'Activity Logs')

@section('content')
<div class="p-8">
    <h1 class="text-4xl font-bold text-slate-900 mb-8">Activity Logs</h1>

    <div class="bg-white rounded-lg shadow p-8">
        <div class="text-center">
            <div class="mb-6">
                <svg class="w-24 h-24 text-slate-300 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-slate-900 mb-2">Activity Logs</h2>
            <p class="text-slate-600 mb-4">{{ $message ?? 'Activity Logs module is reserved for future development.' }}</p>
            <p class="text-sm text-slate-500">This module will track and display all user activities and system events in the HRIS system.</p>
        </div>
    </div>
</div>
@endsection
