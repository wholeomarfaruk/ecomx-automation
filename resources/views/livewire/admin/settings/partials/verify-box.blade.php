{{-- Verification code box for the Security Settings email / phone sections.
     Expects: $channel, $sent, $sends, $resendIn, $destination, $sendAction, $verifyAction, $codeField --}}
<div class="rounded-md border border-amber-200 bg-amber-50 p-4">
    @if(!$sent)
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <p class="text-sm text-amber-900">
                {{ $channel === 'email' ? 'Your email is not verified.' : 'Your phone number is not verified.' }}
                We'll send a 6-digit code to <span class="font-medium break-all">{{ $destination }}</span>.
            </p>
            <button type="button" wire:click="{{ $sendAction }}" wire:loading.attr="disabled" wire:target="{{ $sendAction }}"
                class="shrink-0 inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-md text-amber-900 bg-white border border-amber-300 hover:bg-amber-100 disabled:opacity-60">
                <span wire:loading.remove wire:target="{{ $sendAction }}">Send verification code</span>
                <span wire:loading wire:target="{{ $sendAction }}">Sending…</span>
            </button>
        </div>
    @else
        <form wire:submit.prevent="{{ $verifyAction }}" class="space-y-2">
            <label for="{{ $codeField }}" class="block text-sm text-amber-900">
                Enter the 6-digit code sent to <span class="font-medium break-all">{{ $destination }}</span>
                <span class="text-amber-700">(valid {{ \App\Livewire\Admin\Settings\Settings::CODE_TTL_MINUTES }} minutes)</span>
            </label>
            <div class="flex flex-wrap items-center gap-2">
                <input type="text" wire:model="{{ $codeField }}" id="{{ $codeField }}" inputmode="numeric" maxlength="6"
                    autocomplete="one-time-code" placeholder="••••••"
                    class="w-36 p-2 rounded-md border-gray-300 shadow-sm text-center tracking-[0.4em] font-mono focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <button type="submit" wire:loading.attr="disabled" wire:target="{{ $verifyAction }}"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60">
                    <span wire:loading.remove wire:target="{{ $verifyAction }}">Verify</span>
                    <span wire:loading wire:target="{{ $verifyAction }}">Checking…</span>
                </button>

                {{-- Resend countdown runs client-side; wire:key re-inits it after each send --}}
                <span wire:key="{{ $channel }}-resend-{{ $sends }}"
                    x-data="{ left: {{ (int) $resendIn }}, init() { const t = setInterval(() => { if (this.left > 0) this.left--; else clearInterval(t) }, 1000) } }">
                    <button type="button" x-show="left <= 0" wire:click="{{ $sendAction }}" wire:loading.attr="disabled" wire:target="{{ $sendAction }}"
                        class="text-sm font-medium text-indigo-600 hover:text-indigo-800 disabled:opacity-60">Resend code</button>
                    <span x-show="left > 0" x-cloak class="text-xs text-gray-500" x-text="'Resend in ' + left + 's'"></span>
                </span>
            </div>
        </form>
    @endif
    @error($codeField) <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
</div>
