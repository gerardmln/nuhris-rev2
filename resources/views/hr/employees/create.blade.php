@extends('hr.layout')

@php
    $pageTitle = 'Create Employee';
    $pageHeading = 'Create Employee';
    $activeNav = 'employees';
@endphp

@section('content')
    <div class="mx-auto max-w-5xl">
        <h2 class="mb-1 text-3xl font-bold text-[#1f2b5d]">Create Employee</h2>
        <p class="mb-5 text-sm text-slate-500">Add a new HR employee record</p>

        <div class="rounded-xl border border-slate-300 bg-white p-5 shadow-sm">
            <form method="POST" action="{{ route('employees.store') }}" data-employee-form>
                @csrf
                @include('hr.employees._form')
            </form>
        </div>
    </div>
@endsection
