<div>
    <div class="flex justify-end p-4">
        @livewire('website.localization.language-switcher')
    </div>

    {{-- Live as if you were to die tomorrow. Learn as if you were to live forever. - Mahatma Gandhi --}}
    <h1>{{ __('messages.home_title') }}</h1>
    <p>{{ __('messages.welcome', ['site' => config('app.name')]) }}</p>
</div>
