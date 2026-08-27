<div x-data x-init="$store.pageName = { name: 'Review Settings', slug: 'customers-reviews-settings' }">

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.customers.reviews.index') }}"
            class="w-9 h-9 inline-flex items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
            </svg>
        </a>
        <h1 class="text-lg font-semibold text-gray-900">Review Settings</h1>
    </div>

    <form wire:submit.prevent="save" class="space-y-6 max-w-3xl">

        {{-- Submission --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-sm font-semibold text-gray-800 mb-4">Review Submission</h2>
            <div class="space-y-4">
                @php
                    $toggles = [
                        ['enabled', 'Reviews Enabled', 'Turn the whole review system on or off'],
                        ['allowCustomerReviews', 'Allow Customer Reviews', 'Customers can submit reviews from the storefront'],
                        ['allowAdminReviews', 'Allow Admin Reviews', 'Admins can add reviews on behalf of customers'],
                        ['requirePurchase', 'Require Purchase', 'Only customers who purchased the product can review it'],
                        ['requireDeliveredOrder', 'Require Delivered Order', 'Only allow reviews after the order is marked delivered'],
                    ];
                @endphp
                @foreach($toggles as [$prop, $label, $desc])
                    <label class="flex items-center justify-between gap-4 cursor-pointer">
                        <span>
                            <span class="block text-sm font-medium text-gray-700">{{ $label }}</span>
                            <span class="block text-xs text-gray-400">{{ $desc }}</span>
                        </span>
                        <span class="relative inline-flex h-5 w-9 items-center shrink-0">
                            <input type="checkbox" wire:model="{{ $prop }}" class="peer sr-only">
                            <span class="absolute inset-0 rounded-full bg-gray-300 peer-checked:bg-indigo-500 transition-colors"></span>
                            <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></span>
                        </span>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Verification --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-sm font-semibold text-gray-800 mb-4">Verification</h2>
            <div class="space-y-4">
                <label class="flex items-center justify-between gap-4 cursor-pointer">
                    <span>
                        <span class="block text-sm font-medium text-gray-700">Auto Approve</span>
                        <span class="block text-xs text-gray-400">Skip moderation and publish reviews immediately (not recommended)</span>
                    </span>
                    <span class="relative inline-flex h-5 w-9 items-center shrink-0">
                        <input type="checkbox" wire:model="autoApprove" class="peer sr-only">
                        <span class="absolute inset-0 rounded-full bg-gray-300 peer-checked:bg-indigo-500 transition-colors"></span>
                        <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></span>
                    </span>
                </label>
                <label class="flex items-center justify-between gap-4 cursor-pointer">
                    <span>
                        <span class="block text-sm font-medium text-gray-700">Prevent Self-Approval</span>
                        <span class="block text-xs text-gray-400">An admin cannot approve/reject a review or reply they created themselves</span>
                    </span>
                    <span class="relative inline-flex h-5 w-9 items-center shrink-0">
                        <input type="checkbox" wire:model="preventSelfApproval" class="peer sr-only">
                        <span class="absolute inset-0 rounded-full bg-gray-300 peer-checked:bg-indigo-500 transition-colors"></span>
                        <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></span>
                    </span>
                </label>
            </div>
        </div>

        {{-- Media --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-sm font-semibold text-gray-800 mb-4">Images &amp; Videos</h2>
            <div class="space-y-4">
                <label class="flex items-center justify-between gap-4 cursor-pointer">
                    <span class="text-sm font-medium text-gray-700">Allow Images</span>
                    <span class="relative inline-flex h-5 w-9 items-center shrink-0">
                        <input type="checkbox" wire:model="allowImages" class="peer sr-only">
                        <span class="absolute inset-0 rounded-full bg-gray-300 peer-checked:bg-indigo-500 transition-colors"></span>
                        <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></span>
                    </span>
                </label>
                <label class="flex items-center justify-between gap-4 cursor-pointer">
                    <span class="text-sm font-medium text-gray-700">Allow Videos</span>
                    <span class="relative inline-flex h-5 w-9 items-center shrink-0">
                        <input type="checkbox" wire:model="allowVideos" class="peer sr-only">
                        <span class="absolute inset-0 rounded-full bg-gray-300 peer-checked:bg-indigo-500 transition-colors"></span>
                        <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></span>
                    </span>
                </label>
                <div class="grid grid-cols-2 gap-4 pt-2">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Max Images per Review</label>
                        <input wire:model="maxImages" type="number" min="0" max="20"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('maxImages') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Max Videos per Review</label>
                        <input wire:model="maxVideos" type="number" min="0" max="5"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('maxVideos') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="pt-2 border-t border-gray-100 space-y-4">
                    <label class="flex items-center justify-between gap-4 cursor-pointer">
                        <span>
                            <span class="block text-sm font-medium text-gray-700">Require Photo — Storefront</span>
                            <span class="block text-xs text-gray-400">Customers must attach at least 1 photo when writing a review on the product page</span>
                        </span>
                        <span class="relative inline-flex h-5 w-9 items-center shrink-0">
                            <input type="checkbox" wire:model="requireImageStorefront" class="peer sr-only">
                            <span class="absolute inset-0 rounded-full bg-gray-300 peer-checked:bg-indigo-500 transition-colors"></span>
                            <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></span>
                        </span>
                    </label>
                    <label class="flex items-center justify-between gap-4 cursor-pointer">
                        <span>
                            <span class="block text-sm font-medium text-gray-700">Require Photo — Admin</span>
                            <span class="block text-xs text-gray-400">Admins must attach at least 1 photo when adding a review from Customers → Reviews</span>
                        </span>
                        <span class="relative inline-flex h-5 w-9 items-center shrink-0">
                            <input type="checkbox" wire:model="requireImageAdmin" class="peer sr-only">
                            <span class="absolute inset-0 rounded-full bg-gray-300 peer-checked:bg-indigo-500 transition-colors"></span>
                            <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></span>
                        </span>
                    </label>
                </div>
            </div>
        </div>

        {{-- Replies --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-sm font-semibold text-gray-800 mb-4">Replies</h2>
            <div class="space-y-4">
                <label class="flex items-center justify-between gap-4 cursor-pointer">
                    <span class="text-sm font-medium text-gray-700">Allow Replies</span>
                    <span class="relative inline-flex h-5 w-9 items-center shrink-0">
                        <input type="checkbox" wire:model="allowReplies" class="peer sr-only">
                        <span class="absolute inset-0 rounded-full bg-gray-300 peer-checked:bg-indigo-500 transition-colors"></span>
                        <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></span>
                    </span>
                </label>
                <div class="pt-2 max-w-xs">
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Max Reply Depth</label>
                    <input wire:model="maxReplyDepth" type="number" min="1" max="5"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    @error('maxReplyDepth') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Display --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-sm font-semibold text-gray-800 mb-4">Display Settings</h2>
            <div class="space-y-4">
                <label class="flex items-center justify-between gap-4 cursor-pointer">
                    <span>
                        <span class="block text-sm font-medium text-gray-700">Helpful Votes</span>
                        <span class="block text-xs text-gray-400">Let customers mark reviews as helpful / not helpful</span>
                    </span>
                    <span class="relative inline-flex h-5 w-9 items-center shrink-0">
                        <input type="checkbox" wire:model="helpfulVoteEnabled" class="peer sr-only">
                        <span class="absolute inset-0 rounded-full bg-gray-300 peer-checked:bg-indigo-500 transition-colors"></span>
                        <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></span>
                    </span>
                </label>
                <div class="pt-2 max-w-xs">
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Product Page Review Limit</label>
                    <input wire:model="productPageReviewLimit" type="number" min="1" max="100"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    <p class="text-xs text-gray-400 mt-1.5">Maximum reviews shown on a product page. Own-product reviews are shown first; remaining slots fill with top reviews from other products.</p>
                    @error('productPageReviewLimit') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="px-6 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm">
                Save Settings
            </button>
        </div>
    </form>

</div>
