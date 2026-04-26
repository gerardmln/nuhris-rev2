@extends('admin.layout')

@section('title', 'Backup & Security')
@section('page_title', 'Backup & Security')

@section('content')
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm"><p class="text-xs text-slate-500">Last Backup</p><p class="text-4xl font-extrabold">{{ $stats['last_backup'] }}</p></article>
        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm"><p class="text-xs text-slate-500">Storage Used</p><p class="text-4xl font-extrabold">{{ $stats['storage_used'] }}</p></article>
        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm"><p class="text-xs text-slate-500">Failed Logins (24h)</p><p class="text-4xl font-extrabold">{{ $stats['failed_logins'] }}</p></article>
        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm"><p class="text-xs text-slate-500">Security Score</p><p class="text-4xl font-extrabold">{{ $stats['security_score'] }}</p></article>
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
            <h3 class="text-2xl font-bold text-[#24358a]">Backup Management</h3>
            <div class="mt-2 flex gap-2">
                <button class="rounded border border-slate-300 bg-[#083b72] px-3 py-1 text-xs font-semibold text-white">Manual Backup</button>
                <button class="rounded border border-slate-300 bg-white px-3 py-1 text-xs font-semibold">Restore Backup</button>
            </div>
            <div class="mt-3 space-y-2 text-xs">
                @foreach ($backups as $backup)
                    <div class="flex items-center justify-between rounded bg-slate-100 px-3 py-2"><span>{{ $backup['name'] }}</span><span>{{ $backup['size'] }}</span></div>
                @endforeach
            </div>
        </article>

        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
            <h3 class="text-2xl font-bold text-[#24358a]">Security Alerts</h3>
            <div class="mt-3 space-y-2 text-xs">
                @foreach ($alerts as $alert)
                    <div class="rounded bg-blue-100 px-3 py-2">{{ $alert }}</div>
                @endforeach
            </div>
        </article>
    </div>
@endsection
