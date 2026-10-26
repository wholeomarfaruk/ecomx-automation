<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    @php $title = 'Two-Factor Authentication'; @endphp
    @include('layouts.admin.partials.head-meta')
    @vite(['resources/sass/admin.scss', 'resources/css/admin.css', 'resources/js/admin.js'])
</head>

<body class="antialiased min-h-screen bg-gray-100 flex items-center justify-center p-4">

    @php
        $siteName = \App\Models\Setting::get('site_name', config('app.name'), 'general');
        $logoWhite = \App\Models\Setting::get('site_logo_white', null, 'general');
        $logoUrl = $logoWhite ? file_path($logoWhite) : null;
        $initial = strtoupper(mb_substr($siteName, 0, 1));

        // Which methods the user being challenged can use (session login.id is set by Fortify).
        $codes = app(\App\Services\Auth\VerificationCodes::class);
        $challenged = session('login.id') ? \App\Models\User::find(session('login.id')) : null;
        $hasApp = $challenged?->hasAuthenticatorTwoFactor() ?? false;
        $channels = $challenged?->twoFactorCodeChannels() ?? [];
        $channelLabels = ['email' => 'Email', 'sms' => 'SMS'];

        $startMode = session('two_factor_channel') ?? old('channel');
        if (! in_array($startMode, $channels, true)) {
            $startMode = $hasApp || $channels === [] ? 'app' : $channels[0];
        }
    @endphp

    <div class="w-full max-w-sm" x-data="{ mode: '{{ $startMode }}' }">

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
                <p class="text-xs text-gray-400 mt-0.5" x-show="mode === 'app'">
                    Enter the authentication code from your authenticator app.
                </p>
                <p class="text-xs text-gray-400 mt-0.5" x-cloak x-show="mode === 'recovery'">
                    Enter one of your emergency recovery codes.
                </p>
                @foreach($channels as $channel)
                    <p class="text-xs text-gray-400 mt-0.5" x-cloak x-show="mode === '{{ $channel }}'">
                        Enter the 6-digit code sent {{ $channel === 'email' ? 'by email' : 'by SMS' }} to {{ \App\Services\Auth\VerificationCodes::mask($challenged, $channel) }}.
                    </p>
                @endforeach
            </div>

            <div class="px-6 py-5 space-y-4">
                @if ($errors->any())
                    <div class="rounded-xl bg-red-50 border border-red-100 px-4 py-3">
                        <ul class="text-xs text-red-600 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (session('two_factor_send_error'))
                    <div class="rounded-xl bg-red-50 border border-red-100 px-4 py-3 text-xs text-red-600">{{ session('two_factor_send_error') }}</div>
                @endif

                @if (session('status'))
                    <div class="rounded-xl bg-green-50 border border-green-100 px-4 py-3 text-xs text-green-700">{{ session('status') }}</div>
                @endif

                {{-- Authenticator app / recovery code (Fortify) --}}
                @if($hasApp || $channels === [])
                    <form method="POST" action="{{ route('two-factor.login') }}" class="space-y-4"
                        x-show="mode === 'app' || mode === 'recovery'" @if($startMode !== 'app') x-cloak @endif>
                        @csrf

                        <div x-show="mode === 'app'">
                            <label for="code" class="block text-sm font-medium text-gray-700 mb-1.5">Code</label>
                            <input id="code" type="text" inputmode="numeric" name="code" @if($startMode === 'app') autofocus @endif x-ref="code" autocomplete="one-time-code"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-mono focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        </div>

                        <div x-cloak x-show="mode === 'recovery'">
                            <label for="recovery_code" class="block text-sm font-medium text-gray-700 mb-1.5">Recovery Code</label>
                            <input id="recovery_code" type="text" name="recovery_code" x-ref="recovery_code" autocomplete="one-time-code"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-mono focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        </div>

                        <div class="flex justify-end">
                            <button type="submit"
                                class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm">
                                Log in
                            </button>
                        </div>
                    </form>
                @endif

                {{-- Email / SMS codes --}}
                @foreach($channels as $channel)
                    <div x-show="mode === '{{ $channel }}'" @if($startMode !== $channel) x-cloak @endif class="space-y-3">
                        @if($codes->hasPending($challenged, '2fa', $channel))
                            <form method="POST" action="{{ route('two-factor.code.verify') }}" class="space-y-4">
                                @csrf
                                <input type="hidden" name="channel" value="{{ $channel }}">
                                <div>
                                    <label for="code-{{ $channel }}" class="block text-sm font-medium text-gray-700 mb-1.5">{{ $channelLabels[$channel] }} code</label>
                                    <input id="code-{{ $channel }}" type="text" inputmode="numeric" name="code" maxlength="6" autocomplete="one-time-code"
                                        @if($startMode === $channel) autofocus @endif
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-mono tracking-[0.4em] text-center focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                </div>
                                <div class="flex justify-end">
                                    <button type="submit"
                                        class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm">
                                        Log in
                                    </button>
                                </div>
                            </form>
                        @endif

                        {{-- (Re)send — the countdown mirrors the server-side cooldown --}}
                        <form method="POST" action="{{ route('two-factor.code.send') }}"
                            x-data="{ left: {{ $codes->resendIn($challenged, '2fa', $channel) }}, init() { const t = setInterval(() => { if (this.left > 0) this.left--; else clearInterval(t) }, 1000) } }">
                            @csrf
                            <input type="hidden" name="channel" value="{{ $channel }}">
                            @if($codes->hasPending($challenged, '2fa', $channel))
                                <button type="submit" x-bind:disabled="left > 0"
                                    class="text-xs text-gray-500 hover:text-indigo-600 transition disabled:opacity-60 disabled:hover:text-gray-500">
                                    <span x-show="left <= 0">Didn't get it? Resend code</span>
                                    <span x-cloak x-show="left > 0" x-text="'Resend code in ' + left + 's'"></span>
                                </button>
                            @else
                                <button type="submit" x-bind:disabled="left > 0"
                                    class="w-full inline-flex justify-center items-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm disabled:opacity-60">
                                    <span x-show="left <= 0">Send code to {{ \App\Services\Auth\VerificationCodes::mask($challenged, $channel) }}</span>
                                    <span x-cloak x-show="left > 0" x-text="'Send again in ' + left + 's'"></span>
                                </button>
                            @endif
                        </form>
                    </div>
                @endforeach

                {{-- Switch method --}}
                @php
                    $methods = [];
                    if ($hasApp) $methods['app'] = 'Use authenticator app';
                    foreach ($channels as $channel) $methods[$channel] = $channel === 'email' ? 'Get a code by email' : 'Get a code by SMS';
                    if ($hasApp) $methods['recovery'] = 'Use a recovery code';
                @endphp
                @if(count($methods) > 1)
                    <div class="pt-3 border-t border-gray-100 space-y-1.5">
                        <p class="text-[11px] uppercase tracking-wide text-gray-400">Other ways to sign in</p>
                        @foreach($methods as $mode => $label)
                            <button type="button" x-show="mode !== '{{ $mode }}'"
                                x-on:click="mode = '{{ $mode }}'"
                                class="block text-xs text-gray-500 hover:text-indigo-600 transition">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                @endif

                <div class="text-center">
                    <a href="{{ route('login') }}" class="text-xs text-gray-400 hover:text-gray-600">← Back to login</a>
                </div>
            </div>
        </div>

        <p class="text-center text-xs text-gray-400 mt-6">{{ $siteName }} &copy; {{ date('Y') }}</p>
    </div>

    @livewireScripts
</body>

</html>
