<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login | NU HRIS</title>
    @include('partials.favicon')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-6px); }
            40%, 80% { transform: translateX(6px); }
        }
        .shake { animation: shake 0.45s ease-in-out; }
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-in-down { animation: fadeInDown 0.3s ease-out; }
    </style>
</head>
<body class="min-h-screen antialiased">
    <div class="flex min-h-screen">
        {{-- Left Panel - Background Image --}}
        <div class="hidden lg:flex lg:w-[55%] relative overflow-hidden">
            <img src="{{ asset('images/lipa.jpg') }}"
                 alt="National University Lipa Campus"
                 class="absolute inset-0 h-full w-full object-cover"
                 data-testid="login-bg-image">
            <div class="absolute inset-0 bg-gradient-to-br from-[#00386f]/85 via-[#00386f]/70 to-[#1f2b5d]/80"></div>
            <div class="relative z-10 flex flex-col justify-between p-12 text-white w-full">
                <div>
                    <div class="flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-[#ffdc00]">
                            <span class="text-lg font-extrabold text-[#00386f]">NU</span>
                        </div>
                        <div>
                            <p class="text-xl font-extrabold tracking-wide leading-none">NU HRIS</p>
                            <p class="text-xs text-blue-200 tracking-widest uppercase">Human Resource Information System</p>
                        </div>
                    </div>
                </div>
                <div class="max-w-lg">
                    <h1 class="text-5xl font-extrabold leading-tight mb-4">Empowering your<br>workforce management</h1>
                    <p class="text-lg text-blue-100/90 leading-relaxed">Streamline employee records, attendance tracking, leave management, and credential monitoring all in one place.</p>
                </div>
                <div class="flex items-center gap-8 text-sm text-blue-200">
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m9 12 2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>
                        Attendance Tracking
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m9 12 2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>
                        Leave Management
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m9 12 2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>
                        Credential Monitoring
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Panel - Login Form --}}
        <div class="flex w-full flex-col justify-center px-6 lg:w-[45%] lg:px-16 xl:px-24 bg-[#f8f9fb]">
            {{-- Mobile logo --}}
            <div class="mb-8 flex items-center gap-3 lg:hidden">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#00386f]">
                    <span class="text-sm font-extrabold text-[#ffdc00]">NU</span>
                </div>
                <p class="text-lg font-extrabold text-[#00386f]">NU HRIS</p>
            </div>

            <div class="w-full max-w-md mx-auto">
                <div class="mb-8">
                    <h2 class="text-3xl font-extrabold text-[#1f2b5d]" data-testid="login-title">Welcome back</h2>
                    <p class="mt-2 text-sm text-slate-500">Sign in to your account to continue</p>
                </div>

                @if (session('status'))
                    <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        {{ session('status') }}
                    </div>
                @endif

                {{-- Error banner for invalid credentials --}}
                @if ($errors->any())
                    <div id="login-error-banner"
                         class="mb-4 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 fade-in-down"
                         data-testid="login-error-banner">
                        <svg class="h-5 w-5 shrink-0 text-red-500 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 8v4"/>
                            <path d="M12 16h.01"/>
                        </svg>
                        <div>
                            <p class="font-semibold">Login failed</p>
                            <p class="mt-0.5 text-red-600">
                                @if ($errors->has('email'))
                                    {{ $errors->first('email') }}
                                @else
                                    The email or password you entered is incorrect. Please try again.
                                @endif
                            </p>
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-5" data-testid="login-form" id="login-form">
                    @csrf

                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-semibold text-slate-700">Email Address</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="name@nu.edu.ph"
                            data-testid="login-email-input"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm shadow-sm transition focus:border-[#00386f] focus:outline-none focus:ring-2 focus:ring-[#00386f]/20 {{ $errors->has('email') ? 'border-red-400 ring-1 ring-red-300' : '' }}"
                        >
                        @error('email')
                            <p class="mt-1.5 text-xs text-red-600" data-testid="login-email-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-semibold text-slate-700">Password</label>
                        <div class="relative">
                            <input
                                id="password"
                                name="password"
                                type="password"
                                required
                                autocomplete="current-password"
                                placeholder="Enter your password"
                                data-testid="login-password-input"
                                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 pr-11 text-sm shadow-sm transition focus:border-[#00386f] focus:outline-none focus:ring-2 focus:ring-[#00386f]/20 {{ $errors->has('email') || $errors->has('password') ? 'border-red-400 ring-1 ring-red-300' : '' }}"
                            >
                            <button type="button" id="toggle-password" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600" data-testid="login-toggle-password">
                                <svg id="eye-open" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <svg id="eye-closed" class="h-5 w-5 hidden" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M3.98 8.223A10.477 10.477 0 001.934 12c1.292 4.338 5.31 7.5 10.066 7.5.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-1.5 text-xs text-red-600" data-testid="login-password-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-between">
                        <label for="remember_me" class="flex items-center gap-2 cursor-pointer">
                            <input id="remember_me" type="checkbox" name="remember" data-testid="login-remember-checkbox"
                                   class="h-4 w-4 rounded border-slate-300 text-[#00386f] focus:ring-[#00386f]/50">
                            <span class="text-sm text-slate-600">Remember me</span>
                        </label>

                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-sm font-medium text-[#00386f] hover:underline" data-testid="login-forgot-password">
                                Forgot password?
                            </a>
                        @endif
                    </div>

                    <button type="submit" id="login-submit" data-testid="login-submit-button"
                            class="flex w-full items-center justify-center gap-2 rounded-xl bg-[#00386f] px-4 py-3.5 text-sm font-bold text-white shadow-lg shadow-[#00386f]/25 transition hover:bg-[#002f5d] hover:shadow-xl hover:shadow-[#00386f]/30 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-75 disabled:hover:bg-[#00386f]">
                        <svg id="login-spinner" class="hidden h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span id="login-submit-label">Sign In</span>
                    </button>
                </form>

                <p class="mt-8 text-center text-xs text-slate-400">National University Human Resource Information System</p>
            </div>
        </div>
    </div>

    <script>
        // Password toggle
        const toggleBtn = document.getElementById('toggle-password');
        const passwordInput = document.getElementById('password');
        const eyeOpen = document.getElementById('eye-open');
        const eyeClosed = document.getElementById('eye-closed');

        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                const isPassword = passwordInput.type === 'password';
                passwordInput.type = isPassword ? 'text' : 'password';
                eyeOpen.classList.toggle('hidden', !isPassword);
                eyeClosed.classList.toggle('hidden', isPassword);
            });
        }

        // Remember me: autofill email on load, save/remove on submit
        (function () {
            const REMEMBER_KEY = 'nuhris_remember_email';
            const emailInput = document.getElementById('email');
            const rememberCheckbox = document.getElementById('remember_me');
            const form = document.getElementById('login-form');

            // Autofill saved email (only if Laravel's `old('email')` is empty)
            try {
                const saved = localStorage.getItem(REMEMBER_KEY);
                if (saved && emailInput && !emailInput.value) {
                    emailInput.value = saved;
                    if (rememberCheckbox) rememberCheckbox.checked = true;
                    // Move focus to password when email is already filled
                    const pw = document.getElementById('password');
                    if (pw) pw.focus();
                }
            } catch (e) { /* ignore storage errors */ }

            // On submit: persist or clear based on checkbox
            if (form) {
                form.addEventListener('submit', function () {
                    try {
                        if (rememberCheckbox && rememberCheckbox.checked && emailInput.value) {
                            localStorage.setItem(REMEMBER_KEY, emailInput.value);
                        } else {
                            localStorage.removeItem(REMEMBER_KEY);
                        }
                    } catch (e) { /* ignore */ }
                });
            }
        })();

        // Submit loading state: "Logging in..."
        (function () {
            const form = document.getElementById('login-form');
            const btn = document.getElementById('login-submit');
            const spinner = document.getElementById('login-spinner');
            const label = document.getElementById('login-submit-label');
            if (!form || !btn) return;

            form.addEventListener('submit', function (e) {
                // Let HTML5 required validation run first
                if (!form.checkValidity()) return;

                btn.disabled = true;
                if (spinner) spinner.classList.remove('hidden');
                if (label) label.textContent = 'Logging in...';
            });
        })();

        // Shake + focus on error
        (function () {
            const banner = document.getElementById('login-error-banner');
            if (!banner) return;
            const form = document.getElementById('login-form');
            if (form) {
                form.classList.add('shake');
                setTimeout(() => form.classList.remove('shake'), 500);
            }
            const pw = document.getElementById('password');
            if (pw) {
                pw.focus();
                pw.select();
            }
        })();
    </script>
</body>
</html>
