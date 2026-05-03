@extends('admin.layout')

@section('title', 'Create Employee')
@section('page_title', 'Create Employee')

@section('content')
    @php
        $pageSubtitle = 'Add a new admin employee record';
        $cancelRoute = route('admin.employees.index');
    @endphp

    <div class="mx-auto max-w-5xl space-y-5">
        <div>
            <h2 class="mb-1 text-3xl font-bold text-[#1f2b5d]">Create Employee</h2>
            <p class="text-sm text-slate-500">Add a new admin employee record</p>
        </div>

        <div class="rounded-xl border border-slate-300 bg-white p-5 shadow-sm">
            <form method="POST" action="{{ route('admin.employees.store') }}" data-employee-form>
                @csrf
                @include('hr.employees._form')
            </form>
        </div>
    </div>
@endsection