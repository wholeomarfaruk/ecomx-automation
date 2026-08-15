<div x-data x-init="$store.pageName = { name: 'Notification Configuration', slug: 'notification-configuration' }" class="space-y-6">

    @include('livewire.admin.notifications.partials.tabs')

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-900">Notification Events</h2>
            <p class="text-xs text-gray-400">Choose which channels fire for each system event, and which template each channel uses</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left px-6 py-2 font-semibold text-gray-600 text-xs uppercase tracking-wide">Event</th>
                        <th class="text-center px-3 py-2 font-semibold text-gray-600 text-xs uppercase tracking-wide">Email</th>
                        <th class="text-center px-3 py-2 font-semibold text-gray-600 text-xs uppercase tracking-wide">SMS</th>
                        <th class="text-center px-3 py-2 font-semibold text-gray-600 text-xs uppercase tracking-wide">Push</th>
                        <th class="text-center px-3 py-2 font-semibold text-gray-600 text-xs uppercase tracking-wide">Browser</th>
                        <th class="text-center px-3 py-2 font-semibold text-gray-600 text-xs uppercase tracking-wide">Database</th>
                        <th class="text-left px-6 py-2 font-semibold text-gray-600 text-xs uppercase tracking-wide">Email Template</th>
                        <th class="text-left px-6 py-2 font-semibold text-gray-600 text-xs uppercase tracking-wide">SMS Template</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($events as $event)
                        <tr>
                            <td class="px-6 py-3 text-gray-800 font-medium whitespace-nowrap">
                                {{ $event->label }}
                                <span class="block text-xs text-gray-400 font-mono font-normal">{{ $event->event_key }}</span>
                            </td>
                            @foreach (['email', 'sms', 'push', 'browser', 'database'] as $channel)
                                <td class="px-3 py-3 text-center">
                                    <input type="checkbox" wire:click="toggleChannel({{ $event->id }}, '{{ $channel }}')"
                                        {{ $event->{"channel_{$channel}"} ? 'checked' : '' }}
                                        class="rounded border-gray-300">
                                </td>
                            @endforeach
                            <td class="px-6 py-3">
                                <select wire:change="updateTemplate({{ $event->id }}, 'email_template_key', $event.target.value)"
                                    class="rounded-lg border border-gray-300 px-2 py-1.5 text-xs focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                    <option value="">None</option>
                                    @foreach ($emailTemplateKeys as $key)
                                        <option value="{{ $key }}" {{ $event->email_template_key === $key ? 'selected' : '' }}>{{ $key }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-6 py-3">
                                <select wire:change="updateTemplate({{ $event->id }}, 'sms_template_key', $event.target.value)"
                                    class="rounded-lg border border-gray-300 px-2 py-1.5 text-xs focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                    <option value="">None</option>
                                    @foreach ($smsTemplateKeys as $key)
                                        <option value="{{ $key }}" {{ $event->sms_template_key === $key ? 'selected' : '' }}>{{ $key }}</option>
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
