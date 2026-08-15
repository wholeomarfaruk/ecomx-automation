<div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h1 class="text-base font-semibold text-gray-900">Admin Login</h1>
            <p class="text-xs text-gray-400 mt-0.5">Sign in to access the admin panel</p>
        </div>

        {{-- Plain form POST to Fortify's real /login route (AuthenticatedSessionController)
             — not wire:submit, so rate limiting, 2FA redirect, and session handling all
             stay exactly as Fortify implements them. This component only owns the UI. --}}
        <form method="POST" action="{{ route('login') }}" class="px-6 py-5 space-y-4">
            @csrf

            @if ($errors->any())
                <div class="rounded-xl bg-red-50 border border-red-100 px-4 py-3">
                    <ul class="text-xs text-red-600 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @session('status')
                <div class="rounded-xl bg-emerald-50 border border-emerald-100 px-4 py-3 text-xs text-emerald-700">
                    {{ $value }}
                </div>
            @endsession

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
                <input id="password" type="password" name="password" required autocomplete="current-password"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
            </div>

            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="remember" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <span class="text-sm text-gray-600">Remember me</span>
            </label>

            <div class="flex items-center justify-between pt-2 border-t border-gray-100">
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-xs text-gray-500 hover:text-indigo-600 transition">
                        Forgot your password?
                    </a>
                @else
                    <span></span>
                @endif

                <button type="submit"
                    class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm">
                    Log in
                </button>
            </div>
        </form>
    </div>
</div>
