@extends('admin.layout')

@section('title', 'User Management')
@section('page_title', 'User Management')
@section('page_subtitle', 'Manage user accounts, roles and access')

@section('content')
    <div class="flex flex-wrap items-center gap-2">
        <input
            type="text"
            id="user-search"
            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm md:w-72"
            placeholder="Search by name, email, or role..."
            data-testid="user-search-input">
        <select id="user-role-filter" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" data-testid="user-role-filter">
            <option value="">All Roles</option>
            @foreach ($roles as $role)
                <option value="{{ $role }}">{{ $role }}</option>
            @endforeach
        </select>
        <select id="user-status-filter" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" data-testid="user-status-filter">
            <option value="">All Status</option>
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
        </select>
        <div class="ml-auto flex items-center gap-2">
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600" id="user-count-chip" data-testid="user-count-chip">{{ $users->count() }} users</span>
        </div>
    </div>

    <article class="overflow-x-auto rounded-xl border border-slate-300 bg-white shadow-sm">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-[#24358a]">
                <tr>
                    <th class="px-4 py-3 font-semibold">User</th>
                    <th class="px-4 py-3 font-semibold">Role</th>
                    <th class="px-4 py-3 font-semibold">Department</th>
                    <th class="px-4 py-3 font-semibold">Status</th>
                    <th class="px-4 py-3 font-semibold">Last Login</th>
                    <th class="px-4 py-3 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200" id="user-tbody">
                @foreach ($users as $user)
                    <tr class="user-row transition hover:bg-slate-50"
                        data-name="{{ strtolower($user['name']) }}"
                        data-email="{{ strtolower($user['email']) }}"
                        data-role="{{ $user['role'] }}"
                        data-status="{{ $user['status'] }}"
                        data-testid="user-row-{{ $user['id'] }}">
                        <td class="px-4 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#0a3f79]/10 text-xs font-bold text-[#0a3f79]">
                                    {{ strtoupper(mb_substr($user['name'], 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-semibold text-[#24358a]">{{ $user['name'] }}</p>
                                    <p class="text-xs text-slate-500">{{ $user['email'] }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            @php
                                $roleBg = match ($user['role']) {
                                    'Admin' => 'bg-indigo-100 text-indigo-700',
                                    'HR Personnel' => 'bg-sky-100 text-sky-700',
                                    default => 'bg-slate-100 text-slate-700',
                                };
                            @endphp
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $roleBg }}">{{ $user['role'] }}</span>
                        </td>
                        <td class="px-4 py-4 text-slate-700">{{ $user['department'] }}</td>
                        <td class="px-4 py-4">
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $user['status'] === 'Active' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                {{ $user['status'] }}
                            </span>
                        </td>
                        <td class="px-4 py-4 text-slate-600">{{ $user['last_login'] }}</td>
                        <td class="px-4 py-4 text-right">
                            <div class="relative inline-block text-left">
                                <button type="button"
                                        class="user-menu-toggle rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                                        aria-haspopup="true"
                                        aria-expanded="false"
                                        data-testid="user-menu-toggle-{{ $user['id'] }}">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><circle cx="5" cy="12" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="19" cy="12" r="1.8"/></svg>
                                </button>
                                <div class="user-menu-panel absolute right-0 z-20 mt-2 hidden w-44 origin-top-right overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-xl">
                                    <button type="button"
                                            class="edit-role-trigger flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-slate-700 hover:bg-slate-50"
                                            data-user-id="{{ $user['id'] }}"
                                            data-user-name="{{ $user['name'] }}"
                                            data-user-email="{{ $user['email'] }}"
                                            data-user-type="{{ $user['user_type'] }}"
                                            data-testid="user-edit-role-{{ $user['id'] }}">
                                        <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        Edit role
                                    </button>
                                    <button type="button"
                                            class="delete-user-trigger flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50"
                                            data-user-id="{{ $user['id'] }}"
                                            data-user-name="{{ $user['name'] }}"
                                            data-user-email="{{ $user['email'] }}"
                                            data-testid="user-delete-{{ $user['id'] }}">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M8 7V4a1 1 0 011-1h6a1 1 0 011 1v3"/></svg>
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforeach
                <tr id="user-empty-row" class="hidden">
                    <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">No users match your search.</td>
                </tr>
            </tbody>
        </table>
    </article>

    {{-- Edit Role Modal --}}
    <div id="edit-role-modal"
         class="fixed inset-0 z-[90] hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm px-4"
         data-testid="edit-role-modal"
         aria-hidden="true">
        <div id="edit-role-card"
             class="w-full max-w-md scale-95 opacity-0 rounded-2xl bg-white p-6 shadow-2xl transition-all duration-200">
            <form method="POST" action="{{ route('admin.users.role-assignment.update') }}" id="edit-role-form">
                @csrf
                <input type="hidden" name="user_id" id="edit-role-user-id">

                <div class="flex items-start gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-indigo-100">
                        <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-slate-900">Change user role</h3>
                        <p class="mt-1 text-sm text-slate-500">Update the role for <span class="font-semibold" id="edit-role-user-name"></span>.</p>
                        <p class="text-xs text-slate-400" id="edit-role-user-email"></p>
                    </div>
                </div>

                <div class="mt-5">
                    <label class="mb-2 block text-sm font-semibold text-slate-700" for="edit-role-user-type">Role</label>
                    <select name="user_type" id="edit-role-user-type" required
                            class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-[#0a3f79] focus:outline-none focus:ring-2 focus:ring-[#0a3f79]/20"
                            data-testid="edit-role-select">
                        <option value="{{ $roleOptionMap['Admin'] }}">Admin</option>
                        <option value="{{ $roleOptionMap['HR Personnel'] }}">HR Personnel</option>
                        <option value="{{ $roleOptionMap['Employee'] }}">Employee</option>
                    </select>
                </div>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <button type="button"
                            id="edit-role-cancel"
                            class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                            data-testid="edit-role-cancel-button">
                        Cancel
                    </button>
                    <button type="submit"
                            class="rounded-xl bg-[#0a3f79] px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#0a3f79]/20 transition hover:bg-[#083266]"
                            data-testid="edit-role-save-button">
                        Save role
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Delete User Modal --}}
    <div id="delete-user-modal"
         class="fixed inset-0 z-[90] hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm px-4"
         data-testid="delete-user-modal"
         aria-hidden="true">
        <div id="delete-user-card"
             class="w-full max-w-md scale-95 opacity-0 rounded-2xl bg-white p-6 shadow-2xl transition-all duration-200">
            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-red-100">
                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-bold text-slate-900">Delete this user?</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        This will permanently remove
                        <span class="font-semibold" id="delete-user-name"></span>
                        (<span id="delete-user-email"></span>) from the system. This action cannot be undone.
                    </p>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3">
                <button type="button"
                        id="delete-user-cancel"
                        class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                        data-testid="delete-user-cancel-button">
                    Cancel
                </button>
                <form method="POST" id="delete-user-form" action="">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-red-500/25 transition hover:bg-red-700"
                            data-testid="delete-user-confirm-button">
                        Yes, delete user
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        // ====== 3-dots dropdown ======
        const toggles = document.querySelectorAll('.user-menu-toggle');

        const closeAll = () => {
            document.querySelectorAll('.user-menu-panel').forEach((p) => p.classList.add('hidden'));
            toggles.forEach((t) => t.setAttribute('aria-expanded', 'false'));
        };

        toggles.forEach((toggle) => {
            toggle.addEventListener('click', (e) => {
                e.stopPropagation();
                const panel = toggle.nextElementSibling;
                const isOpen = !panel.classList.contains('hidden');
                closeAll();
                if (!isOpen) {
                    panel.classList.remove('hidden');
                    toggle.setAttribute('aria-expanded', 'true');
                }
            });
        });
        document.addEventListener('click', closeAll);
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeAll(); });

        // ====== Modal helpers ======
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

        // ====== Edit Role modal ======
        const editModal = document.getElementById('edit-role-modal');
        const editCard = document.getElementById('edit-role-card');
        const editIdInput = document.getElementById('edit-role-user-id');
        const editNameEl = document.getElementById('edit-role-user-name');
        const editEmailEl = document.getElementById('edit-role-user-email');
        const editTypeSelect = document.getElementById('edit-role-user-type');

        document.querySelectorAll('.edit-role-trigger').forEach((btn) => {
            btn.addEventListener('click', () => {
                closeAll();
                editIdInput.value = btn.dataset.userId;
                editNameEl.textContent = btn.dataset.userName;
                editEmailEl.textContent = btn.dataset.userEmail;
                editTypeSelect.value = btn.dataset.userType;
                openModal(editModal, editCard);
            });
        });
        document.getElementById('edit-role-cancel').addEventListener('click', () => closeModal(editModal, editCard));
        editModal.addEventListener('click', (e) => { if (e.target === editModal) closeModal(editModal, editCard); });

        // ====== Delete User modal ======
        const delModal = document.getElementById('delete-user-modal');
        const delCard = document.getElementById('delete-user-card');
        const delNameEl = document.getElementById('delete-user-name');
        const delEmailEl = document.getElementById('delete-user-email');
        const delForm = document.getElementById('delete-user-form');

        document.querySelectorAll('.delete-user-trigger').forEach((btn) => {
            btn.addEventListener('click', () => {
                closeAll();
                delNameEl.textContent = btn.dataset.userName;
                delEmailEl.textContent = btn.dataset.userEmail;
                delForm.action = `{{ url('admin/user-management/accounts') }}/${btn.dataset.userId}`;
                openModal(delModal, delCard);
            });
        });
        document.getElementById('delete-user-cancel').addEventListener('click', () => closeModal(delModal, delCard));
        delModal.addEventListener('click', (e) => { if (e.target === delModal) closeModal(delModal, delCard); });

        // Shared ESC for both modals
        document.addEventListener('keydown', (e) => {
            if (e.key !== 'Escape') return;
            if (!editModal.classList.contains('hidden')) closeModal(editModal, editCard);
            if (!delModal.classList.contains('hidden')) closeModal(delModal, delCard);
        });

        // ====== Search & filters ======
        const searchInput = document.getElementById('user-search');
        const roleFilter = document.getElementById('user-role-filter');
        const statusFilter = document.getElementById('user-status-filter');
        const countChip = document.getElementById('user-count-chip');
        const emptyRow = document.getElementById('user-empty-row');
        const rows = document.querySelectorAll('.user-row');

        const applyFilters = () => {
            const term = (searchInput.value || '').toLowerCase().trim();
            const role = roleFilter.value;
            const status = statusFilter.value;
            let visible = 0;
            rows.forEach((row) => {
                const matchesTerm = !term || row.dataset.name.includes(term) || row.dataset.email.includes(term) || row.dataset.role.toLowerCase().includes(term);
                const matchesRole = !role || row.dataset.role === role;
                const matchesStatus = !status || row.dataset.status === status;
                const show = matchesTerm && matchesRole && matchesStatus;
                row.classList.toggle('hidden', !show);
                if (show) visible++;
            });
            emptyRow.classList.toggle('hidden', visible > 0);
            countChip.textContent = `${visible} user${visible === 1 ? '' : 's'}`;
        };
        [searchInput, roleFilter, statusFilter].forEach((el) => el.addEventListener('input', applyFilters));
    })();
</script>
@endpush
