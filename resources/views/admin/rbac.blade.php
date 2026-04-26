@extends('admin.layout')

@section('title', 'RBAC Permissions Matrix')
@section('page_title', 'RBAC Permissions Matrix')
@section('page_subtitle', 'Configure role-based access control permissions for each module')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="inline-flex rounded-lg bg-slate-300 p-1 text-sm font-semibold">
            <button id="rbac-tab-matrix" class="rounded-md bg-white px-4 py-2">Permission Matrix</button>
            <button id="rbac-tab-role" class="rounded-md px-4 py-2">By Role View</button>
        </div>

        <div class="flex gap-2">
            <button type="button" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold">Reset Changes</button>
            <button type="button" class="rounded-lg bg-[#083b72] px-4 py-2 text-sm font-semibold text-white">Save Changes</button>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.users.rbac.save') }}" id="rbac-role-view" class="space-y-3">
        @csrf
        <div class="flex flex-wrap gap-2">
            @foreach ($roles as $role)
                <button class="rbac-role-btn rounded border border-slate-300 px-4 py-1 text-sm font-semibold {{ $role === 'Admin' ? 'bg-[#083b72] text-white' : 'bg-white text-[#24358a]' }}" data-role="{{ $role }}">{{ $role }}</button>
            @endforeach
        </div>

        <article class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
            <h3 id="rbac-role-title" class="text-3xl font-bold text-[#24358a]">Admin Permissions</h3>
            <p class="text-sm text-slate-500">Configure role-based access control permissions for each module</p>

            <div class="mt-4 space-y-2">
                @foreach ($modules as $module)
                    <div class="rounded-lg bg-[#e9f1ff] px-4 py-3">
                        <p class="font-semibold text-[#24358a]">{{ $module }}</p>
                        <div class="mt-2 flex flex-wrap gap-3 text-xs">
                            @foreach ($permissions as $perm)
                                <label class="inline-flex items-center gap-1">
                                    <input type="checkbox" name="matrix[{{ $role }}][]" value="{{ $perm }}" @checked(in_array($perm, $matrix[$role] ?? [], true))>
                                    <span>{{ $perm }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 text-right">
                <button class="rounded-lg bg-[#083b72] px-4 py-2 text-sm font-semibold text-white">Save Role Permissions</button>
            </div>
        </article>
    </form>

    <div id="rbac-matrix-view" class="hidden">
        <article class="overflow-x-auto rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
            <h3 class="text-2xl font-bold text-[#24358a]">Permission Matrix</h3>
            <table class="mt-3 min-w-full text-left text-xs">
                <thead class="bg-slate-100 text-[#24358a]">
                    <tr>
                        <th class="px-3 py-2">Module</th>
                        <th class="px-3 py-2">Permission</th>
                        <th class="px-3 py-2">Admin</th>
                        <th class="px-3 py-2">HR Personnel</th>
                        <th class="px-3 py-2">Faculty</th>
                        <th class="px-3 py-2">ASP</th>
                        <th class="px-3 py-2">Security</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach ($modules as $module)
                        @foreach ($permissions as $perm)
                            <tr>
                                <td class="px-3 py-2">{{ $module }}</td>
                                <td class="px-3 py-2">{{ $perm }}</td>
                                <td class="px-3 py-2 text-emerald-600">✔</td>
                                <td class="px-3 py-2 text-emerald-600">✔</td>
                                <td class="px-3 py-2 text-blue-600">✖</td>
                                <td class="px-3 py-2 text-blue-600">✖</td>
                                <td class="px-3 py-2 text-blue-600">✖</td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </article>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const matrixTab = document.getElementById('rbac-tab-matrix');
            const roleTab = document.getElementById('rbac-tab-role');
            const matrixView = document.getElementById('rbac-matrix-view');
            const roleView = document.getElementById('rbac-role-view');
            const roleTitle = document.getElementById('rbac-role-title');

            const showRole = () => {
                roleView.classList.remove('hidden');
                matrixView.classList.add('hidden');
                roleTab.classList.add('bg-white');
                matrixTab.classList.remove('bg-white');
            };

            const showMatrix = () => {
                matrixView.classList.remove('hidden');
                roleView.classList.add('hidden');
                matrixTab.classList.add('bg-white');
                roleTab.classList.remove('bg-white');
            };

            roleTab.addEventListener('click', showRole);
            matrixTab.addEventListener('click', showMatrix);

            document.querySelectorAll('.rbac-role-btn').forEach((button) => {
                button.addEventListener('click', () => {
                    document.querySelectorAll('.rbac-role-btn').forEach((b) => {
                        b.classList.remove('bg-[#083b72]', 'text-white');
                        b.classList.add('bg-white', 'text-[#24358a]');
                    });

                    button.classList.add('bg-[#083b72]', 'text-white');
                    button.classList.remove('bg-white', 'text-[#24358a]');
                    roleTitle.textContent = `${button.dataset.role} Permissions`;
                });
            });

            showRole();
        })();
    </script>
@endpush