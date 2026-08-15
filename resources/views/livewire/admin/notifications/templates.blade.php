<div x-data x-init="$store.pageName = { name: 'Notification Configuration', slug: 'notification-configuration' }" class="space-y-6">

    @include('livewire.admin.notifications.partials.tabs')

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-900">Templates by Event</h2>
            <p class="text-xs text-gray-400">The Notification Center only references templates — edit their content in Email/SMS Configuration</p>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach ($events as $event)
                <div class="px-6 py-4 flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-medium text-gray-800">{{ $event->label }}</p>
                        <p class="text-xs text-gray-400 font-mono">{{ $event->event_key }}</p>
                    </div>
                    <div class="flex items-center gap-4 text-xs text-gray-500">
                        <span>Email: <span class="font-mono text-gray-700">{{ $event->email_template_key ?? '—' }}</span></span>
                        <span>SMS: <span class="font-mono text-gray-700">{{ $event->sms_template_key ?? '—' }}</span></span>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-6 py-4 border-t border-gray-100 flex gap-4">
            <a href="{{ route('admin.settings.advance.email-configuration.templates') }}" class="text-xs text-indigo-600 hover:underline">
                Edit Email Templates &rarr;
            </a>
            <a href="{{ route('admin.settings.advance.sms-configuration.templates') }}" class="text-xs text-indigo-600 hover:underline">
                Edit SMS Templates &rarr;
            </a>
        </div>
    </div>

</div>
