@extends('admin.layout')

@section('title', 'Create Employee')
@section('page_title', '')

@section('content')
    @php
        $pageSubtitle = 'Register an employee using their existing ID';
        $panelTitle = 'Add Existing Employee';
        $panelSubtitle = 'Register an employee using their existing ID';
        $cancelRoute = route('admin.employees.index');
        $showEmployeeIdInput = true;
        $submitLabel = 'Add Existing Employee';
    @endphp

    <div class="mx-auto max-w-5xl py-2 sm:py-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-[0_20px_50px_rgba(15,23,42,0.12)] sm:p-6">
            <div class="mb-5 flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                <div>
                    <h2 class="text-3xl font-extrabold leading-tight text-[#1f2b5d] sm:text-4xl">{{ $panelTitle }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $panelSubtitle }}</p>
                </div>

                <a href="{{ $cancelRoute }}" class="inline-flex h-10 w-10 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round">
                        <path d="M6 6l12 12" />
                        <path d="M18 6 6 18" />
                    </svg>
                </a>
            </div>

            <form method="POST" action="{{ route('admin.employees.store') }}" data-employee-form>
                @csrf
                @include('hr.employees._form')
            </form>
        </div>
    </div>
@endsection