@php
    $siteName = \App\Models\Setting::get('site_name', 'AnyNiche') ?: 'AnyNiche';
    $trust = config('ecomx-anyniche.trust', []);
@endphp

<div class="jtc-about">
    <div class="jtc-about__hero">
        <nav class="jtc-shop__crumb" style="margin-bottom:14px">
            <a href="{{ route('ecomx-anyniche.home') }}" style="color:inherit;text-decoration:none">Home</a>
            <span>/</span>
            <span style="color:#14201c;font-weight:600">About us</span>
        </nav>
        <h1>Bangladesh's online store, built for how you actually shop</h1>
        <p>{{ $siteName }} brings quality products to your doorstep — from Dhaka to every district — backed by cash on delivery, verified reviews, and a support team that actually picks up the phone.</p>
    </div>

    @if(count($trust))
        <div class="jtc-about__stats">
            @foreach($trust as $stat)
                <div class="jtc-about__stat">
                    <div class="jtc-about__stat-val">{{ $stat['val'] }}</div>
                    <div class="jtc-about__stat-label">{{ $stat['label'] }}</div>
                </div>
            @endforeach
        </div>
    @endif

    <div class="jtc-about__section">
        <h2>Our story</h2>
        <p>
            {{ $siteName }} started with a simple frustration shared by online shoppers across Bangladesh: too many stores promise fast delivery and genuine products, but too few actually deliver on it.
            We set out to fix that — sourcing directly, checking every order before it ships, and keeping cash on delivery available everywhere we operate, because trust has to be earned before it's asked for.
        </p>
        <p>
            Today we serve customers in every division of the country, from Dhaka and Chattogram to smaller upazilas that larger platforms often skip. Every order — big or small — gets the same care.
        </p>
    </div>

    <div class="jtc-about__grid">
        <div class="jtc-about__card">
            <h3>Nationwide delivery</h3>
            <p>We partner with trusted local couriers to reach all 64 districts, with reliable 24–72 hour delivery inside major cities and a clear estimate for everywhere else.</p>
        </div>
        <div class="jtc-about__card">
            <h3>Cash on delivery</h3>
            <p>Pay when it arrives at your door — no upfront risk. We also accept bKash, Nagad, and card payments for customers who prefer to pay online.</p>
        </div>
        <div class="jtc-about__card">
            <h3>Genuine products, real reviews</h3>
            <p>Every listing is checked before it goes live, and every review on our site comes from a verified purchase — no fake ratings, no surprises.</p>
        </div>
        <div class="jtc-about__card">
            <h3>Support that answers</h3>
            <p>Questions about an order, a return, or a product? Our support team is reachable by phone and live chat, every day — not a bot that loops you in circles.</p>
        </div>
    </div>

    <div class="jtc-about__cta">
        <div>
            <h2>Still have a question?</h2>
            <p>Our team is happy to help before or after you order.</p>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
            <a href="{{ route('ecomx-anyniche.track') }}" class="jtc-btn jtc-btn--outline">Track an order</a>
            <a href="tel:{{ config('ecomx-anyniche.phone') }}" class="jtc-btn jtc-btn--primary">Call us</a>
        </div>
    </div>
</div>
