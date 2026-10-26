{{-- ======================== Page Layout Start From Here ======================== --}}
<div x-data x-init="$store.pageName = { name: 'My Profile', slug: 'profile' }">
    <div class="flex-1 w-full bg-white rounded-lg min-h-[80vh] p-4 sm:p-6">
        {{-- ======================== Content Start From Here ======================== --}}
        <div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- ======================== Left column ======================== --}}
            <div class="space-y-6">

                {{-- Profile Overview --}}
                <div class="bg-gray-50 rounded-lg p-6 text-center">
                    @php $savedAvatar = $user->avatar_id ? file_path($user->avatar_id, 'thumbnail') : null; @endphp
                    <img src="{{ $savedAvatar ?? $user->initialsAvatarUrl() }}"
                        onerror="this.onerror=null;this.src='{{ $user->initialsAvatarUrl() }}'"
                        alt="{{ $user->name }}"
                        class="w-24 h-24 mx-auto rounded-full object-cover border-4 border-white shadow">

                    <h3 class="mt-3 text-xl font-bold text-gray-900 break-words">{{ $user->name }}</h3>
                    <p class="text-sm text-gray-600 break-all">{{ $user->email }}</p>
                    @if($user->phone)
                        <p class="text-sm text-gray-600">{{ $user->country_code }} {{ $user->phone }}</p>
                    @endif

                    @if($user->roles->count() > 0)
                        <div class="mt-3 flex flex-wrap justify-center gap-2">
                            @foreach($user->roles as $role)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ $role->name }}
                                </span>
                            @endforeach
                        </div>
                    @endif

                    @if($user->bio)
                        <p class="mt-4 text-sm text-gray-600 whitespace-pre-line text-left">{{ $user->bio }}</p>
                    @endif
                </div>

                {{-- Account & Security --}}
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-base font-semibold text-gray-900">Account & Security</h2>
                        <a href="{{ route('admin.settings') }}" wire:navigate
                            class="text-xs font-medium text-indigo-600 hover:text-indigo-800">Security settings →</a>
                    </div>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-600">Email</dt>
                            <dd>
                                @if($user->email_verified_at)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Verified</span>
                                @else
                                    <a href="{{ route('admin.settings') }}" wire:navigate class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 hover:bg-red-200">Unverified — verify</a>
                                @endif
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-600">Phone</dt>
                            <dd>
                                @if($user->phone_verified_at)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Verified</span>
                                @else
                                    @if($user->phone)
                                    <a href="{{ route('admin.settings') }}" wire:navigate class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 hover:bg-gray-200">Not verified — verify</a>
                                @else
                                    <span class="text-xs text-gray-500">Not set</span>
                                @endif
                                @endif
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-600">Two-factor auth</dt>
                            <dd>
                                @if($user->two_factor_confirmed_at)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Enabled</span>
                                @else
                                    <a href="{{ route('admin.settings') }}" wire:navigate
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 hover:bg-amber-200">Off — enable</a>
                                @endif
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-600">Password changed</dt>
                            <dd class="text-gray-900">{{ local_time($user->password_set_at)?->format('M d, Y') ?? '—' }}</dd>
                        </div>
                        @if($user->panels->count() > 0)
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-600 shrink-0">Panels</dt>
                                <dd class="flex flex-wrap justify-end gap-1">
                                    @foreach($user->panels as $panel)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">{{ $panel->name }}</span>
                                    @endforeach
                                </dd>
                            </div>
                        @endif
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-600">Member since</dt>
                            <dd class="text-gray-900">{{ local_time($user->created_at)?->format('M d, Y') ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-600">Last updated</dt>
                            <dd class="text-gray-900">{{ local_time($user->updated_at)?->format('M d, Y') ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- ======================== Right column ======================== --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Edit Profile Form --}}
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-semibold text-gray-900">Edit Profile</h2>
                        <span wire:dirty class="text-xs font-medium text-amber-600">Unsaved changes</span>
                    </div>
                    <form wire:submit.prevent="updateProfile" class="space-y-6">

                        {{-- Avatar --}}
                        <div class="max-w-xs">
                            <x-media-picker-field field="avatar_id" :value="$avatar_id" label="Profile Photo" type="image" placeholder="Select photo" />
                            @error('avatar_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {{-- Name --}}
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">Name <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="name" id="name" autocomplete="name"
                                    class="mt-1 block w-full p-2 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            {{-- Gender --}}
                            <div>
                                <label for="gender" class="block text-sm font-medium text-gray-700">Gender</label>
                                <select wire:model="gender" id="gender"
                                    class="mt-1 block w-full p-2 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="">Prefer not to say</option>
                                    @foreach($genders as $g)
                                        <option value="{{ $g->slug }}">{{ $g->name }}</option>
                                    @endforeach
                                </select>
                                @error('gender') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        {{-- Email & Phone are changed under Security Settings --}}
                        <div class="rounded-md border border-gray-200 bg-gray-50 p-4">
                            <div class="flex items-center justify-between gap-4 mb-3">
                                <p class="text-sm font-medium text-gray-700">Email &amp; Phone</p>
                                <a href="{{ route('admin.settings') }}" wire:navigate
                                    class="text-xs font-medium text-indigo-600 hover:text-indigo-800">Change in Security settings →</a>
                            </div>
                            <dl class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                                <div class="min-w-0">
                                    <dt class="text-gray-500">Email</dt>
                                    <dd class="text-gray-900 break-all">{{ $user->email }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">Phone</dt>
                                    <dd class="text-gray-900">{{ $user->phone ? trim($user->country_code . ' ' . $user->phone) : 'Not set' }}</dd>
                                </div>
                            </dl>
                        </div>

                        {{-- Address --}}
                        <div>
                            <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
                            <textarea wire:model="address" id="address" rows="2" autocomplete="street-address"
                                class="mt-1 block w-full p-2 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                            @error('address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        {{-- Bio --}}
                        <div x-data="{ len: ($wire.bio ?? '').length }">
                            <div class="flex items-center justify-between">
                                <label for="bio" class="block text-sm font-medium text-gray-700">Bio</label>
                                <span class="text-xs text-gray-400" :class="len > 1000 && 'text-red-600'" x-text="len + ' / 1000'"></span>
                            </div>
                            <textarea wire:model="bio" id="bio" rows="4" x-on:input="len = $el.value.length"
                                class="mt-1 block w-full p-2 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                            @error('bio') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex justify-end gap-3">
                            <button type="button" wire:click="resetForm" wire:dirty
                                class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Discard
                            </button>
                            <button type="submit" wire:loading.attr="disabled" wire:target="updateProfile"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-60">
                                <span wire:loading.remove wire:target="updateProfile">Update Profile</span>
                                <span wire:loading wire:target="updateProfile">Saving…</span>
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Recent Activity --}}
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-base font-semibold text-gray-900">My Recent Activity</h2>
                        @can('activity_log.view')
                            <a href="{{ route('admin.activity-log') }}" wire:navigate
                                class="text-xs font-medium text-indigo-600 hover:text-indigo-800">Full log →</a>
                        @endcan
                    </div>
                    @forelse($recentActivities as $activity)
                        @php $ua = \App\Models\ActivityLog::parseUserAgent($activity->user_agent); @endphp
                        <div wire:key="activity-{{ $activity->id }}" class="flex items-start justify-between gap-4 py-3 border-t border-gray-100 first:border-t-0 first:pt-0">
                            <div class="min-w-0">
                                <p class="text-sm text-gray-900 break-words">
                                    @if($activity->event)
                                        <span class="inline-flex items-center px-1.5 py-0.5 mr-1 rounded text-[10px] font-semibold uppercase bg-gray-100 text-gray-700">{{ $activity->event }}</span>
                                    @endif
                                    {{ $activity->description }}
                                    @if($activity->subject_type)
                                        <span class="text-gray-500">· {{ class_basename($activity->subject_type) }} #{{ $activity->subject_id }}</span>
                                    @endif
                                </p>
                                @if($activity->ip_address || $ua['browser'])
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        {{ collect([$activity->ip_address, $ua['browser'] ? $ua['browser'] . ' on ' . $ua['os'] : null])->filter()->implode(' · ') }}
                                    </p>
                                @endif
                            </div>
                            <time class="text-xs text-gray-500 whitespace-nowrap" title="{{ local_time($activity->created_at)?->format('M d, Y h:i A') }}">
                                {{ $activity->created_at?->diffForHumans() ?? '—' }}
                            </time>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No activity recorded yet.</p>
                    @endforelse
                </div>
            </div>

        </div>
        {{-- ======================== Content End Here ======================== --}}
    </div>
</div>
