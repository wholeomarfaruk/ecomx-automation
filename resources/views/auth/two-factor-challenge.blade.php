<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Two-Factor Authentication — {{ \App\Models\Setting::get('site_name', config('app.name'), 'general') }}</title>
    @vite(['resources/sass/admin.scss', 'resources/css/admin.css', 'resources/js/admin.js'])
</head>

<body class="antialiased min-h-screen bg-gray-100 flex items-center justify-center p-4">

    @php
        $siteName = \App\Models\Setting::get('site_name', config('app.name'), 'general');
        $logoWhite = \App\Models\Setting::get('site_logo_white', null, 'general');
        $logoUrl = $logoWhite ? file_path($logoWhite) : null;
        $initial = strtoupper(mb_substr($siteName, 0, 1));
    @endphp

    <div class="w-full max-w-sm" x-data="{ recovery: false }">

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
                <h1 class="text-base font-semibold text-gray-900">Two-Factor Authentication</h1>
                <p class="text-xs text-gray-400 mt-0.5" x-show="! recovery">
                    Enter the authentication code from your authenticator app.
                </p>
                <p class="text-xs text-gray-400 mt-0.5" x-cloak x-show="recovery">
                    Enter one of your emergency recovery codes.
                </p>
            </div>

            <form method="POST" action="{{ route('two-factor.login') }}" class="px-6 py-5 space-y-4">
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

                <div x-show="! recovery">
                    <label for="code" class="block text-sm font-medium text-gray-700 mb-1.5">Code</label>
                    <input id="code" type="text" inputmode="numeric" name="code" autofocus x-ref="code" autocomplete="one-time-code"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-mono focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>

                <div x-cloak x-show="recovery">
                    <label for="recovery_code" class="block text-sm font-medium text-gray-700 mb-1.5">Recovery Code</label>
                    <input id="recovery_code" type="text" name="recovery_code" x-ref="recovery_code" autocomplete="one-time-code"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-mono focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>

                <div class="flex items-center justify-between pt-2 border-t border-gray-100">
                    <button type="button" x-show="! recovery"
                        x-on:click="recovery = true; $nextTick(() => { $refs.recovery_code.focus() })"
                        class="text-xs text-gray-500 hover:text-indigo-600 transition">
                        Use a recovery code
                    </button>
                    <button type="button" x-cloak x-show="recovery"
                        x-on:click="recovery = false; $nextTick(() => { $refs.code.focus() })"
                        class="text-xs text-gray-500 hover:text-indigo-600 transition">
                        Use an authentication code
                    </button>

                    <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm">
                        Log in
                    </button>
                </div>
            </form>
        </div>

        <p class="text-center text-xs text-gray-400 mt-6">{{ $siteName }} &copy; {{ date('Y') }}</p>
    </div>

    @livewireScripts
</body>

</html>
