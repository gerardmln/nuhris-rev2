{{-- Sign-out confirmation modal. Trigger via any button with class="logout-trigger" --}}
<div id="logout-modal"
     class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm px-4"
     data-testid="logout-modal"
     aria-hidden="true"
     role="dialog"
     aria-modal="true"
     aria-labelledby="logout-modal-title">
    <div id="logout-modal-card"
         class="w-full max-w-md scale-95 opacity-0 rounded-2xl bg-white p-6 shadow-2xl transition-all duration-200">
        <div class="flex items-start gap-4">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-red-100">
                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
            </div>
            <div class="flex-1">
                <h3 id="logout-modal-title" class="text-lg font-bold text-slate-900">Sign out of NU HRIS?</h3>
                <p class="mt-1.5 text-sm text-slate-500">Are you sure you want to sign out? You will need to log in again to access your dashboard.</p>
            </div>
        </div>

        <div class="mt-6 flex items-center justify-end gap-3">
            <button type="button"
                    id="logout-cancel"
                    class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    data-testid="logout-cancel-button">
                Cancel
            </button>
            <form method="POST" action="{{ route('logout') }}" id="logout-form">
                @csrf
                <button type="submit"
                        id="logout-confirm"
                        class="flex items-center gap-2 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-red-500/25 transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-75"
                        data-testid="logout-confirm-button">
                    <svg id="logout-spinner" class="hidden h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span id="logout-confirm-label">Yes, sign out</span>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('logout-modal');
        const card = document.getElementById('logout-modal-card');
        const cancelBtn = document.getElementById('logout-cancel');
        const confirmBtn = document.getElementById('logout-confirm');
        const spinner = document.getElementById('logout-spinner');
        const label = document.getElementById('logout-confirm-label');
        const form = document.getElementById('logout-form');
        if (!modal || !form) return;

        const openModal = () => {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            modal.setAttribute('aria-hidden', 'false');
            // animate card in
            requestAnimationFrame(() => {
                card.classList.remove('scale-95', 'opacity-0');
                card.classList.add('scale-100', 'opacity-100');
            });
        };

        const closeModal = () => {
            card.classList.add('scale-95', 'opacity-0');
            card.classList.remove('scale-100', 'opacity-100');
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                modal.setAttribute('aria-hidden', 'true');
            }, 150);
        };

        document.querySelectorAll('.logout-trigger').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                openModal();
            });
        });

        cancelBtn?.addEventListener('click', closeModal);

        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
        });

        form.addEventListener('submit', () => {
            if (confirmBtn) confirmBtn.disabled = true;
            if (spinner) spinner.classList.remove('hidden');
            if (label) label.textContent = 'Signing out...';
        });
    })();
</script>
