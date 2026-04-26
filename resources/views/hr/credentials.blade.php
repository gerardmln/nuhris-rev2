@extends('hr.layout')

@php
    $pageTitle = 'Credential Verification';
    $pageHeading = 'Credential Verification';
    $activeNav = 'credentials';
@endphp

@section('content')
    <div>
        <p class="text-sm text-slate-500">Review and approve credential files submitted by employees.</p>
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" data-testid="hr-credentials-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800" data-testid="hr-credentials-error">{{ session('error') }}</div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total Submissions</p>
            <p class="mt-1 text-3xl font-extrabold text-slate-900">{{ $stats['total'] }}</p>
        </article>
        <article class="rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-amber-700">Pending Review</p>
            <p class="mt-1 text-3xl font-extrabold text-amber-900">{{ $stats['pending'] }}</p>
        </article>
        <article class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-emerald-700">Approved</p>
            <p class="mt-1 text-3xl font-extrabold text-emerald-900">{{ $stats['verified'] }}</p>
        </article>
        <article class="rounded-xl border border-red-200 bg-red-50 p-4 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-red-700">Rejected</p>
            <p class="mt-1 text-3xl font-extrabold text-red-900">{{ $stats['rejected'] }}</p>
        </article>
    </div>

    {{-- Tabs --}}
    <div class="flex flex-wrap items-center gap-2 rounded-xl border border-slate-200 bg-white p-1.5 shadow-sm" data-testid="hr-credentials-tabs">
        @php
            $tabs = [
                ['key' => 'pending',  'label' => 'Pending',  'count' => $stats['pending']],
                ['key' => 'verified', 'label' => 'Approved', 'count' => $stats['verified']],
                ['key' => 'rejected', 'label' => 'Rejected', 'count' => $stats['rejected']],
                ['key' => 'all',      'label' => 'All',      'count' => $stats['total']],
            ];
        @endphp
        @foreach ($tabs as $tab)
            @php($active = $statusFilter === $tab['key'])
            <a href="{{ route('credentials.index', ['status' => $tab['key']]) }}"
               class="flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition {{ $active ? 'bg-[#00386f] text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}"
               data-testid="hr-credentials-tab-{{ $tab['key'] }}">
                {{ $tab['label'] }}
                <span class="rounded-full {{ $active ? 'bg-white/20' : 'bg-slate-200 text-slate-600' }} px-2 py-0.5 text-xs">{{ $tab['count'] }}</span>
            </a>
        @endforeach
    </div>

    {{-- List --}}
    <div class="space-y-3">
        @forelse ($credentials as $credential)
            <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm" data-testid="hr-credential-card-{{ $credential['id'] }}">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex items-start gap-4">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#0a3f79]/10 text-[#0a3f79]">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-base font-bold text-slate-900">{{ $credential['title'] }}</h3>
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700">{{ $credential['type_label'] }}</span>
                                @php
                                    $statusStyles = match ($credential['status']) {
                                        'pending' => 'bg-amber-100 text-amber-800',
                                        'verified' => 'bg-emerald-100 text-emerald-800',
                                        'rejected' => 'bg-red-100 text-red-800',
                                        default => 'bg-slate-100 text-slate-700',
                                    };
                                @endphp
                                <span class="rounded-full {{ $statusStyles }} px-2.5 py-0.5 text-xs font-semibold capitalize">{{ $credential['status'] === 'verified' ? 'Approved' : $credential['status'] }}</span>
                            </div>
                            <p class="mt-1 text-sm text-slate-700">
                                <span class="font-semibold">{{ $credential['employee_name'] }}</span>
                                <span class="text-slate-400">·</span>
                                <span class="text-slate-500">{{ $credential['employee_email'] }}</span>
                            </p>
                            <p class="text-xs text-slate-500">
                                {{ $credential['department'] }}
                                @if ($credential['expires_at'])
                                    <span class="mx-1.5">·</span> Expires {{ $credential['expires_at'] }}
                                @endif
                                <span class="mx-1.5">·</span> Submitted {{ $credential['submitted_at'] }}
                            </p>
                            @if ($credential['description'])
                                <p class="mt-2 text-sm text-slate-600">{{ $credential['description'] }}</p>
                            @endif
                            @if ($credential['review_notes'])
                                <p class="mt-2 rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600">
                                    <span class="font-semibold">HR notes{{ $credential['reviewer_name'] ? ' ('.$credential['reviewer_name'].')' : '' }}:</span>
                                    {{ $credential['review_notes'] }}
                                </p>
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        @if ($credential['has_file'])
                            <a href="{{ route('credentials.view', $credential['id']) }}"
                               target="_blank" rel="noopener"
                               class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                               data-testid="hr-credential-view-{{ $credential['id'] }}">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                View file
                            </a>
                        @else
                            <span class="rounded-lg bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-400">No file attached</span>
                        @endif

                        @if ($credential['status'] === 'pending')
                            <button type="button"
                                    class="approve-trigger inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-700"
                                    data-credential-id="{{ $credential['id'] }}"
                                    data-credential-title="{{ $credential['title'] }}"
                                    data-employee-name="{{ $credential['employee_name'] }}"
                                    data-testid="hr-credential-approve-{{ $credential['id'] }}">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                Approve
                            </button>
                            <button type="button"
                                    class="reject-trigger inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-red-700"
                                    data-credential-id="{{ $credential['id'] }}"
                                    data-credential-title="{{ $credential['title'] }}"
                                    data-employee-name="{{ $credential['employee_name'] }}"
                                    data-testid="hr-credential-reject-{{ $credential['id'] }}">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                Reject
                            </button>
                        @elseif ($credential['reviewed_at'])
                            <span class="text-xs text-slate-500">Reviewed {{ $credential['reviewed_at'] }}</span>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <article class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center shadow-sm" data-testid="hr-credentials-empty">
                <p class="text-sm font-semibold text-slate-600">No credentials to show</p>
                <p class="mt-1 text-xs text-slate-500">
                    @if ($statusFilter === 'pending')
                        There are no pending credential submissions right now.
                    @elseif ($statusFilter === 'verified')
                        No approved credentials yet.
                    @elseif ($statusFilter === 'rejected')
                        No rejected credentials on record.
                    @else
                        Once employees upload credentials, they will appear here.
                    @endif
                </p>
            </article>
        @endforelse
    </div>

    {{-- Approve Modal --}}
    <div id="approve-modal"
         class="fixed inset-0 z-[90] hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm px-4"
         data-testid="hr-approve-modal"
         aria-hidden="true">
        <div id="approve-card" class="w-full max-w-md scale-95 opacity-0 rounded-2xl bg-white p-6 shadow-2xl transition-all duration-200">
            <form method="POST" action="" id="approve-form">
                @csrf
                <div class="flex items-start gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-emerald-100">
                        <svg class="h-6 w-6 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-slate-900">Approve credential?</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Approve <span class="font-semibold" id="approve-title"></span> for
                            <span class="font-semibold" id="approve-employee"></span>.
                        </p>
                    </div>
                </div>
                <div class="mt-5">
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Notes <span class="font-normal text-slate-400">(optional)</span></label>
                    <textarea name="review_notes" rows="3"
                              class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                              placeholder="Optional approval notes for the employee…"
                              data-testid="hr-approve-notes"></textarea>
                </div>
                <div class="mt-6 flex items-center justify-end gap-3">
                    <button type="button" id="approve-cancel"
                            class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                            data-testid="hr-approve-cancel">Cancel</button>
                    <button type="submit"
                            class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/20 hover:bg-emerald-700"
                            data-testid="hr-approve-confirm">Confirm approve</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Reject Modal --}}
    <div id="reject-modal"
         class="fixed inset-0 z-[90] hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm px-4"
         data-testid="hr-reject-modal"
         aria-hidden="true">
        <div id="reject-card" class="w-full max-w-md scale-95 opacity-0 rounded-2xl bg-white p-6 shadow-2xl transition-all duration-200">
            <form method="POST" action="" id="reject-form">
                @csrf
                <div class="flex items-start gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-red-100">
                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-slate-900">Reject credential?</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Reject <span class="font-semibold" id="reject-title"></span> for
                            <span class="font-semibold" id="reject-employee"></span>. Please provide a reason.
                        </p>
                    </div>
                </div>
                <div class="mt-5">
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Reason <span class="text-red-500">*</span></label>
                    <textarea name="review_notes" rows="3" required
                              class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-500/20"
                              placeholder="Explain what the employee needs to fix…"
                              data-testid="hr-reject-notes"></textarea>
                </div>
                <div class="mt-6 flex items-center justify-end gap-3">
                    <button type="button" id="reject-cancel"
                            class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                            data-testid="hr-reject-cancel">Cancel</button>
                    <button type="submit"
                            class="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-red-500/20 hover:bg-red-700"
                            data-testid="hr-reject-confirm">Confirm reject</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        const openModal = (modal, card) => {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            modal.setAttribute('aria-hidden', 'false');
            requestAnimationFrame(() => {
                card.classList.remove('scale-95', 'opacity-0');
                card.classList.add('scale-100', 'opacity-100');
            });
        };
        const closeModal = (modal, card) => {
            card.classList.add('scale-95', 'opacity-0');
            card.classList.remove('scale-100', 'opacity-100');
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                modal.setAttribute('aria-hidden', 'true');
            }, 150);
        };

        // Approve modal
        const approveModal = document.getElementById('approve-modal');
        const approveCard = document.getElementById('approve-card');
        const approveForm = document.getElementById('approve-form');
        const approveTitle = document.getElementById('approve-title');
        const approveEmployee = document.getElementById('approve-employee');
        document.querySelectorAll('.approve-trigger').forEach((btn) => {
            btn.addEventListener('click', () => {
                approveTitle.textContent = btn.dataset.credentialTitle;
                approveEmployee.textContent = btn.dataset.employeeName;
                approveForm.action = `{{ url('hr/credentials') }}/${btn.dataset.credentialId}/approve`;
                openModal(approveModal, approveCard);
            });
        });
        document.getElementById('approve-cancel').addEventListener('click', () => closeModal(approveModal, approveCard));
        approveModal.addEventListener('click', (e) => { if (e.target === approveModal) closeModal(approveModal, approveCard); });

        // Reject modal
        const rejectModal = document.getElementById('reject-modal');
        const rejectCard = document.getElementById('reject-card');
        const rejectForm = document.getElementById('reject-form');
        const rejectTitle = document.getElementById('reject-title');
        const rejectEmployee = document.getElementById('reject-employee');
        document.querySelectorAll('.reject-trigger').forEach((btn) => {
            btn.addEventListener('click', () => {
                rejectTitle.textContent = btn.dataset.credentialTitle;
                rejectEmployee.textContent = btn.dataset.employeeName;
                rejectForm.action = `{{ url('hr/credentials') }}/${btn.dataset.credentialId}/reject`;
                openModal(rejectModal, rejectCard);
            });
        });
        document.getElementById('reject-cancel').addEventListener('click', () => closeModal(rejectModal, rejectCard));
        rejectModal.addEventListener('click', (e) => { if (e.target === rejectModal) closeModal(rejectModal, rejectCard); });

        document.addEventListener('keydown', (e) => {
            if (e.key !== 'Escape') return;
            if (!approveModal.classList.contains('hidden')) closeModal(approveModal, approveCard);
            if (!rejectModal.classList.contains('hidden')) closeModal(rejectModal, rejectCard);
        });
    })();
</script>
@endpush
