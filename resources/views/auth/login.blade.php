<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Login — {{ \App\Models\Setting::get('site_name', config('app.name'), 'general') }}</title>
    @vite(['resources/sass/admin.scss', 'resources/css/admin.css', 'resources/js/admin.js'])
</head>

<body class="antialiased min-h-screen bg-gray-100 flex items-center justify-center p-4">

    @php
        $siteName   = \App\Models\Setting::get('site_name', config('app.name'), 'general');
        $logoWhite  = \App\Models\Setting::get('site_logo_white', null, 'general');
        $logoUrl    = $logoWhite ? file_path($logoWhite) : null;
        $initial    = strtoupper(mb_substr($siteName, 0, 1));
    @endphp

    <div class="w-full max-w-sm">

        {{-- Branding --}}
        <div class="flex flex-col items-center mb-6">
            @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="h-12 object-contain mb-3">
            @else
                <div class="w-12 h-12 rounded-xl bg-indigo-600 flex items-center justify-center shadow-sm mb-3">
                    <span class="text-white font-black text-lg leading-none select-none">{{ $initial }}</span>
                </div>
            @endif
            <span class="text-gray-800 font-semibold text-lg">{{ $siteName }}</span>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100">
                <h1 class="text-base font-semibold text-gray-900">Admin Login</h1>
                <p class="text-xs text-gray-400 mt-0.5">Sign in to access the admin panel</p>
            </div>

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

        <p class="text-center text-xs text-gray-400 mt-6">{{ $siteName }} &copy; {{ date('Y') }}</p>
    </div>

</body>

</html>
