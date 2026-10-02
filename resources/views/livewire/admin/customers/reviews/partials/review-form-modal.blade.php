{{-- Add / Edit review modal (HandlesReviewForm). No @click.outside on purpose: the media
     picker renders as a sibling component at the end of <body>, so clicks on it would
     count as "outside" and close this modal before the picked file arrives. --}}
@if($formModal)
<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" role="dialog" wire:key="review-form-{{ $editingReviewId ?? 'new' }}">
    <div class="w-full max-w-2xl bg-white rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
        <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 shrink-0">
            <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    @if($editingReviewId)
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/>
                    @else
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    @endif
                </svg>
            </div>
            <div class="flex-1">
                <h2 class="text-base font-semibold text-gray-900">{{ $editingReviewId ? "Edit Review #{$editingReviewId}" : 'Add Review' }}</h2>
                <p class="text-xs text-gray-400">
                    {{ $editingReviewId ? 'Changes apply immediately; the review keeps its current status.' : 'Added reviews still require verification before they appear on the storefront.' }}
                </p>
            </div>
            <button wire:click="closeReviewForm" type="button" class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form wire:submit.prevent="saveReview" class="overflow-y-auto px-6 py-5 space-y-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">Product <span class="text-red-500">*</span></label>
                <x-searchable-select wire:key="review-product-{{ $editingReviewId ?? 'new' }}" field="formProductId" :value="$formProductId"
                    :options="$productOptions" :images="$productImages" placeholder="— Select a product —" search-placeholder="Search products…" />
                @error('formProductId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Existing Customer (optional)</label>
                    <x-searchable-select wire:key="review-customer-{{ $editingReviewId ?? 'new' }}" field="formCustomerId" :value="$formCustomerId"
                        :options="$customerOptions" placeholder="— None (use name) —" search-placeholder="Search customers…" />
                    @error('formCustomerId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Reviewer Name @if(!$formCustomerId)<span class="text-red-500">*</span>@endif</label>
                    <input wire:model="formAuthorName" type="text" placeholder="e.g. Rahim Uddin"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    @error('formAuthorName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Rating <span class="text-red-500">*</span></label>
                    <select wire:model="formRating" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        <option value="">— Select —</option>
                        @foreach ([5, 4, 3, 2, 1] as $r)
                            <option value="{{ $r }}">{{ $r }} Star</option>
                        @endforeach
                    </select>
                    @error('formRating') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Source <span class="text-red-500">*</span></label>
                    <select wire:model="formSource" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        <option value="admin">Admin</option>
                        <option value="website">Website</option>
                        <option value="facebook">Facebook</option>
                        <option value="whatsapp">WhatsApp</option>
                        <option value="phone">Phone</option>
                        <option value="import">Import</option>
                    </select>
                    @error('formSource') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">Title (optional)</label>
                <input wire:model="formTitle" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                @error('formTitle') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">Comment <span class="text-red-500">*</span></label>
                <textarea wire:model="formComment" rows="4" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"></textarea>
                @error('formComment') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-media-picker-field field="formImageIds" :value="$formImageIds" type="image" :multiple="true"
                        label="Photos" :required="$requireImageAdmin && $formAuthorType === 'admin'" placeholder="Upload or choose photos" />
                    @error('formImageIds.*') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <x-media-picker-field field="formVideoIds" :value="$formVideoIds" type="video" :multiple="true"
                        label="Videos (optional)" placeholder="Upload or choose videos" />
                    @error('formVideoIds.*') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <p class="text-xs text-gray-400 -mt-2">Up to 5 photos and 5 videos. Use the picker's Upload tab to add new files.</p>

            <label class="flex items-center gap-2.5 cursor-pointer">
                <input type="checkbox" wire:model="formVerifiedPurchase" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <span class="text-sm text-gray-700">Mark as verified purchase</span>
            </label>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                <button wire:click="closeReviewForm" type="button" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                <button type="submit" wire:loading.attr="disabled" wire:target="saveReview"
                    class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition disabled:opacity-50">
                    {{ $editingReviewId ? 'Save Changes' : 'Add Review' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endif
