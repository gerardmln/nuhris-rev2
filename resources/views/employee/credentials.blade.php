@extends('employee.layout')

@section('title', 'Credentials')
@section('page_title', 'Credentials')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4">
        <p class="text-sm text-slate-600">Upload and manage your credentials. HR will review and verify submissions.</p>
        <a href="{{ route('employee.credentials.upload') }}"
           class="rounded-xl bg-[#242b34] px-5 py-2 text-sm font-semibold text-white hover:bg-[#1b222b]"
           data-testid="employee-credentials-upload-new">+ Upload New</a>
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" data-testid="employee-credentials-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800" data-testid="employee-credentials-error">{{ session('error') }}</div>
    @endif

    <div class="inline-flex flex-wrap items-center gap-1 rounded-xl bg-[#c7c7c9] p-1 text-xs font-semibold text-slate-900">
        <button type="button" data-filter="all" class="cred-filter rounded-lg bg-[#d9d9db] px-4 py-2">All ({{ $credentialCounts['all'] }})</button>
        <button type="button" data-filter="resume" class="cred-filter rounded-lg px-4 py-2 hover:bg-[#d9d9db]">Resume ({{ $credentialCounts['resume'] }})</button>
        <button type="button" data-filter="prc" class="cred-filter rounded-lg px-4 py-2 hover:bg-[#d9d9db]">PRC License ({{ $credentialCounts['prc'] }})</button>
        <button type="button" data-filter="seminars" class="cred-filter rounded-lg px-4 py-2 hover:bg-[#d9d9db]">Seminars ({{ $credentialCounts['seminars'] }})</button>
        <button type="button" data-filter="degrees" class="cred-filter rounded-lg px-4 py-2 hover:bg-[#d9d9db]">Degrees ({{ $credentialCounts['degrees'] }})</button>
        <button type="button" data-filter="ranking" class="cred-filter rounded-lg px-4 py-2 hover:bg-[#d9d9db]">Ranking ({{ $credentialCounts['ranking'] }})</button>
    </div>

    @if ($credentials->isEmpty())
        <div class="grid place-items-center py-24">
            <p id="credentials-empty" class="text-3xl text-slate-400">No credentials found. Upload your first credential above</p>
        </div>
    @else
        <article class="overflow-x-auto rounded-2xl border border-slate-300 bg-white shadow-sm">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-100 text-slate-600">
                    <tr>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Title</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">HR Notes</th>
                        <th class="px-4 py-3">Last Updated</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach ($credentials as $credential)
                        @php
                            $statusStyles = match ($credential['status_raw']) {
                                'pending' => 'bg-amber-100 text-amber-800',
                                'verified' => 'bg-emerald-100 text-emerald-800',
                                'rejected' => 'bg-red-100 text-red-800',
                                default => 'bg-slate-100 text-slate-700',
                            };
                        @endphp
                        <tr>
                            <td class="px-4 py-3">{{ $credential['label'] }}</td>
                            <td class="px-4 py-3">{{ $credential['title'] }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full {{ $statusStyles }} px-2.5 py-0.5 text-xs font-semibold">
                                    {{ $credential['status_raw'] === 'verified' ? 'Approved' : $credential['status'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                @if ($credential['review_notes'])
                                    <span title="{{ $credential['review_notes'] }}">{{ \Illuminate\Support\Str::limit($credential['review_notes'], 60) }}</span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ optional($credential['updated_at'])->format('M d, Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </article>
    @endif
@endsection

@push('scripts')
    <script>
        (() => {
            const buttons = document.querySelectorAll('.cred-filter');
            const emptyText = document.getElementById('credentials-empty');

            const labels = {
                all: 'No credentials found. Upload your first credential above',
                resume: 'No Resume credentials found. Upload your first file above',
                prc: 'No PRC License credentials found. Upload your first file above',
                seminars: 'No Seminar credentials found. Upload your first file above',
                degrees: 'No Degree credentials found. Upload your first file above',
                ranking: 'No Ranking credentials found. Upload your first file above',
            };

            const applyFilter = (filter) => {
                buttons.forEach((button) => {
                    const active = button.dataset.filter === filter;
                    button.classList.toggle('bg-[#d9d9db]', active);
                    button.setAttribute('aria-pressed', active ? 'true' : 'false');
                });

                if (emptyText) {
                    emptyText.textContent = labels[filter] ?? labels.all;
                }
            };

            buttons.forEach((button) => {
                button.addEventListener('click', () => applyFilter(button.dataset.filter));
            });

            applyFilter('all');
        })();
    </script>
@endpush
