@php
    $siteName = \App\Models\Setting::get('site_name', 'AnyNiche') ?: 'AnyNiche';
    $companyPhone = config('ecomx-anyniche.phone');
    $sections = [
        'information-we-collect' => 'Information we collect',
        'how-we-use-it' => 'How we use your information',
        'cod-and-payments' => 'Cash on delivery & online payments',
        'sharing-with-third-parties' => 'Sharing with third parties',
        'cookies' => 'Cookies & similar technologies',
        'data-retention' => 'Data retention',
        'your-rights' => 'Your rights & choices',
        'childrens-privacy' => "Children's privacy",
        'data-security' => 'Data security',
        'changes' => 'Changes to this policy',
        'contact' => 'Contact us',
    ];
@endphp

<div class="jtc-legal">
    <div class="jtc-legal__hero">
        <nav class="jtc-shop__crumb" style="margin-bottom:14px">
            <a href="{{ route('ecomx-anyniche.home') }}" style="color:inherit;text-decoration:none">Home</a>
            <span>/</span>
            <span style="color:#14201c;font-weight:600">Privacy policy</span>
        </nav>
        <h1>Privacy policy</h1>
        <p class="jtc-legal__updated">Last updated: {{ now()->format('d F Y') }}</p>
        <p>
            This policy explains what personal information {{ $siteName }} collects when you browse or order from us,
            why we collect it, who we share it with, and the choices you have. We wrote it in plain language on purpose —
            no legal jargon you need a lawyer to decode.
        </p>
    </div>

    <div class="jtc-legal__layout">
        <nav class="jtc-legal__toc">
            <span class="jtc-legal__toc-label">On this page</span>
            <ul>
                @foreach($sections as $id => $label)
                    <li><a href="#{{ $id }}">{{ $label }}</a></li>
                @endforeach
            </ul>
        </nav>

        <article class="jtc-legal__body">
            <section id="information-we-collect">
                <h2>1. Information we collect</h2>
                <p>We collect information you give us directly, and a small amount collected automatically as you use the site.</p>
                <h3>Information you give us</h3>
                <ul>
                    <li><strong>Account details</strong> — your name, phone number, and email address when you sign up or check out.</li>
                    <li><strong>Delivery information</strong> — the shipping address, alternate phone number, and any delivery notes you save to your address book or enter at checkout.</li>
                    <li><strong>Order details</strong> — the products you buy, quantities, order value, and your chosen payment method.</li>
                    <li><strong>Communications</strong> — anything you tell us directly, such as messages to our support team or a product review you post.</li>
                </ul>
                <h3>Information collected automatically</h3>
                <ul>
                    <li><strong>Device and usage data</strong> — your IP address, browser type, and pages you view, used to keep the store secure and to understand which products and pages are popular.</li>
                    <li><strong>Cart and wishlist activity</strong> — items you add to your cart or wishlist, so they're still there when you come back.</li>
                </ul>
            </section>

            <section id="how-we-use-it">
                <h2>2. How we use your information</h2>
                <p>We use the information we collect to:</p>
                <ul>
                    <li>Process and deliver your orders, including passing your name, phone number, and delivery address to our courier partners.</li>
                    <li>Send order confirmations, delivery updates, and OTP verification codes by SMS or email.</li>
                    <li>Provide customer support and respond to your questions or complaints.</li>
                    <li>Personalize your experience — for example, showing your saved addresses at checkout or remembering your wishlist.</li>
                    <li>Detect and prevent fraud, abuse, or activity that violates our terms.</li>
                    <li>Improve our products, site performance, and the overall shopping experience.</li>
                    <li>Send you promotional offers and updates, only if you've chosen to receive them — you can opt out any time.</li>
                </ul>
            </section>

            <section id="cod-and-payments">
                <h2>3. Cash on delivery & online payments</h2>
                <p>
                    For cash-on-delivery orders, we share your name, phone number, and delivery address with our courier
                    partner so they can complete the delivery and collect payment. We do not store any cash-handling details
                    beyond what's needed to confirm the delivery.
                </p>
                <p>
                    For online payments made through bKash, Nagad, or card, the transaction itself is processed directly by
                    that payment provider. We receive confirmation that a payment succeeded and a reference number — we never
                    see or store your PIN, card number, or account password.
                </p>
            </section>

            <section id="sharing-with-third-parties">
                <h2>4. Sharing with third parties</h2>
                <p>We do not sell your personal information. We only share it with:</p>
                <ul>
                    <li><strong>Courier partners</strong> — to deliver your order to the address you provide.</li>
                    <li><strong>Payment processors</strong> (bKash, Nagad, card gateways) — to complete a payment you've initiated.</li>
                    <li><strong>SMS gateway providers</strong> — to send order updates and OTP verification codes to your phone.</li>
                    <li><strong>Service providers</strong> who help us run the store (hosting, analytics), bound to keep your data confidential and use it only to provide that service.</li>
                    <li><strong>Law enforcement or regulators</strong>, only when required by Bangladeshi law or to protect our legal rights.</li>
                </ul>
            </section>

            <section id="cookies">
                <h2>5. Cookies & similar technologies</h2>
                <p>
                    We use cookies and similar local storage to keep you signed in, remember your cart and wishlist between
                    visits, and understand how the site is used so we can improve it. You can disable cookies in your browser
                    settings, but some parts of the site — like staying logged in or keeping items in your cart — may stop
                    working properly.
                </p>
            </section>

            <section id="data-retention">
                <h2>6. Data retention</h2>
                <p>
                    We keep your account and order information for as long as your account is active, and afterward for as
                    long as needed to meet our legal, accounting, and tax obligations, resolve disputes, and enforce our
                    agreements. Order records in particular are retained for the period required under applicable
                    Bangladeshi commercial and tax regulations.
                </p>
            </section>

            <section id="your-rights">
                <h2>7. Your rights & choices</h2>
                <p>You can, at any time:</p>
                <ul>
                    <li><strong>Access and update</strong> your name, email, phone number, and saved addresses from your account page.</li>
                    <li><strong>Delete a saved address</strong> or add a new one whenever you like.</li>
                    <li><strong>Opt out of marketing messages</strong> by following the unsubscribe instructions in any promotional email or SMS, or by contacting us directly.</li>
                    <li><strong>Request a copy or deletion</strong> of your personal data by contacting our support team — we'll respond within a reasonable time, subject to what we're legally required to keep (for example, completed order records).</li>
                </ul>
            </section>

            <section id="childrens-privacy">
                <h2>8. Children's privacy</h2>
                <p>
                    Our store is not directed at children under 18. We do not knowingly collect personal information from
                    anyone under 18. If you believe a child has provided us with personal information, please contact us and
                    we'll remove it.
                </p>
            </section>

            <section id="data-security">
                <h2>9. Data security</h2>
                <p>
                    We use industry-standard measures — including encrypted connections (HTTPS), hashed passwords, and
                    access controls — to protect your information from unauthorized access, alteration, or disclosure. No
                    method of transmission or storage is 100% secure, but we work to keep your data as safe as reasonably
                    possible.
                </p>
            </section>

            <section id="changes">
                <h2>10. Changes to this policy</h2>
                <p>
                    We may update this privacy policy from time to time to reflect changes in our practices or for legal
                    reasons. When we make material changes, we'll update the "Last updated" date at the top of this page.
                    We encourage you to review this page periodically.
                </p>
            </section>

            <section id="contact">
                <h2>11. Contact us</h2>
                <p>
                    If you have any questions about this privacy policy or how we handle your information, please reach out:
                </p>
                <ul>
                    @if($companyPhone)
                        <li><strong>Phone:</strong> <a href="tel:{{ $companyPhone }}">{{ $companyPhone }}</a></li>
                    @endif
                    <li><strong>Support:</strong> use the Help option in the site header, or the <a href="{{ route('ecomx-anyniche.track') }}">track order</a> page if your question is about an existing order.</li>
                </ul>
            </section>
        </article>
    </div>
</div>
