<div x-data x-init="$store.pageName = { name: 'Review Details', slug: 'customers-reviews-show' }">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.customers.reviews.index') }}"
                class="w-9 h-9 inline-flex items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
            </a>
            <h1 class="text-lg font-semibold text-gray-900">Review #{{ $review->id }}</h1>
            @php
                $statusStyles = [
                    'pending'  => 'bg-amber-50 text-amber-600',
                    'approved' => 'bg-emerald-50 text-emerald-600',
                    'rejected' => 'bg-red-50 text-red-500',
                    'hidden'   => 'bg-gray-100 text-gray-500',
                ];
            @endphp
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium capitalize {{ $statusStyles[$review->status] ?? 'bg-gray-100 text-gray-500' }}">
                {{ $review->status }}
            </span>
        </div>
        <div class="flex items-center gap-2">
            @if($review->status !== 'approved')
                <button wire:click="approve" type="button"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition">
                    Approve
                </button>
            @endif
            @if($review->status !== 'rejected')
                <button wire:click="openReasonModal('review', 'reject')" type="button"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 text-sm font-medium text-red-600 bg-white border border-red-200 rounded-lg hover:bg-red-50 transition">
                    Reject
                </button>
            @endif
            @if($review->status !== 'hidden')
                <button wire:click="openReasonModal('review', 'hide')" type="button"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    Hide
                </button>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-3 gap-6">
        {{-- Main column --}}
        <div class="col-span-2 space-y-6">

            {{-- Review content --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-start justify-between gap-4 mb-4">
                    <div>
                        <p class="text-sm font-semibold text-gray-800">{{ $review->authorName() }}</p>
                        <p class="text-xs text-gray-400 capitalize">{{ $review->author_type }} · via {{ $review->source }}</p>
                    </div>
                    <span class="inline-flex items-center gap-1 text-base font-medium text-amber-500">
                        {{ $review->rating }} <span class="text-amber-400">★</span>
                    </span>
                </div>
                @if($review->title)
                    <p class="text-sm font-semibold text-gray-800 mb-1">{{ $review->title }}</p>
                @endif
                <p class="text-sm text-gray-600 leading-relaxed">{{ $review->comment }}</p>

                @if($review->media->isNotEmpty())
                    <div class="grid grid-cols-4 gap-3 mt-4">
                        @foreach($review->media as $media)
                            <div class="aspect-square rounded-lg bg-gray-100 overflow-hidden relative">
                                @if($media->isVideo())
                                    <video src="{{ $media->getUrl() }}" class="w-full h-full object-cover" controls></video>
                                @else
                                    <img src="{{ $media->getUrl() }}" alt="" class="w-full h-full object-cover">
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                @if($review->is_verified_purchase)
                    <span class="inline-flex items-center gap-1 mt-4 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-600">
                        Verified Purchase
                    </span>
                @endif
            </div>

            {{-- Replies --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-sm font-semibold text-gray-800 mb-4">Replies ({{ $review->replies->count() }})</h2>

                <div class="space-y-4">
                    @forelse($review->replies as $reply)
                        <div class="flex items-start justify-between gap-4 border border-gray-100 rounded-xl p-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-sm font-medium text-gray-800">
                                        {{ $reply->author_type === 'admin' ? ($reply->createdBy?->name ?? 'Admin') : ($reply->customer?->full_name ?? 'Customer') }}
                                    </span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium capitalize {{ $statusStyles[$reply->status] ?? 'bg-gray-100 text-gray-500' }}">
                                        {{ $reply->status }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600">{{ $reply->comment }}</p>
                            </div>
                            <div class="flex items-center gap-1 shrink-0">
                                @if($reply->status !== 'approved')
                                    <button wire:click="approveReply({{ $reply->id }})" type="button" title="Approve"
                                        class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-gray-400 hover:bg-emerald-50 hover:text-emerald-600 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                                        </svg>
                                    </button>
                                @endif
                                @if($reply->status !== 'rejected')
                                    <button wire:click="openReasonModal('reply', 'reject', {{ $reply->id }})" type="button" title="Reject"
                                        class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-500 transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">No replies yet.</p>
                    @endforelse
                </div>

                <form wire:submit.prevent="submitReply" class="mt-4 pt-4 border-t border-gray-100">
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Add Admin Reply</label>
                    <textarea wire:model="replyComment" rows="3" placeholder="Write a reply…"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"></textarea>
                    @error('replyComment') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    <div class="flex justify-end mt-2">
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
                            Post Reply
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
                <h2 class="text-sm font-semibold text-gray-800 mb-3">Review Information</h2>
                <dl class="space-y-2.5 text-sm">
                    <div class="flex justify-between gap-2">
                        <dt class="text-gray-400">Product</dt>
                        <dd class="text-gray-700 text-right">{{ $review->product->name ?? '—' }}</dd>
                    </div>
                    @if($review->variant)
                        <div class="flex justify-between gap-2">
                            <dt class="text-gray-400">Variant</dt>
                            <dd class="text-gray-700 text-right">{{ $review->variant->name ?? '—' }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between gap-2">
                        <dt class="text-gray-400">Source</dt>
                        <dd class="text-gray-700 capitalize">{{ $review->source }}</dd>
                    </div>
                    <div class="flex justify-between gap-2">
                        <dt class="text-gray-400">Verified Purchase</dt>
                        <dd class="text-gray-700">{{ $review->is_verified_purchase ? 'Yes' : 'No' }}</dd>
                    </div>
                    <div class="flex justify-between gap-2">
                        <dt class="text-gray-400">Helpful Votes</dt>
                        <dd class="text-gray-700">{{ $review->helpfulVotes->count() }}</dd>
                    </div>
                    <div class="flex justify-between gap-2">
                        <dt class="text-gray-400">Submitted</dt>
                        <dd class="text-gray-700">{{ $review->created_at->format('M d, Y') }}</dd>
                    </div>
                </dl>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
                <h2 class="text-sm font-semibold text-gray-800 mb-3">Verification History</h2>
                <dl class="space-y-2.5 text-sm">
                    @if($review->verified_at)
                        <div>
                            <dt class="text-gray-400 text-xs">Approved</dt>
                            <dd class="text-gray-700">{{ $review->verifiedBy?->name ?? '—' }} · {{ $review->verified_at->format('M d, Y H:i') }}</dd>
                        </div>
                    @endif
                    @if($review->rejected_at)
                        <div>
                            <dt class="text-gray-400 text-xs">Rejected</dt>
                            <dd class="text-gray-700">{{ $review->rejectedBy?->name ?? '—' }} · {{ $review->rejected_at->format('M d, Y H:i') }}</dd>
                            @if($review->rejection_reason)
                                <dd class="text-xs text-gray-400 mt-0.5">{{ $review->rejection_reason }}</dd>
                            @endif
                        </div>
                    @endif
                    @if($review->hidden_at)
                        <div>
                            <dt class="text-gray-400 text-xs">Hidden</dt>
                            <dd class="text-gray-700">{{ $review->hiddenBy?->name ?? '—' }} · {{ $review->hidden_at->format('M d, Y H:i') }}</dd>
                            @if($review->hidden_reason)
                                <dd class="text-xs text-gray-400 mt-0.5">{{ $review->hidden_reason }}</dd>
                            @endif
                        </div>
                    @endif
                    @if(!$review->verified_at && !$review->rejected_at && !$review->hidden_at)
                        <p class="text-xs text-gray-400">No moderation action taken yet.</p>
                    @endif
                </dl>
            </div>
        </div>
    </div>

    {{-- Reason Modal (Reject / Hide) --}}
    <div x-cloak x-data="{ open: @entangle('reasonModal') }" x-show="open" x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" role="dialog">
        <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden" @click.outside="open = false">
            <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100">
                <div class="flex-1">
                    <h2 class="text-base font-semibold text-gray-900">
                        {{ $reasonAction === 'reject' ? 'Reject' : 'Hide' }} {{ $reasonTarget === 'review' ? 'Review' : 'Reply' }}
                    </h2>
                </div>
                <button @click="open = false" type="button" class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="px-6 py-5 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Reason (optional)</label>
                    <textarea wire:model="reason" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"></textarea>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                    <button @click="open = false" type="button" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                    <button wire:click="confirmReasonAction" type="button" class="px-5 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition">
                        Confirm
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
