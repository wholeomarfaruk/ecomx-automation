<section class="container section" aria-label="Why Seldom Fashion">
    <div class="faq" x-data="{ open:0 }">
        <div style="display:flex;flex-direction:column;gap:24px">
            <div>
                <p class="kicker" style="color:var(--ac3)">{{ $kicker }}</p>
                <h2 style="font-size:clamp(26px,3vw,40px)">{{ $heading }}</h2>
                <p style="font-size:13.5px;line-height:1.8;color:rgba(var(--sec-rgb),.65);margin-top:12px">{{ $description }}</p>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                @foreach($trust as $t)
                    <div style="background:rgba(var(--sec-rgb),.06);border:1px solid rgba(var(--sec-rgb),.1);border-radius:12px;padding:16px 18px">
                        <p style="font-family:'Playfair Display',serif;font-size:clamp(20px,2vw,28px);color:var(--ac3)">{{ $t['val'] }}</p>
                        <p style="font-size:11.5px;color:rgba(var(--sec-rgb),.6);margin-top:2px">{{ $t['label'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
        <div>
            <p class="kicker" style="color:rgba(var(--sec-rgb),.5)">Before you order</p>
            <div style="border-top:1px solid rgba(var(--sec-rgb),.12)">
                @foreach($faqs as $i => $q)
                    <div style="border-bottom:1px solid rgba(var(--sec-rgb),.12)">
                        <button class="faq__q" @click="open = open==={{ $i }} ? -1 : {{ $i }}">
                            <span style="display:flex;gap:12px"><span style="font-size:11px;color:var(--ac3)">0{{ $i+1 }}</span>{{ $q['q'] }}</span>
                            <span x-text="open==={{ $i }} ? '×' : '+'" style="font-size:18px"></span>
                        </button>
                        <p x-show="open==={{ $i }}" x-collapse style="margin:0;padding:0 40px 20px 25px;font-size:13px;line-height:1.75;color:rgba(var(--sec-rgb),.6)">{{ $q['a'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
