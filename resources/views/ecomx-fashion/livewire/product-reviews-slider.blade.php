<div x-data="{ review: @entangle('showForm'), rating: @entangle('rating') }">
    <section class="section reveal" aria-label="Reviews" style="margin-top:clamp(56px,8vw,96px)">
        <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:16px;margin-bottom:20px;flex-wrap:wrap">
            <div>
                <p class="kicker">
                    @if($ownAverageRating !== null)
                        ★ {{ number_format($ownAverageRating, 1) }} · {{ number_format($ownReviewCount) }} {{ $ownReviewCount === 1 ? 'review' : 'reviews' }}
                    @else
                        Be the first to review this product
                    @endif
                </p>
                <h2 class="h-section">Loved by our customers</h2>
            </div>
            <button class="btn btn--primary btn--pill" style="padding:11px 20px" wire:click="openForm">✎ Write a review</button>
        </div>
        <x-review-slider :reviews="$reviews" :enable-helpful="true" />
    </section>

    {{-- Write review modal --}}
    <template x-if="review">
        <div class="modal" @click.self="$wire.closeForm()">
            <div class="modal__box modal__box--md">
                <div class="modal__head"><p class="modal__title">Write a review</p><button class="modal__close" @click="$wire.closeForm()">✕</button></div>

                @if(!$submitted)
                    <form wire:submit.prevent="submitReview">
                        <p class="muted" style="font-size:13px;margin-bottom:20px">Your honest thoughts help others</p>

                        <div class="field">
                            <label>Your name <span style="color:#e11d48">*</span></label>
                            <input type="text" wire:model="reviewerName" placeholder="Your name" required
                                style="width:100%;padding:12px 14px;border:1px solid rgba(var(--pri-rgb),.15);border-radius:10px;background:#fff;font-size:13px">
                            @error('reviewerName') <p style="color:#e11d48;font-size:12px;margin-top:4px">{{ $message }}</p> @enderror
                        </div>

                        <div class="field">
                            <label>Your rating <span style="color:#e11d48">*</span></label>
                            <div style="display:flex;gap:6px">
                                <template x-for="n in 5" :key="n">
                                    <button type="button" @click="rating=n; $wire.setRating(n)" style="border:none;background:none;font-size:30px;line-height:1;padding:0" :style="'color:'+(n<=rating?'var(--ac)':'rgba(var(--pri-rgb),.2)')">★</button>
                                </template>
                            </div>
                            @error('rating') <p style="color:#e11d48;font-size:12px;margin-top:4px">{{ $message }}</p> @enderror
                        </div>

                        <div class="field">
                            <label>Your review <span style="color:#e11d48">*</span></label>
                            <textarea rows="4" wire:model="comment" placeholder="How's the fit, fabric, delivery?" required
                                style="width:100%;padding:12px 14px;border:1px solid rgba(var(--pri-rgb),.15);border-radius:10px;background:#fff;font-size:13px;resize:vertical"></textarea>
                            @error('comment') <p style="color:#e11d48;font-size:12px;margin-top:4px">{{ $message }}</p> @enderror
                        </div>

                        <div class="field">
                            <label>
                                Add photos
                                @if($requireImageStorefront)
                                    <span style="color:#e11d48">*</span> <span class="muted" style="font-weight:400">(at least 1 required)</span>
                                @else
                                    <span class="muted" style="font-weight:400">(optional)</span>
                                @endif
                            </label>

                            <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:10px" wire:key="photo-previews">
                                @foreach($photos as $index => $photo)
                                    <div style="position:relative;width:72px;height:72px;border-radius:10px;overflow:hidden;border:1px solid rgba(var(--pri-rgb),.15)" wire:key="photo-{{ $index }}">
                                        <img src="{{ $photo->temporaryUrl() }}" alt="" style="width:100%;height:100%;object-fit:cover">
                                        <button type="button" wire:click="removePhoto({{ $index }})"
                                            style="position:absolute;top:2px;right:2px;width:20px;height:20px;border-radius:999px;background:rgba(0,0,0,.6);color:#fff;border:none;font-size:12px;line-height:1;cursor:pointer">✕</button>
                                    </div>
                                @endforeach

                                <label style="width:72px;height:72px;border-radius:10px;border:1px dashed rgba(var(--pri-rgb),.3);display:flex;align-items:center;justify-content:center;cursor:pointer;color:rgba(var(--pri-rgb),.4);font-size:24px">
                                    +
                                    <input type="file" wire:model="photos" multiple accept="image/*" style="display:none">
                                </label>
                            </div>

                            <div wire:loading wire:target="photos" style="font-size:12px;color:rgba(var(--pri-rgb),.5)">Uploading…</div>
                            @error('photos') <p style="color:#e11d48;font-size:12px;margin-top:4px">{{ $message }}</p> @enderror
                            @error('photos.*') <p style="color:#e11d48;font-size:12px;margin-top:4px">{{ $message }}</p> @enderror
                        </div>

                        <button type="submit" class="btn btn--primary btn--block" wire:loading.attr="disabled" wire:target="submitReview,photos">
                            <span wire:loading.remove wire:target="submitReview">Submit review</span>
                            <span wire:loading wire:target="submitReview">Submitting…</span>
                        </button>
                    </form>
                @else
                    <div style="text-align:center;padding:20px 0 8px">
                        <div style="width:60px;height:60px;border-radius:999px;background:var(--ac);color:#fff;font-size:28px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px">✓</div>
                        <p style="font-family:'Playfair Display',serif;font-size:20px;margin-bottom:6px">Thank you!</p>
                        <p class="muted" style="font-size:13px">Your review has been submitted for verification.</p>
                    </div>
                @endif
            </div>
        </div>
    </template>
</div>
