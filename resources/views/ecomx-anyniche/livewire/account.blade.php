<div class="jtc-account">
    <nav class="jtc-account__nav">
        <a href="{{ route('ecomx-anyniche.account') }}" class="is-active" wire:navigate>Account</a>
        <a href="{{ route('ecomx-anyniche.track') }}" wire:navigate>Orders</a>
    </nav>

    <div class="jtc-account__main">
        <div class="jtc-account__head">
            <h1>My account</h1>
            <p>Manage your profile, password and saved addresses.</p>
        </div>

        @auth
            <div class="jtc-account-card">
                <div class="jtc-account-card__head">
                    <div>
                        <h2>Profile</h2>
                        <p>Your basic account information.</p>
                    </div>
                </div>

                <form class="jtc-account-form" wire:submit.prevent="updateProfile">
                    <label>Full name
                        <input type="text" wire:model="name">
                        @error('name') <span style="color:#c0392b;font-size:0.78rem;font-weight:500">{{ $message }}</span> @enderror
                    </label>
                    <label>Phone
                        <input type="tel" placeholder="01XXXXXXXXX" wire:model="phone">
                        @error('phone') <span style="color:#c0392b;font-size:0.78rem;font-weight:500">{{ $message }}</span> @enderror
                    </label>
                    <label class="jtc-account-form__full">Email
                        <input type="email" wire:model="email">
                        @error('email') <span style="color:#c0392b;font-size:0.78rem;font-weight:500">{{ $message }}</span> @enderror
                    </label>

                    <div class="jtc-account-form__actions">
                        <button type="submit" class="jtc-btn jtc-btn--primary" wire:loading.attr="disabled" wire:target="updateProfile">
                            <span wire:loading.remove wire:target="updateProfile">Save changes</span>
                            <span wire:loading wire:target="updateProfile">Saving…</span>
                        </button>
                    </div>
                </form>
            </div>

            <div class="jtc-account-card">
                <div class="jtc-account-card__head">
                    <div>
                        <h2>Password</h2>
                        <p>{{ $this->hasPassword() ? 'Change your account password.' : "You don't have a password set yet — add one so you can log in without an OTP." }}</p>
                    </div>
                </div>

                <form class="jtc-account-form" wire:submit.prevent="updatePassword">
                    @if($this->hasPassword())
                        <label class="jtc-account-form__full">Current password
                            <input type="password" wire:model="currentPassword" autocomplete="current-password">
                            @error('currentPassword') <span style="color:#c0392b;font-size:0.78rem;font-weight:500">{{ $message }}</span> @enderror
                        </label>
                    @endif
                    <label>New password
                        <input type="password" wire:model="newPassword" autocomplete="new-password">
                        @error('newPassword') <span style="color:#c0392b;font-size:0.78rem;font-weight:500">{{ $message }}</span> @enderror
                    </label>
                    <label>Confirm new password
                        <input type="password" wire:model="newPassword_confirmation" autocomplete="new-password">
                    </label>

                    <div class="jtc-account-form__actions">
                        <button type="submit" class="jtc-btn jtc-btn--primary" wire:loading.attr="disabled" wire:target="updatePassword">
                            <span wire:loading.remove wire:target="updatePassword">{{ $this->hasPassword() ? 'Update password' : 'Set password' }}</span>
                            <span wire:loading wire:target="updatePassword">Saving…</span>
                        </button>
                    </div>
                </form>
            </div>

            <div class="jtc-account-card">
                <div class="jtc-account-card__head">
                    <div>
                        <h2>Address book</h2>
                        <p>Saved delivery addresses.</p>
                    </div>
                    @unless($showAddressForm)
                        <button type="button" class="jtc-btn jtc-btn--primary" wire:click="addAddress">Add address</button>
                    @endunless
                </div>

                @if($showAddressForm)
                    <form class="jtc-account-form" wire:submit.prevent="saveAddress" style="margin-bottom:20px">
                        <label>Full name
                            <input type="text" wire:model="addressName">
                            @error('addressName') <span style="color:#c0392b;font-size:0.78rem;font-weight:500">{{ $message }}</span> @enderror
                        </label>
                        <label>Phone
                            <input type="tel" placeholder="01XXXXXXXXX" wire:model="addressPhone">
                            @error('addressPhone') <span style="color:#c0392b;font-size:0.78rem;font-weight:500">{{ $message }}</span> @enderror
                        </label>
                        <label class="jtc-account-form__full">Address
                            <input type="text" wire:model="addressLine">
                            @error('addressLine') <span style="color:#c0392b;font-size:0.78rem;font-weight:500">{{ $message }}</span> @enderror
                        </label>
                        <label>Label (optional)
                            <input type="text" placeholder="Home, Office…" wire:model="addressType">
                        </label>
                        <label>Area
                            <select wire:model="deliveryArea">
                                <option value="dhaka">Inside Dhaka</option>
                                <option value="other">Outside Dhaka</option>
                            </select>
                        </label>
                        <label class="jtc-account-form__full" style="flex-direction:row;align-items:center;gap:8px">
                            <input type="checkbox" wire:model="setAsDefault" style="width:auto">
                            Set as default address
                        </label>

                        <div class="jtc-account-form__actions">
                            <button type="submit" class="jtc-btn jtc-btn--primary" wire:loading.attr="disabled" wire:target="saveAddress">
                                <span wire:loading.remove wire:target="saveAddress">Save address</span>
                                <span wire:loading wire:target="saveAddress">Saving…</span>
                            </button>
                            <button type="button" class="jtc-btn" style="background:#fff;border:1.5px solid #e6eae7" wire:click="cancelAddressForm">Cancel</button>
                        </div>
                    </form>
                @endif

                @if($addresses->isEmpty())
                    <p class="jtc-account-empty">No saved addresses yet.</p>
                @else
                    <div class="jtc-address-grid">
                        @foreach($addresses as $address)
                            <div class="jtc-address-card @if($address->is_default_shipping) is-primary @endif">
                                @if($address->is_default_shipping)
                                    <span class="jtc-address-card__badge">Primary</span>
                                @endif
                                <div class="jtc-address-card__name">{{ $address->name }}</div>
                                <div class="jtc-address-card__phone">{{ $address->phone }}</div>
                                <div class="jtc-address-card__lines">
                                    {{ $address->full_address }}<br>
                                    {{ collect([$address->city?->name, $address->state?->name])->filter()->implode(', ') }}
                                </div>
                                <div class="jtc-address-card__actions">
                                    <button type="button" class="jtc-address-card__link" wire:click="editAddress({{ $address->id }})">Edit</button>
                                    @unless($address->is_default_shipping)
                                        <button type="button" class="jtc-address-card__link" wire:click="makePrimary({{ $address->id }})">Make primary</button>
                                    @endunless
                                    <button type="button" class="jtc-address-card__link jtc-address-card__link--danger"
                                            wire:click="deleteAddress({{ $address->id }})"
                                            wire:confirm="Remove this address?">Delete</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @else
            <div class="jtc-account-card jtc-account-card--muted" style="text-align:center">
                <h2>Sign in to view your account</h2>
                <p>Your profile, password and saved addresses live here once you're signed in.</p>
                <button type="button" class="jtc-btn jtc-btn--primary" @click="$store.ui.authOpen = true">Sign in with phone (OTP)</button>
            </div>
        @endauth
    </div>
</div>
