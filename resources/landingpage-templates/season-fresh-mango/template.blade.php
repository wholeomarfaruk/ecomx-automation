<!DOCTYPE html>
<html lang="bn" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="referrer" content="no-referrer-when-downgrade">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $data->seo->meta_title ?? 'Season Fresh Mango' }}</title>
<meta name="description" content="{{ $data->seo->meta_description ?? '' }}">
@if(!empty($data->seo->meta_image ?? ''))<meta property="og:image" content="{{ $data->seo->meta_image }}">@endif
@if(!empty($data->seo->favicon_url ?? ''))<link rel="icon" href="{{ $data->seo->favicon_url }}">@endif
@php
  $lp_primary = $selected_products->first() ?? null;
  $lp_view_payload = null;
  if ($lp_primary) {
      try {
          $lp_event = new \App\CAPI\ViewItemEvent();
          $lp_event->push(
              null,
              currency: 'BDT',
              contentPrice: $lp_primary->discounted_price,
              contentId: $lp_primary->id,
              contentName: $lp_primary->name,
              contentType: 'product',
              contentCategory: null,
          );
          \App\Jobs\SendMetaCapiEventJob::dispatch($lp_event->serverPayload())
              ->onQueue(config('conversionapi.meta_capi_queue', 'metacapi'));
          $lp_view_payload = $lp_event->browserEventPayload();
      } catch (\Throwable $e) {
          // tracking must never break the page
      }
  }
@endphp
@if(config('conversionapi.tiktok_pixel_id'))
<script>
!function(w,d,t){w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie"],ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e},ttq.load=function(e,n){var i="https://analytics.tiktok.com/i18n/pixel/events.js";ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=i,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};var o=document.createElement("script");o.type="text/javascript",o.async=!0,o.src=i+"?sdkid="+e+"&lib="+t;var a=document.getElementsByTagName("script")[0];a.parentNode.insertBefore(o,a)};
ttq.load('{{ config('conversionapi.tiktok_pixel_id') }}');
ttq.page();
}(window, document, 'ttq');
</script>
@endif
@if(config('conversionapi.gtm_id'))
<script>
(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','{{ config('conversionapi.gtm_id') }}');
</script>
@endif
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
/* ═══════════════════════════════════════
   THEME TOKENS
═══════════════════════════════════════ */
:root {
  --gold:#F5A623; --gold-light:#FFD166; --gold-dark:#C47D0E;
  /* dark defaults */
  --bg:#0A0A0A; --bg2:#111111; --bg3:#1a1a1a;
  --glass-bg:rgba(255,255,255,0.05);
  --glass-border:rgba(245,166,35,0.22);
  --text:#f0e6cc; --text-muted:rgba(240,230,204,0.58);
  --card-bg:rgba(255,255,255,0.04);
  --nav-bg:rgba(10,10,10,0.78);
  --shadow:rgba(0,0,0,0.4);
}
[data-theme="light"] {
  --bg:#FFFDF7; --bg2:#FFF8EE; --bg3:#FFF0D6;
  --glass-bg:rgba(245,166,35,0.06);
  --glass-border:rgba(200,130,10,0.22);
  --text:#2C1A00; --text-muted:rgba(44,26,0,0.55);
  --card-bg:rgba(245,166,35,0.05);
  --nav-bg:rgba(255,253,247,0.88);
  --shadow:rgba(200,130,10,0.12);
}

*{margin:0;padding:0;box-sizing:border-box;}
html{scroll-behavior:smooth;}
body{background:var(--bg);color:var(--text);font-family:'Hind Siliguri',sans-serif;overflow-x:hidden;transition:background .4s,color .4s;}

/* noise grain — dark only */
[data-theme="dark"] body::before{content:'';position:fixed;inset:0;pointer-events:none;z-index:0;opacity:.35;
  background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");}

/* ═══════════════════════════════════════ NAV */
nav{position:fixed;top:0;left:0;right:0;z-index:100;padding:16px 48px;display:flex;align-items:center;justify-content:space-between;backdrop-filter:blur(18px);background:var(--nav-bg);border-bottom:1px solid var(--glass-border);transition:background .4s;}
.nav-logo{font-size:1.15rem;font-weight:600;color:var(--gold);text-decoration:none;display:inline-flex;align-items:center;}

/* hamburger toggler — hidden on desktop, shown on mobile */
.nav-toggle{
  display:none;
  background:transparent;border:1px solid var(--glass-border);
  border-radius:8px;
  width:38px;height:38px;
  padding:0;cursor:pointer;
  flex-direction:column;justify-content:center;align-items:center;
  gap:4px;
  transition:border-color .25s,background .2s;
  order:3;
}
.nav-toggle:hover{border-color:var(--gold);}
.nav-toggle span{
  display:block;width:18px;height:2px;border-radius:2px;
  background:var(--text);
  transition:transform .3s,opacity .25s;
}
nav.nav-open .nav-toggle span:nth-child(1){transform:translateY(6px) rotate(45deg);}
nav.nav-open .nav-toggle span:nth-child(2){opacity:0;}
nav.nav-open .nav-toggle span:nth-child(3){transform:translateY(-6px) rotate(-45deg);}
nav.nav-open .nav-toggle{border-color:var(--gold);background:rgba(245,166,35,.1);}
.nav-links{display:flex;gap:28px;list-style:none;align-items:center;}
.nav-links a{color:var(--text-muted);text-decoration:none;font-size:.88rem;font-weight:400;transition:color .3s;}
.nav-links a:hover{color:var(--gold);}

/* nav controls group */
.nav-controls{display:flex;align-items:center;gap:10px;}
/* theme toggle */
.theme-btn{background:var(--glass-bg);border:1px solid var(--glass-border);border-radius:50px;padding:7px 14px;cursor:pointer;font-size:.78rem;font-weight:500;color:var(--text-muted);display:flex;align-items:center;gap:6px;transition:all .3s;}
.theme-btn:hover{border-color:var(--gold);color:var(--gold);}
/* sound toggle */
.sound-btn{background:var(--glass-bg);border:1px solid var(--glass-border);border-radius:50%;width:34px;height:34px;cursor:pointer;font-size:.95rem;display:flex;align-items:center;justify-content:center;transition:all .3s;color:var(--text-muted);}
.sound-btn:hover{border-color:var(--gold);}
.sound-btn.on{border-color:rgba(245,166,35,.5);color:var(--gold);}

/* ═══════════════════════════════════════ HERO */
.hero{min-height:auto;padding:160px 24px 100px;display:flex;align-items:center;justify-content:center;flex-direction:column;text-align:center;position:relative;overflow:hidden;}
.hero-bg{position:absolute;inset:0;z-index:0;background-size:cover;background-position:center;background-repeat:no-repeat;animation:heroBgZoom 18s ease-in-out infinite alternate;}
@keyframes heroBgZoom{from{transform:scale(1)}to{transform:scale(1.06)}}
.hero-bg::after{content:'';position:absolute;inset:0;background:linear-gradient(to bottom,rgba(5,5,5,.52) 0%,rgba(5,5,5,.38) 45%,rgba(5,5,5,.72) 100%);}
[data-theme="light"] .hero-bg::after{background:linear-gradient(to bottom,rgba(255,248,230,.45) 0%,rgba(255,248,230,.25) 45%,rgba(255,248,230,.65) 100%);}
.hero-glow{position:absolute;inset:0;z-index:1;pointer-events:none;background:radial-gradient(ellipse at 50% 60%,rgba(245,166,35,.1) 0%,transparent 65%);animation:pulseGlow 5s ease-in-out infinite;}
@keyframes pulseGlow{0%,100%{opacity:.8;transform:scale(1)}50%{opacity:1;transform:scale(1.1)}}
.hero>*:not(.hero-bg):not(.hero-glow){position:relative;z-index:2;}
.hero-badge{display:inline-block;background:rgba(245,166,35,.15);border:1px solid rgba(245,166,35,.45);color:var(--gold);font-size:.78rem;font-weight:500;padding:6px 20px;border-radius:50px;margin-bottom:30px;animation:fadeDown .8s ease both;backdrop-filter:blur(8px);}
.hero-logo{width:min(400px,78vw);margin-bottom:30px;animation:fadeDown 1s ease .2s both;filter:drop-shadow(0 0 48px rgba(245,166,35,.45));}
.hero-title{font-size:clamp(2rem,5vw,3.8rem);font-weight:400;line-height:1.25;margin-bottom:14px;animation:fadeUp 1s ease .4s both;text-shadow:0 2px 20px rgba(0,0,0,.55);}
.hero-title strong{font-weight:600;}
.hero-title em{font-style:italic;color:var(--gold);font-weight:500;}
.type-line em{font-style:italic;color:var(--gold);font-weight:500;}
.hero-subtitle-glass{display:inline-block;background:rgba(255,255,255,.09);backdrop-filter:blur(14px);border:1px solid rgba(245,166,35,.28);border-radius:14px;padding:14px 28px;font-size:1rem;font-weight:400;color:rgba(240,230,204,.88);letter-spacing:0;margin-bottom:42px;max-width:560px;line-height:1.75;animation:fadeUp 1s ease .6s both;}
[data-theme="light"] .hero-subtitle-glass{background:rgba(255,255,255,.55);color:rgba(44,26,0,.8);}
.btn-wrap{display:inline-block;animation:fadeUp 1s ease .8s both;}
.btn-main{background:var(--gold);color:#000;font-family:'Hind Siliguri',sans-serif;font-size:1.05rem;font-weight:600;padding:15px 44px;border:none;border-radius:10px;cursor:pointer;transition:transform .3s,box-shadow .3s;animation:btnPulse 2s ease-in-out infinite;}
@keyframes btnPulse{0%,100%{box-shadow:0 0 0 0 rgba(245,166,35,.5);}50%{box-shadow:0 0 0 10px rgba(245,166,35,0);}}
.btn-main:hover{transform:scale(1.04);box-shadow:0 0 28px rgba(245,166,35,.5);}
.scroll-indicator{margin-top:48px;animation:fadeUp 1s ease 1s both;display:flex;flex-direction:column;align-items:center;gap:8px;}
.scroll-indicator span{font-size:.7rem;color:rgba(240,230,204,.45);}
.scroll-arrow{width:20px;height:20px;border-right:2px solid var(--gold);border-bottom:2px solid var(--gold);transform:rotate(45deg);animation:bounceDown 1.5s ease-in-out infinite;}
@keyframes bounceDown{0%,100%{transform:rotate(45deg) translateY(0);opacity:1}50%{transform:rotate(45deg) translateY(8px);opacity:.5}}

/* ═══════════════════════════════════════ MARQUEE */
.marquee-wrap{overflow:hidden;padding:13px 0;border-top:1px solid var(--glass-border);border-bottom:1px solid var(--glass-border);background:rgba(245,166,35,.03);}
.marquee-track{display:flex;gap:48px;animation:marquee 18s linear infinite;white-space:nowrap;}
.marquee-track span{font-size:.8rem;font-weight:500;color:var(--gold);flex-shrink:0;}
.marquee-dot{color:rgba(245,166,35,.3)!important;}
@keyframes marquee{from{transform:translateX(0)}to{transform:translateX(-50%)}}

/* ═══════════════════════════════════════ SECTIONS */
section{padding:80px 20px;max-width:1180px;margin:0 auto;}
.section-badge{display:inline-block;border:1px solid var(--glass-border);background:rgba(245,166,35,.08);color:var(--gold);font-size:.72rem;font-weight:500;padding:5px 14px;border-radius:50px;margin-bottom:14px;}
.section-title{font-size:clamp(1.7rem,3.5vw,2.7rem);font-weight:400;line-height:1.3;margin-bottom:10px;}
.section-title strong{font-weight:600;}
.section-title em{font-style:italic;color:var(--gold);}
.section-desc{font-size:.93rem;color:var(--text-muted);max-width:560px;line-height:1.75;}
.divider{height:1px;background:linear-gradient(90deg,transparent,var(--glass-border),transparent);margin:0 auto;max-width:800px;}

/* ═══════════════════════════════════════ FEATURES */
.features-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:18px;margin-top:44px;}
.feature-card{background:var(--card-bg);border:1px solid var(--glass-border);border-radius:18px;padding:26px 22px;transition:all .4s;position:relative;overflow:hidden;display:flex;gap:16px;align-items:flex-start;}
.feature-card:hover{border-color:rgba(245,166,35,.5);transform:translateY(-3px);box-shadow:0 8px 24px var(--shadow);}
.feature-icon-wrap{width:42px;height:42px;border-radius:10px;background:rgba(245,166,35,.1);border:1px solid rgba(245,166,35,.2);display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0;}
.feature-title{font-size:.95rem;font-weight:600;margin-bottom:5px;color:var(--gold-light);}
[data-theme="light"] .feature-title{color:var(--gold-dark);}
.feature-desc{font-size:.83rem;color:var(--text-muted);line-height:1.6;}

/* ═══════════════════════════════════════ MEDIA SECTION
   Layout: left = image slider, right = video column + mini cards */
.media-section{padding:80px 0;background:linear-gradient(180deg,var(--bg) 0%,var(--bg2) 50%,var(--bg) 100%);}
.media-inner{max-width:1180px;margin:0 auto;padding:0 20px;}
.media-layout{display:block;margin-top:44px;}

/* LEFT: image slider */
.img-slider-wrap{}
.img-slider{position:relative;border-radius:18px;overflow:hidden;background:#111;aspect-ratio:4/3;}
.img-slide{position:absolute;inset:0;opacity:0;transition:opacity .65s ease;}
.img-slide.active{opacity:1;}
.img-slide img{width:100%;height:100%;object-fit:cover;}
.img-slider-btn{position:absolute;top:50%;transform:translateY(-50%);z-index:10;background:rgba(0,0,0,.45);backdrop-filter:blur(4px);border:1px solid rgba(245,166,35,.3);color:var(--gold);width:36px;height:36px;border-radius:50%;cursor:pointer;font-size:1rem;display:flex;align-items:center;justify-content:center;transition:all .3s;}
.img-slider-btn:hover{background:rgba(245,166,35,.25);}
.img-slider-btn.prev{left:10px;}
.img-slider-btn.next{right:10px;}
.img-slider-dots{display:flex;gap:7px;justify-content:center;margin-top:12px;}
.isdot{width:7px;height:7px;border-radius:50%;background:rgba(245,166,35,.3);cursor:pointer;transition:all .3s;}
.isdot.active{background:var(--gold);width:22px;border-radius:3px;}

/* RIGHT: video + mini cards column */
.right-col{display:flex;flex-direction:column;gap:16px;}

/* YouTube embed — auto aspect based on type */
.yt-wrap{position:relative;border-radius:18px;overflow:hidden;background:#000;width:100%;}
.yt-wrap.landscape{aspect-ratio:16/9;}
.yt-wrap.portrait{aspect-ratio:9/16;max-width:260px;margin:0 auto;}
.yt-wrap iframe{position:absolute;inset:0;width:100%;height:100%;border:0;}

/* ═════════════════════════════ IMAGE STRIP (3/2 per view) */
.img-strip-wrap{
  position:relative;
  max-width:1140px;
  margin:0 auto;
  width:100%;
  --is-gap:14px;
  --is-per:3;
}
.img-strip{
  display:flex;
  gap:var(--is-gap);
  overflow-x:auto;
  scroll-snap-type:x mandatory;
  scroll-behavior:smooth;
  -webkit-overflow-scrolling:touch;
  padding:6px 0 18px;
  scrollbar-width:none;
}
.img-strip::-webkit-scrollbar{display:none;}
.img-strip-slide{
  flex:0 0 calc((100% - var(--is-gap) * (var(--is-per) - 1)) / var(--is-per));
  scroll-snap-align:start;
  aspect-ratio:3/4;
  position:relative;
  border-radius:16px;
  overflow:hidden;
  background:#111;
  box-shadow:0 6px 18px rgba(0,0,0,.22);
  transition:transform .35s ease, box-shadow .3s ease;
  cursor:zoom-in;
}
.img-strip-slide::after{
  content:'⚶';
  position:absolute;top:10px;right:10px;
  width:30px;height:30px;border-radius:50%;
  background:rgba(0,0,0,.55);
  color:#fff;font-size:.95rem;
  display:flex;align-items:center;justify-content:center;
  opacity:0;transform:scale(.85);
  transition:opacity .25s,transform .25s;
  backdrop-filter:blur(6px);
  pointer-events:none;
}
.img-strip-slide:hover::after{opacity:1;transform:scale(1);}
.img-strip-slide img{width:100%;height:100%;object-fit:cover;display:block;}
.img-strip-slide:hover{transform:translateY(-3px);box-shadow:0 12px 26px rgba(0,0,0,.32);}
.img-strip-cap{
  position:absolute;left:0;right:0;bottom:0;
  padding:10px 14px;
  font-size:.78rem;font-weight:500;color:#fff;
  background:linear-gradient(180deg,transparent,rgba(0,0,0,.7));
  letter-spacing:.2px;
}
@media(max-width:900px){ .img-strip-wrap{--is-per:2;} }
@media(max-width:480px){ .img-strip-wrap{--is-per:2;--is-gap:10px;} }

/* ═════════════════════════════ IMAGE LIGHTBOX */
.lb-overlay{
  position:fixed;inset:0;z-index:500;
  background:rgba(0,0,0,.92);
  backdrop-filter:blur(8px);
  display:flex;align-items:center;justify-content:center;
  padding:28px;
  opacity:0;pointer-events:none;
  transition:opacity .28s;
}
.lb-overlay.open{opacity:1;pointer-events:auto;}
.lb-figure{
  position:relative;
  margin:0;
  max-width:min(92vw, 1200px);
  max-height:88vh;
  display:flex;flex-direction:column;align-items:center;justify-content:center;
  transform:scale(.9);opacity:0;
  transition:transform .35s cubic-bezier(.34,1.4,.64,1), opacity .3s;
}
.lb-overlay.open .lb-figure{transform:scale(1);opacity:1;}
.lb-figure img{
  max-width:100%;
  max-height:80vh;
  width:auto;height:auto;
  border-radius:14px;
  box-shadow:0 20px 60px rgba(0,0,0,.6);
  display:block;
  user-select:none;
  transition:opacity .18s;
}
.lb-cap{
  margin-top:14px;
  color:#fff;
  font-size:1rem;font-weight:500;
  text-align:center;
  text-shadow:0 1px 3px rgba(0,0,0,.5);
  max-width:80vw;
}
.lb-count{
  margin-bottom:12px;
  background:rgba(255,255,255,.1);
  border:1px solid rgba(255,255,255,.15);
  color:#fff;font-size:.78rem;font-weight:600;
  padding:4px 12px;border-radius:50px;
  font-variant-numeric:tabular-nums;
  letter-spacing:.5px;
  align-self:center;
}
.lb-close{
  position:fixed;top:18px;right:20px;
  z-index:2;
  width:42px;height:42px;border-radius:50%;
  background:rgba(255,255,255,.1);
  border:1px solid rgba(255,255,255,.18);
  color:#fff;
  font-size:1.25rem;line-height:1;cursor:pointer;
  display:flex;align-items:center;justify-content:center;
  transition:background .2s,transform .2s;
  backdrop-filter:blur(8px);
}
.lb-close:hover{background:var(--gold);color:#000;transform:rotate(90deg);}
.lb-nav{
  position:fixed;top:50%;transform:translateY(-50%);
  z-index:2;
  width:52px;height:52px;border-radius:50%;
  background:rgba(255,255,255,.1);
  border:1px solid rgba(255,255,255,.18);
  color:#fff;
  font-size:2rem;line-height:1;cursor:pointer;
  display:flex;align-items:center;justify-content:center;
  transition:background .2s,transform .2s;
  backdrop-filter:blur(8px);
}
.lb-nav:hover{background:var(--gold);color:#000;}
.lb-nav.prev{left:24px;}
.lb-nav.next{right:24px;}
.lb-nav.prev:hover{transform:translateY(-50%) translateX(-3px);}
.lb-nav.next:hover{transform:translateY(-50%) translateX(3px);}
@media(max-width:560px){
  .lb-close{top:14px;right:14px;width:38px;height:38px;}
  .lb-nav{width:44px;height:44px;font-size:1.6rem;}
  .lb-nav.prev{left:8px;}
  .lb-nav.next{right:8px;}
}
.yt-slider-wrap{
  position:relative;
  max-width:1140px;
  margin:0 auto;
  width:100%;
  --yt-gap: 16px;
  --yt-per: 3;
}
.yt-slider{
  display:flex;
  gap:var(--yt-gap);
  overflow-x:auto;
  scroll-snap-type:x mandatory;
  scroll-behavior:smooth;
  -webkit-overflow-scrolling:touch;
  padding:6px 0 18px;
  scrollbar-width:none;
}
.yt-slider::-webkit-scrollbar{display:none;}
.yt-slide{
  flex:0 0 calc((100% - var(--yt-gap) * (var(--yt-per) - 1)) / var(--yt-per));
  scroll-snap-align:start;
  aspect-ratio:9/16;
  position:relative;
  background:#000;
  border-radius:18px;
  overflow:hidden;
  box-shadow:0 8px 24px rgba(0,0,0,.25);
  transition:transform .35s ease, box-shadow .3s ease;
}
.yt-slide:hover{transform:translateY(-3px);box-shadow:0 14px 30px rgba(0,0,0,.32);}
.yt-slide iframe{position:absolute;inset:0;width:100%;height:100%;border:0;}
.yt-slide.is-landscape{aspect-ratio:16/9;}

/* arrows */
.yt-nav{
  position:absolute;top:calc(50% - 6px);transform:translateY(-50%);
  z-index:3;width:42px;height:42px;border-radius:50%;
  background:rgba(0,0,0,.65);
  border:1px solid rgba(255,255,255,.18);
  color:#fff;font-size:1.55rem;line-height:1;
  cursor:pointer;
  display:flex;align-items:center;justify-content:center;
  transition:background .2s,transform .2s,opacity .25s;
  backdrop-filter:blur(8px);
}
.yt-nav:hover{background:var(--gold);color:#000;transform:translateY(-50%) scale(1.08);}
.yt-nav.prev{left:-16px;}
.yt-nav.next{right:-16px;}
.yt-nav[disabled]{opacity:.25;cursor:default;pointer-events:none;}

/* dots */
.yt-dots{
  display:flex;justify-content:center;gap:7px;
  margin-top:6px;
  flex-wrap:wrap;
}
.yt-dot{
  width:7px;height:7px;border-radius:50%;
  background:var(--glass-border);
  border:none;padding:0;cursor:pointer;
  transition:background .25s,width .25s;
}
.yt-dot.is-active{background:var(--gold);width:20px;border-radius:4px;}

/* tablet: 2 per view */
@media(max-width:900px){
  .yt-slider-wrap{--yt-per:2;}
  .yt-nav.prev{left:-8px;}
  .yt-nav.next{right:-8px;}
}
/* mobile: 1 per view */
@media(max-width:560px){
  .yt-slider-wrap{--yt-per:1;max-width:340px;}
  .yt-nav{width:36px;height:36px;font-size:1.35rem;}
  .yt-nav.prev{left:-6px;}
  .yt-nav.next{right:-6px;}
}

/* mini info cards — wide, low height */
.mini-cards{
  display:flex;
  gap:10px;
  flex-wrap:wrap;
}
.mini-card{display:flex;align-items:center;gap:14px;background:var(--card-bg);border:1px solid var(--glass-border);border-radius:12px;padding:14px 18px;transition:all .35s;cursor:default; flex:1;
  min-width:180px;}
.mini-card:hover{border-color:rgba(245,166,35,.45);background:rgba(245,166,35,.05);transform:translateX(4px);}
.mini-card-icon{font-size:1.3rem;flex-shrink:0;width:36px;text-align:center;}
.mini-card-body{flex:1;}
.mini-card-title{font-size:.88rem;font-weight:600;color:var(--gold-light);margin-bottom:2px;}
[data-theme="light"] .mini-card-title{color:var(--gold-dark);}
.mini-card-sub{font-size:.76rem;color:var(--text-muted);}

/* ═══════════════════════════════════════ PACKAGES */
.package-section{padding:80px 20px;max-width:1180px;margin:0 auto;}
.pkg-row{display:flex;align-items:center;background:var(--card-bg);border:1px solid var(--glass-border);border-radius:14px;overflow:hidden;transition:all .35s;margin-bottom:12px;min-height:100px;position:relative;}
.pkg-row::after{content:'';position:absolute;top:0;left:-100%;width:50%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,.03),transparent);animation:shimmer 3.5s ease-in-out infinite;}
@keyframes shimmer{to{left:150%}}
.pkg-row:hover{border-color:rgba(245,166,35,.5);background:rgba(245,166,35,.05);transform:translateX(4px);}
.pkg-left{display:flex;align-items:center;gap:18px;flex:1;padding:18px 22px;}
.pkg-mango-icon{font-size:1.9rem;flex-shrink:0;}
.pkg-name{font-size:1rem;font-weight:600;color:var(--gold-light);margin-bottom:2px;}
[data-theme="light"] .pkg-name{color:var(--gold-dark);}
.pkg-sub{font-size:.78rem;color:var(--text-muted);font-style:italic;}
.pkg-badge-inline{display:inline-block;font-size:.58rem;font-weight:700;background:var(--gold);color:#000;padding:2px 7px;border-radius:4px;margin-left:7px;vertical-align:middle;}
.pkg-right{display:flex;align-items:center;gap:18px;padding:18px 22px;border-left:1px solid var(--glass-border);flex-shrink:0;}
.pkg-price-big{font-size:1.25rem;font-weight:700;color:var(--gold);white-space:nowrap;}
.pkg-kg{font-size:.72rem;color:var(--text-muted);display:block;text-align:right;}
.add-cart-btn{background:transparent;border:1px solid var(--gold);color:var(--gold);font-family:'Hind Siliguri',sans-serif;font-size:.82rem;font-weight:600;padding:9px 20px;border-radius:8px;cursor:pointer;transition:all .3s;white-space:nowrap;}
.add-cart-btn:hover,.add-cart-btn.added{background:var(--gold);color:#000;}

/* ═══════════════════════════════════════ CART BAR */
.cart-bar{position:fixed;bottom:22px;left:50%;transform:translateX(-50%) translateY(90px);z-index:200;background:var(--gold);color:#000;font-weight:700;padding:13px 34px;border-radius:50px;font-size:.93rem;font-family:'Hind Siliguri',sans-serif;box-shadow:0 8px 28px rgba(245,166,35,.38);transition:transform .4s cubic-bezier(.34,1.56,.64,1);cursor:pointer;white-space:nowrap;user-select:none;}
.cart-bar.show{transform:translateX(-50%) translateY(0);}

/* ═══════════════════════════════════════ INLINE ORDER FORM */
.order-section{padding:80px 24px;max-width:680px;margin:0 auto;scroll-margin-top:80px;}
.order-form-wrap{background:var(--bg2);border:1px solid var(--glass-border);border-radius:22px;padding:32px 28px;margin-top:32px;}
[data-theme="light"] .order-form-wrap{background:var(--bg2);}
.order-form-wrap .cart-summary{background:rgba(245,166,35,.06);border:1px solid rgba(245,166,35,.16);border-radius:10px;padding:14px 16px;margin-bottom:22px;}
.order-form-wrap .cart-item{display:flex;justify-content:space-between;font-size:.83rem;color:var(--text);padding:5px 0;border-bottom:1px solid rgba(255,255,255,.05);}
[data-theme="light"] .order-form-wrap .cart-item{border-color:rgba(0,0,0,.06);}
.order-form-wrap .cart-item:last-child{border:none;}
.order-form-wrap .cart-item span:last-child{color:var(--gold);font-weight:600;}
.order-form-wrap .cart-empty{font-size:.83rem;color:var(--text-muted);font-style:italic;}

/* cart row with qty controls */
.order-form-wrap .cart-summary .cart-item{
  display:grid;
  grid-template-columns:1fr auto auto;
  gap:12px;
  align-items:center;
  padding:10px 0;
  border-bottom:1px solid rgba(255,255,255,.06);
}
[data-theme="light"] .order-form-wrap .cart-summary .cart-item{border-color:rgba(0,0,0,.06);}
.order-form-wrap .cart-summary .cart-item:last-of-type{border-bottom:none;}
.ci-info{min-width:0;}
.ci-name{font-size:.86rem;font-weight:600;color:var(--text);line-height:1.3;}
.ci-unit{font-size:.72rem;color:var(--text-muted);margin-top:2px;}

.ci-qty{
  display:inline-flex;align-items:center;gap:0;
  background:var(--glass-bg);
  border:1px solid var(--glass-border);
  border-radius:8px;
  overflow:hidden;
  user-select:none;
}
.qty-btn{
  background:transparent;border:none;color:var(--gold);
  width:28px;height:28px;font-size:1rem;font-weight:600;
  cursor:pointer;display:flex;align-items:center;justify-content:center;
  transition:background .2s;font-family:'Hind Siliguri',sans-serif;
}
.qty-btn:hover:not(:disabled){background:rgba(245,166,35,.15);}
.qty-btn:disabled{color:var(--text-muted);opacity:.4;cursor:not-allowed;}
.qty-n{
  min-width:26px;text-align:center;
  font-size:.85rem;font-weight:700;color:var(--text);
  font-variant-numeric:tabular-nums;padding:0 2px;
}

.ci-line{
  display:flex;align-items:center;gap:8px;
  font-size:.88rem;font-weight:700;color:var(--gold);
  white-space:nowrap;font-variant-numeric:tabular-nums;
}
.ci-remove{
  background:none;border:none;
  color:rgba(240,230,204,.4);
  cursor:pointer;font-size:.85rem;line-height:1;padding:4px;
  transition:color .2s;
}
[data-theme="light"] .ci-remove{color:rgba(44,26,0,.35);}
.ci-remove:hover{color:#ff6b6b;}

.cart-total{
  display:flex;justify-content:space-between;align-items:center;
  margin-top:10px;padding-top:12px;
  border-top:1px dashed rgba(245,166,35,.3);
  font-size:.95rem;
}
.cart-total>span:first-child{color:var(--text-muted);font-weight:500;}
.cart-total-amt{color:var(--gold);font-weight:700;font-size:1.05rem;font-variant-numeric:tabular-nums;}

@media(max-width:480px){
  .order-form-wrap .cart-summary .cart-item{
    grid-template-columns:1fr auto;
    grid-template-areas:
      "info line"
      "qty  qty";
    row-gap:8px;
  }
  .ci-info{grid-area:info;}
  .ci-line{grid-area:line;justify-self:end;}
  .ci-qty{grid-area:qty;justify-self:start;}
}

/* ═══════════════════════════════════════ DELIVERY OPTIONS */
.delivery-opts{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:4px;}
@media(max-width:480px){.delivery-opts{grid-template-columns:1fr;}}
.delivery-opt{
  position:relative;
  display:flex;
  flex-direction:column;
  gap:4px;
  padding:14px 16px;
  background:var(--glass-bg);
  border:1px solid var(--glass-border);
  border-radius:11px;
  cursor:pointer;
  transition:all .25s;
  overflow:visible;
}
.delivery-opt:hover{border-color:rgba(245,166,35,.55);}
.delivery-opt input{position:absolute;opacity:0;pointer-events:none;}
.delivery-opt:has(input:checked){
  background:rgba(245,166,35,.10);
  border-color:var(--gold);
  box-shadow:0 0 0 1px var(--gold) inset;
}
.do-head{
  display:flex;align-items:center;gap:8px;
  line-height:1.2;
}
.do-icon{font-size:1.15rem;flex-shrink:0;line-height:1;}
.do-title{font-size:.92rem;font-weight:600;color:var(--text);}
.delivery-opt:has(input:checked) .do-title{color:var(--gold);}
.do-sub{font-size:.74rem;color:var(--text-muted);}
.do-badge{
  position:absolute;top:10px;right:10px;
  display:flex;flex-direction:column;align-items:center;justify-content:center;
  gap:0;
  font-family:'Hind Siliguri',sans-serif;
  color:#fff;
  background:linear-gradient(135deg,#e23744 0%,#ff6b35 100%);
  padding:6px 10px 5px;
  border-radius:9px;
  line-height:1;
  letter-spacing:.2px;
  box-shadow:0 6px 18px rgba(226,55,68,.45), 0 2px 4px rgba(0,0,0,.18);
  animation:badgeBob 2.2s ease-in-out infinite;
  z-index:2;
  min-width:54px;
}
.do-badge::after{
  content:'';position:absolute;inset:0;border-radius:9px;
  background:linear-gradient(180deg,rgba(255,255,255,.22),transparent 55%);
  pointer-events:none;
}
.db-amt{
  font-size:1.05rem;font-weight:800;
  font-variant-numeric:tabular-nums;
  display:block;text-shadow:0 1px 2px rgba(0,0,0,.22);
}
.db-word{
  font-size:.62rem;font-weight:600;
  text-transform:uppercase;letter-spacing:.6px;
  opacity:.95;
  margin-top:1px;
}
.do-badge:empty{display:none;}

/* breathing + subtle wobble */
@keyframes badgeBob{
  0%,100%{transform:translateY(0) rotate(-3deg) scale(1);}
  25%    {transform:translateY(-2px) rotate(-1deg) scale(1.05);}
  50%    {transform:translateY(0) rotate(3deg) scale(1.02);}
  75%    {transform:translateY(-1px) rotate(1deg) scale(1.04);}
}

/* selected: gradient turns green + a quick "win" bounce */
.delivery-opt:has(input:checked) .do-badge{
  background:linear-gradient(135deg,#1f8a5b 0%,#3fbf85 100%);
  box-shadow:0 6px 22px rgba(31,138,91,.55), 0 2px 4px rgba(0,0,0,.18);
  animation:badgeWin .6s cubic-bezier(.34,1.56,.64,1), badgeBob 2.2s ease-in-out 0.6s infinite;
}
@keyframes badgeWin{
  0%  {transform:rotate(-3deg) scale(.55);}
  60% {transform:rotate(-3deg) scale(1.22);}
  100%{transform:rotate(-3deg) scale(1);}
}

/* give the card more room since badge stacks vertically now */
.delivery-opt{padding-right:78px;}

/* discount line in cart */
.cart-discount{
  display:flex;justify-content:space-between;align-items:center;
  font-size:.82rem;color:#3fbf85;
  padding:6px 0 0;
}
[data-theme="light"] .cart-discount{color:#197a4f;}
.cart-discount-amt{font-weight:600;font-variant-numeric:tabular-nums;}

/* ═══════════════════════════════════════ DISCOUNT POPUP */
.disc-pop-overlay{
  position:fixed;inset:0;z-index:400;
  background:rgba(0,0,0,.65);
  backdrop-filter:blur(6px);
  display:flex;align-items:center;justify-content:center;
  padding:24px;
  opacity:0;pointer-events:none;
  transition:opacity .28s;
}
.disc-pop-overlay.open{opacity:1;pointer-events:auto;}
.disc-pop{
  background:var(--bg2);
  border:1px solid rgba(245,166,35,.4);
  border-radius:20px;
  padding:36px 28px 26px;
  width:100%;max-width:380px;
  text-align:center;
  position:relative;
  transform:translateY(20px) scale(.94);
  transition:transform .35s cubic-bezier(.34,1.4,.64,1);
  box-shadow:0 20px 60px rgba(245,166,35,.25), 0 8px 28px rgba(0,0,0,.4);
  overflow:hidden;
}
.disc-pop-overlay.open .disc-pop{transform:translateY(0) scale(1);}
.disc-pop::before{
  content:'';position:absolute;inset:0 0 auto 0;height:120px;
  background:radial-gradient(ellipse at 50% 0%, rgba(245,166,35,.25), transparent 70%);
  pointer-events:none;
}
.dp-icon{
  position:relative;
  width:74px;height:74px;
  margin:0 auto 14px;
  border-radius:50%;
  background:linear-gradient(135deg,var(--gold),var(--gold-dark));
  display:flex;align-items:center;justify-content:center;
  font-size:2.2rem;
  box-shadow:0 8px 24px rgba(245,166,35,.45);
  animation:dpPop .55s cubic-bezier(.34,1.56,.64,1);
}
@keyframes dpPop{
  0%{transform:scale(.3) rotate(-30deg);opacity:0;}
  60%{transform:scale(1.12) rotate(8deg);}
  100%{transform:scale(1) rotate(0);opacity:1;}
}
.dp-title{
  position:relative;
  font-size:1.4rem;font-weight:700;color:var(--gold);
  margin-bottom:8px;line-height:1.2;
}
.dp-msg{
  position:relative;
  font-size:.95rem;color:var(--text);line-height:1.55;
  margin-bottom:6px;
}
.dp-msg strong{color:var(--gold-light);font-weight:700;}
[data-theme="light"] .dp-msg strong{color:var(--gold-dark);}
.dp-amount{
  position:relative;
  display:inline-block;
  font-size:1.85rem;font-weight:800;color:var(--gold);
  margin:6px 0 14px;
  font-variant-numeric:tabular-nums;
  letter-spacing:.5px;
}
.dp-method{
  position:relative;
  font-size:.78rem;color:var(--text-muted);
  background:var(--glass-bg);
  border:1px solid var(--glass-border);
  padding:6px 14px;border-radius:50px;
  display:inline-block;margin-bottom:22px;
}
.dp-close{
  position:relative;
  background:var(--gold);color:#000;border:none;
  font-family:'Hind Siliguri',sans-serif;
  font-size:.95rem;font-weight:700;
  padding:11px 32px;border-radius:10px;cursor:pointer;
  transition:transform .2s,box-shadow .2s;
  width:100%;
}
.dp-close:hover{transform:scale(1.02);box-shadow:0 6px 20px rgba(245,166,35,.4);}
.dp-x{
  position:absolute;top:12px;right:14px;
  background:none;border:none;color:var(--text-muted);
  font-size:1.3rem;cursor:pointer;line-height:1;padding:4px;z-index:2;
}
.dp-x:hover{color:var(--gold);}

/* confetti dots */
.dp-confetti{position:absolute;inset:0;pointer-events:none;overflow:hidden;}
.dp-confetti span{
  position:absolute;width:8px;height:8px;border-radius:2px;
  animation:confettiFall 1.6s ease-out forwards;
  opacity:0;
}
@keyframes confettiFall{
  0%{transform:translateY(-20px) rotate(0);opacity:1;}
  100%{transform:translateY(380px) rotate(540deg);opacity:0;}
}

/* ═══════════════════════════════════════ bKash instructions */
.bkash-box{
  margin:-6px 0 16px;
  background:linear-gradient(180deg,rgba(226,0,116,.10),rgba(226,0,116,.04));
  border:1px solid rgba(226,0,116,.32);
  border-radius:14px;
  padding:18px 18px 16px;
  animation:bkashOpen .35s ease both;
}
[data-theme="light"] .bkash-box{
  background:linear-gradient(180deg,rgba(226,0,116,.07),rgba(226,0,116,.02));
}
@keyframes bkashOpen{
  from{opacity:0;transform:translateY(-6px);}
  to{opacity:1;transform:translateY(0);}
}
.bkash-head{display:flex;align-items:center;gap:12px;margin-bottom:14px;}
.bkash-logo{
  background:#E2007A;color:#fff;
  font-family:'Hind Siliguri',sans-serif;
  font-weight:700;font-size:.78rem;letter-spacing:.5px;
  padding:5px 11px;border-radius:6px;
  box-shadow:0 4px 12px rgba(226,0,116,.4);
  flex-shrink:0;
}
.bkash-title{font-size:.95rem;font-weight:600;color:var(--text);line-height:1.2;}
.bkash-sub{font-size:.74rem;color:var(--text-muted);margin-top:2px;}

.bkash-num-row{
  display:flex;align-items:center;gap:10px;
  background:var(--glass-bg);
  border:1px dashed rgba(226,0,116,.45);
  border-radius:10px;
  padding:10px 14px;
  margin-bottom:14px;
}
.bkash-num-label{
  font-size:.66rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;
  color:rgba(226,0,116,.95);
  background:rgba(226,0,116,.12);
  padding:3px 8px;border-radius:5px;
  flex-shrink:0;
}
.bkash-num{
  flex:1;font-size:1.02rem;font-weight:700;color:var(--text);
  letter-spacing:.5px;font-variant-numeric:tabular-nums;
  font-family:'Hind Siliguri',sans-serif;
}
.bkash-copy{
  background:#E2007A;color:#fff;border:none;
  font-family:'Hind Siliguri',sans-serif;
  font-size:.74rem;font-weight:600;
  padding:6px 14px;border-radius:6px;cursor:pointer;
  transition:transform .2s,background .2s;
}
.bkash-copy:hover{transform:scale(1.04);background:#c1006a;}
.bkash-copy.copied{background:#1f8a5b;}

.bkash-steps{
  list-style:none;padding:0;margin:0 0 14px;
  display:flex;flex-direction:column;gap:7px;
}
.bkash-steps li{
  display:flex;align-items:flex-start;gap:10px;
  font-size:.83rem;color:var(--text-muted);line-height:1.55;
}
.bkash-steps li strong{color:var(--text);font-weight:600;}
.bkash-step-n{
  flex-shrink:0;width:20px;height:20px;
  background:rgba(226,0,116,.15);
  border:1px solid rgba(226,0,116,.4);
  color:#E2007A;
  border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  font-size:.7rem;font-weight:700;
  margin-top:1px;
}
.bkash-box .form-group label{color:#E2007A;font-weight:600;}
.bkash-box .form-group input{border-color:rgba(226,0,116,.35);}
.bkash-box .form-group input:focus{border-color:#E2007A;}

/* selected bKash chip tint */
.pay-opt.chosen.pay-bkash{
  background:rgba(226,0,116,.12);
  border-color:#E2007A;
  color:#E2007A;
}

/* ═══════════════════════════════════════ MODAL */
.modal-overlay{position:fixed;inset:0;z-index:300;background:rgba(0,0,0,.72);backdrop-filter:blur(6px);display:flex;align-items:flex-center;justify-content:center;padding:24px;opacity:0;pointer-events:none;transition:opacity .3s;}
.modal-overlay.open{opacity:1;pointer-events:all;}
.modal{background:var(--bg2);border:1px solid var(--glass-border);border-radius:22px;padding:32px 28px;width:100%;max-width:500px;transform:translateY(40px) scale(.97);transition:transform .4s cubic-bezier(.34,1.2,.64,1),opacity .3s;max-height:88vh;overflow-y:auto;}
.modal-overlay.open .modal{transform:translateY(0) scale(1);}
.modal-handle{width:38px;height:4px;background:rgba(255,255,255,.14);border-radius:4px;margin:0 auto 22px;}
[data-theme="light"] .modal-handle{background:rgba(0,0,0,.1);}
.modal-title{font-size:1.25rem;font-weight:600;color:var(--gold-light);margin-bottom:5px;}
[data-theme="light"] .modal-title{color:var(--gold-dark);}
.modal-sub{font-size:.83rem;color:var(--text-muted);font-style:italic;margin-bottom:22px;}
.modal-close{float:right;background:none;border:none;color:var(--text-muted);font-size:1.4rem;cursor:pointer;line-height:1;margin-top:-4px;}
.modal-close:hover{color:var(--gold);}
.cart-summary{background:rgba(245,166,35,.06);border:1px solid rgba(245,166,35,.16);border-radius:10px;padding:14px 16px;margin-bottom:22px;}
.cart-item{display:flex;justify-content:space-between;font-size:.83rem;color:var(--text);padding:5px 0;border-bottom:1px solid rgba(255,255,255,.05);}
[data-theme="light"] .cart-item{border-color:rgba(0,0,0,.06);}
.cart-item:last-child{border:none;}
.cart-item span:last-child{color:var(--gold);font-weight:600;}
.cart-empty{font-size:.83rem;color:var(--text-muted);font-style:italic;}
.form-group{margin-bottom:16px;}
.form-group label{display:block;font-size:.8rem;color:var(--text-muted);margin-bottom:7px;font-weight:500;}
.form-group input,.form-group textarea{width:100%;background:var(--glass-bg);border:1px solid var(--glass-border);border-radius:10px;color:var(--text);font-family:'Hind Siliguri',sans-serif;font-size:.93rem;padding:11px 15px;outline:none;transition:border-color .3s;}
.form-group input:focus,.form-group textarea:focus{border-color:var(--gold);}
.form-group textarea{resize:vertical;min-height:76px;}
.payment-opts{display:flex;gap:8px;flex-wrap:wrap;margin-top:4px;}
.pay-opt{flex:1;min-width:85px;padding:9px 6px;text-align:center;background:var(--glass-bg);border:1px solid var(--glass-border);border-radius:9px;cursor:pointer;font-size:.8rem;font-weight:500;color:var(--text-muted);transition:all .25s;user-select:none;}
.pay-opt:hover{border-color:var(--gold);color:var(--gold);}
.pay-opt.chosen{background:rgba(245,166,35,.12);border-color:var(--gold);color:var(--gold);font-weight:600;}
.submit-btn{width:100%;padding:15px;background:var(--gold);color:#000;font-family:'Hind Siliguri',sans-serif;font-size:1rem;font-weight:700;border:none;border-radius:11px;cursor:pointer;transition:transform .2s,box-shadow .2s;margin-top:6px;}
.submit-btn:hover{transform:scale(1.02);box-shadow:0 4px 18px rgba(245,166,35,.38);}
.submit-btn:disabled{opacity:.5;cursor:not-allowed;transform:none;}
.success-state{display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:32px 16px;min-height:280px;}
.success-state .s-icon{font-size:3.2rem;margin-bottom:14px;}
.success-state h3{font-size:1.25rem;font-weight:600;color:var(--gold-light);margin-bottom:7px;}
[data-theme="light"] .success-state h3{color:var(--gold-dark);}
.success-state p{font-size:.88rem;color:var(--text-muted);line-height:1.72;}

/* ═══════════════════════════════════════ STEPS */
.steps{display:flex;flex-direction:column;gap:16px;margin-top:44px;}
.step{display:flex;align-items:flex-start;gap:22px;background:var(--card-bg);border:1px solid var(--glass-border);border-radius:14px;padding:24px;transition:all .4s;}
.step:hover{border-color:rgba(245,166,35,.4);background:rgba(245,166,35,.04);}
.step-num{font-size:2.2rem;font-weight:300;color:rgba(245,166,35,.2);min-width:52px;line-height:1;}
.step-content h3{font-size:.95rem;font-weight:600;color:var(--gold-light);margin-bottom:5px;}
[data-theme="light"] .step-content h3{color:var(--gold-dark);}
.step-content p{font-size:.85rem;color:var(--text-muted);line-height:1.62;}

/* ═══════════════════════════════════════ TESTIMONIALS */
.testimonials{background:var(--bg2);padding:80px 0;transition:background .4s;}
.testimonials-inner{max-width:1180px;margin:0 auto;padding:0 20px;}
.testimonials-grid{display:none;}

/* ═══════════════════════════ TESTIMONIALS SLIDER (4/2/1 per view) */
.testimonials-slider-wrap{
  position:relative;
  margin-top:44px;
  --tm-gap:18px;
  --tm-per:4;
}
.testimonials-track{
  display:flex;
  gap:var(--tm-gap);
  overflow-x:auto;
  scroll-snap-type:x mandatory;
  scroll-behavior:smooth;
  -webkit-overflow-scrolling:touch;
  padding:6px 0 18px;
  scrollbar-width:none;
}
.testimonials-track::-webkit-scrollbar{display:none;}

/* image-only review card */
.tm-shot{
  flex:0 0 calc((100% - var(--tm-gap) * (var(--tm-per) - 1)) / var(--tm-per));
  scroll-snap-align:start;
  position:relative;
  aspect-ratio:3/4;
  background:#111;
  border:1px solid var(--glass-border);
  border-radius:18px;
  overflow:hidden;
  cursor:zoom-in;
  transition:transform .35s ease, box-shadow .3s ease, border-color .3s ease;
  box-shadow:0 6px 18px rgba(0,0,0,.22);
}
.tm-shot:hover{
  transform:translateY(-4px);
  box-shadow:0 14px 30px rgba(0,0,0,.32);
  border-color:rgba(245,166,35,.4);
}
.tm-shot img{
  width:100%;height:100%;display:block;
  object-fit:cover;
}
.tm-shot::after{
  content:'⚶';
  position:absolute;top:10px;right:10px;
  width:30px;height:30px;border-radius:50%;
  background:rgba(0,0,0,.55);color:#fff;
  font-size:.95rem;
  display:flex;align-items:center;justify-content:center;
  opacity:0;transform:scale(.85);
  transition:opacity .25s,transform .25s;
  backdrop-filter:blur(6px);
  pointer-events:none;
}
.tm-shot:hover::after{opacity:1;transform:scale(1);}

@media(max-width:900px){.testimonials-slider-wrap{--tm-per:2;}}
@media(max-width:560px){.testimonials-slider-wrap{--tm-per:1;--tm-gap:14px;}}
.testimonials-slider-wrap .yt-nav.prev{left:-16px;}
.testimonials-slider-wrap .yt-nav.next{right:-16px;}
@media(max-width:560px){
  .testimonials-slider-wrap .yt-nav.prev{left:-4px;}
  .testimonials-slider-wrap .yt-nav.next{right:-4px;}
}
.testimonial-card{background:var(--card-bg);border:1px solid var(--glass-border);border-radius:18px;padding:28px 24px;transition:all .4s;}
.testimonial-card:hover{border-color:rgba(245,166,35,.38);transform:translateY(-4px);}
.testimonial-stars{color:var(--gold);font-size:.95rem;margin-bottom:12px;}
.testimonial-text{font-size:.9rem;color:var(--text);line-height:1.75;font-style:italic;margin-bottom:18px;}
.testimonial-author{display:flex;align-items:center;gap:11px;}
.author-avatar{width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,var(--gold-dark),var(--gold));display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.88rem;color:#000;flex-shrink:0;}
.author-name{font-size:.88rem;font-weight:600;color:var(--gold-light);}
[data-theme="light"] .author-name{color:var(--gold-dark);}
.author-loc{font-size:.72rem;color:var(--text-muted);}

/* ═══════════════════════════════════════ CTA */
.cta-section{text-align:center;padding:96px 24px;position:relative;overflow:hidden;}
.cta-section::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse at center,rgba(245,166,35,.07) 0%,transparent 70%);animation:pulseGlow 5s ease-in-out infinite;}
.cta-subtitle-glass{display:inline-block;background:var(--glass-bg);backdrop-filter:blur(12px);border:1px solid var(--glass-border);border-radius:12px;padding:11px 22px;font-size:.92rem;color:var(--text-muted);margin:18px 0 38px;font-style:italic;}

/* ═══════════════════════════════════════ FOOTER */
footer{border-top:1px solid var(--glass-border);padding:36px 48px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px;font-size:.8rem;color:var(--text-muted);transition:border-color .4s;}
.foot-brand{color:var(--gold);font-weight:600;}
.foot-links{display:flex;gap:22px;}
footer a{color:var(--text-muted);text-decoration:none;transition:color .3s;}
footer a:hover{color:var(--gold);}

/* ═══════════════════════════════════════ ANIMATIONS */
@keyframes fadeDown{from{opacity:0;transform:translateY(-18px)}to{opacity:1;transform:translateY(0)}}
@keyframes fadeUp{from{opacity:0;transform:translateY(22px)}to{opacity:1;transform:translateY(0)}}
.reveal{opacity:0;transform:translateY(28px);transition:opacity .7s ease,transform .7s ease;}
.reveal.visible{opacity:1;transform:translateY(0);}
.reveal-stagger>*{opacity:0;transform:translateY(24px);transition:opacity .6s ease,transform .6s ease;}
.reveal-stagger.visible>*:nth-child(1){opacity:1;transform:translateY(0);transition-delay:0s}
.reveal-stagger.visible>*:nth-child(2){opacity:1;transform:translateY(0);transition-delay:.1s}
.reveal-stagger.visible>*:nth-child(3){opacity:1;transform:translateY(0);transition-delay:.2s}
.reveal-stagger.visible>*:nth-child(4){opacity:1;transform:translateY(0);transition-delay:.3s}
.reveal-stagger.visible>*:nth-child(5){opacity:1;transform:translateY(0);transition-delay:.4s}
.reveal-stagger.visible>*:nth-child(6){opacity:1;transform:translateY(0);transition-delay:.5s}

/* section title regular */
#features .section-title em,
#media .section-title em,
#packages .section-title em,
.testimonials .section-title em,
section .section-title em{
  font-style:normal;
}

/* ═══════════════════════════════════════ FLOATING ACTION BUTTONS */
.fab-wrap{
  position:fixed;bottom:26px;right:18px;
  z-index:250;
  display:flex;flex-direction:column;gap:12px;
  align-items:center;
}
.fab-btn{
  width:52px;height:52px;border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  font-size:1.45rem;
  text-decoration:none;
  box-shadow:0 6px 20px rgba(0,0,0,.32);
  transition:transform .25s,box-shadow .25s;
  border:none;
  cursor:pointer;
  flex-shrink:0;
}
.fab-btn:hover{transform:scale(1.1);box-shadow:0 10px 28px rgba(0,0,0,.38);}
.fab-whatsapp{background:#25D366;color:#fff;}
.fab-whatsapp:hover{background:#1ebe5d;}
.fab-call{background:var(--gold);color:#000;}
.fab-call:hover{background:var(--gold-dark);}
@media(max-width:480px){
  .fab-wrap{bottom:18px;right:12px;}
  .fab-btn{width:46px;height:46px;font-size:1.25rem;}
}

/* ═══════════════════════════════════════ RESPONSIVE */
@media(max-width:900px){
  .media-layout{grid-template-columns:1fr;}
  .yt-wrap.portrait{max-width:100%;width:100%;}
}

@media(max-width:640px){
  nav{padding:13px 18px;}
  .nav-toggle{display:flex;}
  .nav-links{
    position:absolute;top:100%;left:0;right:0;
    flex-direction:column;align-items:stretch;
    gap:0;padding:0;
    background:var(--nav-bg);
    backdrop-filter:blur(20px);
    border-bottom:1px solid var(--glass-border);
    box-shadow:0 12px 28px rgba(0,0,0,.25);
    max-height:0;overflow:hidden;
    transition:max-height .35s ease, padding .25s ease;
  }
  nav.nav-open .nav-links{max-height:300px;padding:8px 0 14px;}
  .nav-links li{width:100%;}
  .nav-links a{
    display:block;padding:13px 22px;
    font-size:.95rem;
    border-left:3px solid transparent;
    transition:background .2s,border-color .2s,color .2s;
  }
  .nav-links a:hover{background:rgba(245,166,35,.08);border-left-color:var(--gold);}
  .pkg-right{padding:14px;}
  footer{flex-direction:column;text-align:center;}
}
@media(max-width:480px){
  .pkg-row{flex-direction:column;align-items:stretch;}
  .pkg-right{border-left:none;border-top:1px solid var(--glass-border);flex-direction:row;justify-content:space-between;}
}
</style>
</head>
<body>
@if(config('conversionapi.gtm_id'))
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ config('conversionapi.gtm_id') }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
@endif



<!-- ═══ IMAGE LIGHTBOX ═══ -->
<div class="lb-overlay" id="lbOverlay" onclick="if(event.target===this)closeLB()" aria-hidden="true">
  <button class="lb-close" onclick="closeLB()" aria-label="Close">✕</button>
  <button class="lb-nav prev" onclick="lbStep(-1)" aria-label="আগের ছবি">‹</button>
  <button class="lb-nav next" onclick="lbStep(1)" aria-label="পরের ছবি">›</button>
  <figure class="lb-figure">
    <div class="lb-count" id="lbCount"></div>
    <img id="lbImg" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg'/%3E" alt="">
    <figcaption id="lbCap" class="lb-cap"></figcaption>
  </figure>
</div>

<!-- ═══ CART BAR ═══ -->
<div class="cart-bar show" id="cartBar" onclick="scrollToOrderForm()">
  🛒 কার্টে <span id="cartCount">1</span>টি আইটেম — অর্ডার দিন
</div>

<!-- ═══ FLOATING ACTION BUTTONS ═══ -->
<div class="fab-wrap">
  @if(!empty($data->contact->whatsapp_url ?? ''))
  <a class="fab-btn fab-whatsapp" href="{{ $data->contact->whatsapp_url }}" target="_blank" rel="noopener" aria-label="WhatsApp">
    <svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
  </a>
  @endif
  <a class="fab-btn fab-call" href="tel:{{ $data->contact->phone ?? '' }}" aria-label="কল করুন">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
  </a>
</div>

<!-- ═══ SUCCESS POPUP ═══ -->
<div class="disc-pop-overlay" id="successPopOverlay" onclick="if(event.target===this)closeSuccessPop()">
  <div class="disc-pop" id="successPop">
    <button class="dp-x" onclick="closeSuccessPop()" aria-label="Close">✕</button>
    <div class="dp-confetti" id="successConfetti"></div>
    <div class="dp-icon">✅</div>
    <div class="dp-title">অর্ডার সফল!</div>
    <div class="dp-msg" id="successMsg"></div>
    <button class="dp-close" onclick="closeSuccessPop()">ধন্যবাদ! ✓</button>
  </div>
</div>

<!-- ═══ DISCOUNT POPUP ═══ -->
<div class="disc-pop-overlay" id="discPopOverlay" onclick="if(event.target===this)closeDiscPop()">
  <div class="disc-pop" id="discPop">
    <button class="dp-x" onclick="closeDiscPop()" aria-label="Close">✕</button>
    <div class="dp-confetti" id="dpConfetti"></div>
    <div class="dp-icon">🎉</div>
    <div class="dp-title">অভিনন্দন!</div>
    <div class="dp-msg">আপনি পেয়েছেন</div>
    <div class="dp-amount" id="dpAmount">৳ ০</div>
    <div class="dp-method" id="dpMethod">ছাড়</div>
    <button class="dp-close" onclick="closeDiscPop()">দারুণ! ✓</button>
  </div>
</div>

<!-- ═══ NAV ═══ -->
<nav>
  <a class="nav-logo" href="{{ url()->current() }}"><img src="{{ $data->hero->logo_url ?? 'season_fresh_mango_logo.png' }}" alt="" width="120"></a>
  <button class="nav-toggle" id="navToggle" aria-label="মেনু টগল" aria-expanded="false" onclick="toggleNav()">
    <span></span><span></span><span></span>
  </button>
  <ul class="nav-links" id="navLinks">
    @foreach($data->nav->menus ?? [(object)['label'=>'বৈশিষ্ট্য','href'=>'#features'],(object)['label'=>'আমাদের বাগান','href'=>'#media'],(object)['label'=>'অর্ডার','href'=>'#packages']] as $menu)
    <li><a href="{{ $menu->href }}" onclick="closeNav()">{{ $menu->label }}</a></li>
    @endforeach
  </ul>
  <div class="nav-controls">
    <button class="theme-btn" onclick="toggleTheme()" id="themeBtn">🌙 Dark</button>
    <button class="sound-btn on" onclick="toggleSound()" id="soundBtn" title="Sound">🔊</button>
  </div>
</nav>

<!-- ═══ HERO ═══ -->
<div class="hero">
  <div class="hero-bg" style="background-image:url('{{ $data->hero->bg_url ?? 'mango_forest.jpg' }}');"></div>
  <div class="hero-glow"></div>
  <div class="hero-badge">{{ $data->hero->badge ?? 'সরাসরি বাগান থেকে আপনার দরজায়' }}</div>
  <h1 class="hero-title">
    <span id="typeLine1" class="type-line"></span><br>
    <strong><span id="typeLine2" class="type-line"></span></strong>
  </h1>
  <div class="hero-subtitle-glass">{!! $data->hero->subtitle ?? 'খাঁটি দেশি আম — কোনো কেমিক্যাল নেই, কোনো কৃত্রিম রঙ নেই।<br>প্রকৃতির মিষ্টতা সরাসরি আপনার পরিবারের জন্য।' !!}</div>
  <div class="btn-wrap">
    <button class="btn-main" onclick="document.getElementById('packages').scrollIntoView({behavior:'smooth'})">এখনই অর্ডার করুন →</button>
  </div>
  <div class="scroll-indicator"><span>নিচে দেখুন</span><div class="scroll-arrow"></div></div>
</div>

<!-- ═══ MARQUEE ═══ -->
<div class="marquee-wrap">
  <div class="marquee-track">
    <span>১০০% অর্গানিক</span><span class="marquee-dot">◆</span><span>দ্রুত ডেলিভারি</span><span class="marquee-dot">◆</span><span>ফ্রেশ গ্যারান্টি</span><span class="marquee-dot">◆</span><span>সরাসরি বাগান থেকে</span><span class="marquee-dot">◆</span><span>কেমিক্যাল মুক্ত</span><span class="marquee-dot">◆</span><span>মিষ্টি ও সুস্বাদু</span><span class="marquee-dot">◆</span>
    <span>১০০% অর্গানিক</span><span class="marquee-dot">◆</span><span>দ্রুত ডেলিভারি</span><span class="marquee-dot">◆</span><span>ফ্রেশ গ্যারান্টি</span><span class="marquee-dot">◆</span><span>সরাসরি বাগান থেকে</span><span class="marquee-dot">◆</span><span>কেমিক্যাল মুক্ত</span><span class="marquee-dot">◆</span><span>মিষ্টি ও সুস্বাদু</span><span class="marquee-dot">◆</span>
  </div>
</div>

<!-- ═══ FEATURES ═══ -->
<section id="features">
  <div class="reveal">
    <div class="section-badge">{{ $data->features->badge ?? 'কেন আমরা আলাদা' }}</div>
    <h2 class="section-title">{!! $data->features->title ?? 'সেরা আমের <em>নিশ্চয়তা</em>' !!}</h2>
    <p class="section-desc">{{ $data->features->desc ?? 'প্রতিটি আম হওয়া উচিত সতেজ, মিষ্টি এবং স্বাস্থ্যকর — কোনো আপোষ ছাড়াই।' }}</p>
  </div>
  <div class="features-grid reveal-stagger">
    @foreach($data->features->items ?? [] as $feat)
      @if(!empty($feat->title))
      <div class="feature-card">
        <div class="feature-icon-wrap">{{ $feat->icon }}</div>
        <div>
          <div class="feature-title">{{ $feat->title }}</div>
          <p class="feature-desc">{{ $feat->desc }}</p>
        </div>
      </div>
      @endif
    @endforeach
  </div>
</section>

<div class="divider"></div>

<!-- ═══ MEDIA SECTION ═══ -->
<div class="media-section" id="media">
  <div class="media-inner">
    <div class="reveal">
      <div class="section-badge">আমাদের বাগান</div>
      <h2 class="section-title">সরাসরি দেখুন <em>আমাদের মান</em></h2>
      <p class="section-desc">রাজশাহীর সতেজ আম বাগান থেকে শুরু করে আপনার দরজা পর্যন্ত — প্রতিটি ধাপে মান নিশ্চিত।</p>
      <!-- mini info cards -->
      <!-- <div class="mini-cards">
        <div class="mini-card"><div class="mini-card-icon">🌳</div><div class="mini-card-body"><div class="mini-card-title">গাছপাকা আম সংগ্রহ</div><div class="mini-card-sub">প্রতিটি আম সম্পূর্ণ পেকে গাছ থেকে নামানো হয়</div></div></div>
        <div class="mini-card"><div class="mini-card-icon">🔬</div><div class="mini-card-body"><div class="mini-card-title">কোয়ালিটি টেস্ট</div><div class="mini-card-sub">প্যাক করার আগে হাতে বাছাই ও মান পরীক্ষা</div></div></div>
        <div class="mini-card"><div class="mini-card-icon">📦</div><div class="mini-card-body"><div class="mini-card-title">যত্নশীল প্যাকেজিং</div><div class="mini-card-sub">সংগ্রহের ১২ ঘণ্টার মধ্যে প্যাক ও ডেলিভারি</div></div></div>
        <div class="mini-card"><div class="mini-card-icon">🚚</div><div class="mini-card-body"><div class="mini-card-title">দ্রুত ডেলিভারি</div><div class="mini-card-sub">সারা বাংলাদেশে ২৪ ঘণ্টার মধ্যে পৌঁছানো</div></div></div>
      </div> -->
    </div>

    <div class="media-layout">

      <!-- VIDEO showcase — full width -->
      <div class="right-col reveal">

        <!-- YouTube video slider — responsive: 3/2/1 per view -->
        <div class="yt-slider-wrap">
          <div class="yt-slider" id="ytSlider">
            <!-- slides injected by JS -->
          </div>
          <button class="yt-nav prev" id="ytPrev" aria-label="আগের ভিডিও" onclick="slideYT(-1)">‹</button>
          <button class="yt-nav next" id="ytNext" aria-label="পরের ভিডিও" onclick="slideYT(1)">›</button>
          <div class="yt-dots" id="ytDots"></div>
        </div>

        <!-- IMAGE SLIDER — responsive 3 / 2 per view, autoplay -->
        <div class="img-strip-wrap" style="margin-top:32px;">
          <div class="img-strip" id="imgStrip"></div>
          <button class="yt-nav prev" id="imgPrev" aria-label="আগের ছবি" onclick="slideImg(-1)">‹</button>
          <button class="yt-nav next" id="imgNext" aria-label="পরের ছবি" onclick="slideImg(1)">›</button>
          <div class="yt-dots" id="imgStripDots"></div>
        </div>

        

      </div>
    </div>
  </div>
</div>

<!-- ═══ PACKAGES ═══ -->
<!-- VARIETY TABS COMMENTED OUT (as requested)
<div class="variety-tabs">
  <button class="vtab active">হিমসাগর</button>
  <button class="vtab">ফজলি</button>
  <button class="vtab">ল্যাংড়া</button>
</div>
END COMMENT -->
<section class="package-section" id="packages">
  <div class="reveal">
    <div class="section-badge">প্যাকেজ</div>
    <h2 class="section-title">প্যাকেজ বেছে নিন, <em>অর্ডার করুন</em></h2>
    <p class="section-desc">সব প্যাকেজে রাজশাহীর সেরা গাছপাকা আম। কর্পোরেট গিফট বক্সে বিশেষ প্যাকেজিং।</p>
  </div>
  <div style="margin-top:32px" id="pkgList" class="reveal-stagger"></div>
</section>

<!-- ═══ ORDER FORM (inline, below packages) ═══ -->
<section class="order-section" id="orderForm">
  <div class="reveal">
    <div class="section-badge">অর্ডার ফর্ম</div>
    <h2 class="section-title">অর্ডার <em>সম্পন্ন করুন</em></h2>
    <p class="section-desc">আপনার তথ্য দিন, আমরা যোগাযোগ করব।</p>
  </div>
  <div class="order-form-wrap reveal" id="orderFormWrap">
    <div id="orderFormContent">
      <div class="cart-summary" id="cartSummary"></div>
      <form id="orderFormEl" novalidate>
        <div class="form-group"><label>আপনার নাম *</label><input type="text" id="fName" placeholder="যেমন: রাহেলা বেগম" required></div>
        <div class="form-group"><label>মোবাইল নম্বর *</label><input type="tel" id="fPhone" placeholder="01XXXXXXXXX" required></div>
        <div class="form-group"><label>ডেলিভারির ঠিকানা *</label><textarea id="fAddress" placeholder="বাড়ি নং, রোড, এলাকা, জেলা..." required></textarea></div>

        <!-- Delivery method (radio-style cards) -->
        <div class="form-group">
          <label>ডেলিভারি পদ্ধতি *</label>
          <div class="delivery-opts" role="radiogroup" aria-label="ডেলিভারি পদ্ধতি">
            <label class="delivery-opt" id="delHome">
              <input type="radio" name="delivery" value="home" onchange="selectDelivery('home',true)" checked>
              <span class="do-head">
                <span class="do-icon">🏠</span>
                <span class="do-title">হোম ডেলিভারি</span>
              </span>
              <span class="do-sub">আপনার দরজায় পৌঁছে যাবে</span>
              <span class="do-badge" id="badgeHome"></span>
            </label>
            <label class="delivery-opt" id="delPickup">
              <input type="radio" name="delivery" value="pickup" onchange="selectDelivery('pickup',true)">
              <span class="do-head">
                <span class="do-icon">📦</span>
                <span class="do-title">পিকআপ পয়েন্ট</span>
              </span>
              <span class="do-sub">নিকটস্থ পয়েন্ট থেকে ছাড়ে</span>
              <span class="do-badge" id="badgePickup"></span>
            </label>
          </div>
        </div>
        <div class="form-group"><label>পেমেন্ট পদ্ধতি *</label>
          <div class="payment-opts">
            <div class="pay-opt" onclick="selectPay(this,'bkash')">বিকাশ</div>
            <div class="pay-opt" onclick="selectPay(this,'cod')">ক্যাশ অন ডেলিভারি</div>
          </div>
          <input type="hidden" id="fPayment">
        </div>

        <!-- bKash send-money instructions — shown only when bKash selected -->
        <div class="bkash-box" id="bkashBox" hidden>
          <div class="bkash-head">
            <div class="bkash-logo">bKash</div>
            <div class="bkash-head-text">
              <div class="bkash-title">সেন্ড মনি করুন</div>
              <div class="bkash-sub">নিচের নাম্বারে পেমেন্ট পাঠান</div>
            </div>
          </div>

          <div class="bkash-num-row">
            <span class="bkash-num-label">Personal</span>
            <span class="bkash-num" id="bkashNum">{{ $data->contact->bkash_number ?? '01341-696476' }}</span>
            <button type="button" class="bkash-copy" onclick="copyBkashNum(this)">কপি</button>
          </div>

          <ol class="bkash-steps">
            <li><span class="bkash-step-n">১</span> বিকাশ এ্যাপ খুলুন অথবা *247# ডায়াল করুন</li>
            <li><span class="bkash-step-n">২</span> <strong>Send Money</strong> সিলেক্ট করুন</li>
            <li><span class="bkash-step-n">৩</span> উপরের নাম্বারে অর্ডারের সমপরিমাণ টাকা পাঠান</li>
            <li><span class="bkash-step-n">৪</span> Reference: <strong>আপনার মোবাইল নম্বর</strong></li>
            <li><span class="bkash-step-n">৫</span> সফল হলে SMS এ পাওয়া Transaction ID নিচে দিন</li>
          </ol>
          <div class="form-group" style="margin-bottom:0">
            <label>Transaction ID (TrxID) *</label>
            <input type="text" id="fTrx" placeholder="যেমন: 9A7B2C4D5E" autocomplete="off">
          </div>
        </div>
        <div class="form-group"><label>বিশেষ নির্দেশনা (ঐচ্ছিক)</label><input type="text" id="fNote" placeholder="যেমন: সন্ধ্যার পর ডেলিভারি দিন"></div>
        <button type="button" class="submit-btn" id="submitBtn" onclick="submitOrder(event)">অর্ডার নিশ্চিত করুন →</button>
      </form>
    </div>
  </div>
</section>

<div class="divider"></div>

<!-- ═══ HOW IT WORKS ═══ -->
<section>
  <div class="reveal">
    <div class="section-badge">অর্ডার প্রক্রিয়া</div>
    <h2 class="section-title">মাত্র <em>৩টি ধাপে</em> আম পান</h2>
  </div>
  <div class="steps reveal-stagger">
    <div class="step"><div class="step-num">০১</div><div class="step-content"><h3>প্যাকেজ বেছে নিন</h3><p>আপনার পছন্দের বক্স সাইজ সিলেক্ট করে কার্টে যোগ করুন।</p></div></div>
    <div class="step"><div class="step-num">০২</div><div class="step-content"><h3>ঠিকানা ও পেমেন্ট দিন</h3><p>ডেলিভারির ঠিকানা দিন এবং বিকাশ / নগদ / ক্যাশ অন ডেলিভারিতে পেমেন্ট করুন।</p></div></div>
    <div class="step"><div class="step-num">০৩</div><div class="step-content"><h3>ঘরে বসে উপভোগ করুন</h3><p>২৪ ঘণ্টার মধ্যে সতেজ আম আপনার দরজায়।</p></div></div>
  </div>
</section>

<div class="divider"></div>

<!-- ═══ TESTIMONIALS ═══ -->
<div class="testimonials">
  <div class="testimonials-inner">
    <div class="reveal" style="text-align:center">
      <div class="section-badge">গ্রাহকদের কথা</div>
      <h2 class="section-title">তারা <em>ভালোবাসেন</em></h2>
    </div>
    <div class="testimonials-slider-wrap reveal">
      <div class="testimonials-track" id="testimonialsTrack"></div>
      <button class="yt-nav prev" id="tmPrev" aria-label="আগের রিভিউ" onclick="slideTm(-1)">‹</button>
      <button class="yt-nav next" id="tmNext" aria-label="পরের রিভিউ" onclick="slideTm(1)">›</button>
      <div class="yt-dots" id="tmDots"></div>
    </div>
  </div>
</div>

<!-- ═══ CTA ═══ -->
<div class="cta-section">
  <div class="reveal">
    <h2 class="section-title" style="font-size:clamp(2rem,5vw,3.1rem)">এই মৌসুম মিস করবেন না —<br><em>এখনই অর্ডার দিন!</em></h2>
    <div class="cta-subtitle-glass">স্টক সীমিত। দুপুর ১২টার মধ্যে অর্ডার করলে পরদিন ডেলিভারি।</div>
    <div class="btn-wrap">
      <button class="btn-main" onclick="document.getElementById('packages').scrollIntoView({behavior:'smooth'})">অর্ডার করুন এখনই 🥭</button>
    </div>
  </div>
</div>

<!-- ═══ FOOTER ═══ -->
<footer>
  <div class="foot-brand">{!! $data->footer->brand ?? '🥭 Season Fresh Mango' !!}</div>
  <div class="foot-links"><a href="{{ $data->footer->facebook_url ?? '#' }}">ফেসবুক</a><a href="{{ $data->footer->whatsapp_url ?? '#' }}">হোয়াটসঅ্যাপ</a><a href="#">যোগাযোগ</a></div>
  <div>{{ $data->footer->copyright ?? '© ২০২৫ সিজন ফ্রেশ ম্যাঙ্গো' }}</div>
</footer>

<script>
/* ══════════════════════════════════════
   THEME TOGGLE
══════════════════════════════════════ */
let isDark = true;

/* nav menu toggle (mobile) */
function toggleNav(){
  const n = document.querySelector('nav');
  const t = document.getElementById('navToggle');
  const open = n.classList.toggle('nav-open');
  t.setAttribute('aria-expanded', open ? 'true' : 'false');
}
function closeNav(){
  const n = document.querySelector('nav');
  if(n.classList.contains('nav-open')){
    n.classList.remove('nav-open');
    document.getElementById('navToggle').setAttribute('aria-expanded','false');
  }
}
// close on outside click
document.addEventListener('click', e=>{
  const n = document.querySelector('nav');
  if(!n || !n.classList.contains('nav-open')) return;
  if(!n.contains(e.target)) closeNav();
});

function toggleTheme(){
  isDark = !isDark;
  document.documentElement.setAttribute('data-theme', isDark ? 'dark' : 'light');
  document.getElementById('themeBtn').textContent = isDark ? '🌙 Dark' : '☀️ Light';
}

/* ══════════════════════════════════════
   AUDIO ENGINE — Web Audio API
══════════════════════════════════════ */
let audioCtx = null;
let soundOn = true; // default ON

function getCtx(){
  if(!audioCtx){ audioCtx = new(window.AudioContext||window.webkitAudioContext)(); }
  if(audioCtx.state==='suspended') audioCtx.resume();
  return audioCtx;
}

function toggleSound(){
  soundOn = !soundOn;
  const btn = document.getElementById('soundBtn');
  btn.textContent = soundOn ? '🔊' : '🔇';
  btn.classList.toggle('on', soundOn);
}

function playTone(freq,type,dur,vol,delay=0){
  if(!soundOn) return;
  try{
    const ctx=getCtx(), t=ctx.currentTime;
    const o=ctx.createOscillator(), g=ctx.createGain();
    o.connect(g); g.connect(ctx.destination);
    o.type=type; o.frequency.setValueAtTime(freq,t+delay);
    g.gain.setValueAtTime(0,t+delay);
    g.gain.linearRampToValueAtTime(vol,t+delay+0.012);
    g.gain.exponentialRampToValueAtTime(0.001,t+delay+dur);
    o.start(t+delay); o.stop(t+delay+dur+0.01);
  }catch(e){}
}

function playNoise(dur,vol,hpFreq=2000,delay=0){
  if(!soundOn) return;
  try{
    const ctx=getCtx(), t=ctx.currentTime;
    const buf=ctx.createBuffer(1,ctx.sampleRate*dur,ctx.sampleRate);
    const d=buf.getChannelData(0); for(let i=0;i<d.length;i++) d[i]=(Math.random()*2-1);
    const src=ctx.createBufferSource();
    const filt=ctx.createBiquadFilter(); filt.type='highpass'; filt.frequency.value=hpFreq;
    const g=ctx.createGain();
    src.buffer=buf; src.connect(filt); filt.connect(g); g.connect(ctx.destination);
    g.gain.setValueAtTime(vol,t+delay);
    g.gain.exponentialRampToValueAtTime(0.001,t+delay+dur);
    src.start(t+delay); src.stop(t+delay+dur+0.01);
  }catch(e){}
}

// typing click
function sndType(){ playNoise(0.04,0.032,2400); playTone(3000,'square',0.025,0.018); }

// cart add — cheerful ding
function sndCartAdd(){
  playTone(523,'sine',0.14,0.13);
  playTone(659,'sine',0.12,0.10,0.11);
  playTone(784,'sine',0.17,0.11,0.22);
}

// order success — warm C-major resolution
function sndSuccess(){
  if(!soundOn) return;
  [261,329,392,523].forEach((f,i)=>playTone(f,'sine',0.55,0.09,i*0.09));
  playTone(1046,'sine',0.38,0.04,0.38);
}

// dedicated, professional discount/congratulation chime
// short rising 4-note phrase in C major: G→C→E→G (5→1→3→5) with bell top
function sndDiscount(){
  if(!soundOn) return;
  // soft pluck swell — noise wash gives it a polished "awarded" texture
  playNoise(0.08,0.035,1200,0.0);

  // melody (bell-like FM via two stacked oscillators per note)
  const seq = [
    {f:392, t:0.00},  // G4
    {f:523, t:0.10},  // C5
    {f:659, t:0.20},  // E5
    {f:784, t:0.30},  // G5
  ];
  seq.forEach(n=>{
    playTone(n.f,      'sine',     0.55, 0.13, n.t);
    playTone(n.f * 2,  'triangle', 0.18, 0.05, n.t);   // bell harmonic
  });

  // sustained top resolve — the "win" moment
  playTone(1046, 'sine',     0.95, 0.10, 0.42);
  playTone(1568, 'triangle', 0.70, 0.04, 0.44);
  playTone(2093, 'sine',     0.50, 0.025,0.48);

  // gentle sparkle
  playNoise(0.10, 0.012, 6000, 0.42);
}

/* ══════════════════════════════════════
   TYPING EFFECT
══════════════════════════════════════ */
const line1Raw  = @json($data->hero->type_line1_raw ?? 'এই মৌসুমের সেরা আম');
const line1Html = @json($data->hero->type_line1_html ?? '<i>এই মৌসুমের</i> <em>সেরা আম</em>');
const line2Raw  = @json($data->hero->type_line2 ?? 'এখন আপনার হাতের নাগালে');
const SPEED = 68;

function typeText(elId, text, htmlFinal, delay, onDone){
  setTimeout(()=>{
    const el = document.getElementById(elId);
    let i = 0;
    function tick(){
      if(i <= text.length){
        el.textContent = text.slice(0,i);
        sndType();
        i++;
        setTimeout(tick, SPEED + Math.random()*28);
      } else {
        el.innerHTML = htmlFinal;
        if(onDone) onDone();
      }
    }
    tick();
  }, delay);
}
function startTyping(){
  typeText('typeLine1', line1Raw, line1Html, 900, ()=>{
    typeText('typeLine2', line2Raw, line2Raw, 280, null);
  });
}

/* ══════════════════════════════════════
   IMAGE SLIDER
══════════════════════════════════════ */
/* image slider removed */

/* ══════════════════════════════════════
   YOUTUBE VIDEO EMBED
   — paste your YouTube video/Shorts ID below
   — isShorts=true  → portrait 9:16
   — isShorts=false → landscape 16:9
══════════════════════════════════════ */
/* ═══════════════════════════════════════
   YOUTUBE VIDEO SLIDER (1.2-1.3 per view)
   — add/remove items in the videos[] array below.
   — type: 'shorts' (9:16) or 'landscape' (16:9)
═══════════════════════════════════════ */
const videos = @json($data->videos ?? []);

let activeSlideIdx = 0;

function renderYTSlider(){
  const wrap = document.getElementById('ytSlider');
  if(!wrap) return;
  wrap.innerHTML = videos.map((v,i)=>{
    const landscape = v.type === 'landscape';
    const src = `https://www.youtube.com/embed/${v.id}?rel=0&modestbranding=1&playsinline=1&controls=1`;
    const tail = landscape ? '' : `&playlist=${v.id}`;
    // first video autoplays (muted, looped). Others load silent without autoplay.
    const autoplay = i === 0 ? '&autoplay=1' : '';
    return `<div class="yt-slide ${landscape?'is-landscape':''}" data-idx="${i}">
      <iframe src="${src}&mute=1&loop=1${tail}${autoplay}"
        allow="autoplay; encrypted-media; picture-in-picture"
        referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
    </div>`;
  }).join('');

  // dots
  const dots = document.getElementById('ytDots');
  dots.innerHTML = videos.map((_,i)=>`<button class="yt-dot ${i===0?'is-active':''}" onclick="goToYT(${i})" aria-label="ভিডিও ${i+1}"></button>`).join('');

  attachYTScroll();
  updateYTNav();
}

function attachYTScroll(){
  const slider = document.getElementById('ytSlider');
  if(!slider) return;
  let raf;
  slider.addEventListener('scroll', ()=>{
    cancelAnimationFrame(raf);
    raf = requestAnimationFrame(()=>{
      const slides = [...slider.querySelectorAll('.yt-slide')];
      const center = slider.scrollLeft + slider.clientWidth/2;
      let bestIdx = 0, bestDist = Infinity;
      slides.forEach((s,i)=>{
        const c = s.offsetLeft + s.offsetWidth/2;
        const d = Math.abs(c - center);
        if(d < bestDist){ bestDist = d; bestIdx = i; }
      });
      if(bestIdx !== activeSlideIdx){
        setActiveYT(bestIdx);
      }
    });
  }, {passive:true});
}

function setActiveYT(idx){
  activeSlideIdx = idx;
  document.querySelectorAll('#ytDots .yt-dot').forEach((d,i)=>{
    d.classList.toggle('is-active', i===idx);
  });
  updateYTNav();
}

function goToYT(idx){
  const slider = document.getElementById('ytSlider');
  if(!slider) return;
  const slides = slider.querySelectorAll('.yt-slide');
  if(!slides.length) return;
  // clamp
  idx = Math.max(0, Math.min(slides.length - 1, idx));
  const slide = slides[idx];
  // offsetLeft is relative to the offsetParent (.yt-slider). Subtract its scroll-padding-equivalent (none here)
  const target = slide.offsetLeft - slider.offsetLeft;
  // optimistic update so quick repeat-clicks work
  setActiveYT(idx);
  slider.scrollTo({ left: target, behavior: 'smooth' });
}

function slideYT(dir){
  goToYT(activeSlideIdx + dir);
}

function updateYTNav(){
  const prev = document.getElementById('ytPrev');
  const next = document.getElementById('ytNext');
  if(!prev || !next) return;
  prev.toggleAttribute('disabled', activeSlideIdx === 0);
  // last reachable index = N - perView (so last view fully fits)
  const slider = document.getElementById('ytSlider');
  const first  = slider.querySelector('.yt-slide');
  const perView = first ? Math.max(1, Math.round(slider.clientWidth / (first.offsetWidth + 16))) : 1;
  next.toggleAttribute('disabled', activeSlideIdx >= videos.length - perView);
}

renderYTSlider();

/* ═══════════════════════════════════════
   IMAGE STRIP — below the video slider
   — edit images[] below to change
═══════════════════════════════════════ */
const stripImages = @json($data->strip_images ?? []);

let imgStripIdx = 0;
let imgStripAuto = null;

function renderImgStrip(){
  const strip = document.getElementById('imgStrip');
  if(!strip) return;
  strip.innerHTML = stripImages.map((m,i)=>`
    <div class="img-strip-slide" data-idx="${i}" onclick="openLB(${i})">
      <img src="${m.src}" alt="${m.cap}" loading="lazy">
      ${m.cap?`<div class="img-strip-cap">${m.cap}</div>`:''}
    </div>`).join('');

  const dots = document.getElementById('imgStripDots');
  dots.innerHTML = stripImages.map((_,i)=>`<button class="yt-dot ${i===0?'is-active':''}" onclick="goImgStrip(${i})" aria-label="ছবি ${i+1}"></button>`).join('');

  attachImgStripScroll();
  updateImgStripNav();
  startImgAutoplay();

  // pause autoplay on hover / touch
  strip.addEventListener('mouseenter', stopImgAutoplay);
  strip.addEventListener('mouseleave', startImgAutoplay);
  strip.addEventListener('touchstart', stopImgAutoplay, {passive:true});
}

function attachImgStripScroll(){
  const strip = document.getElementById('imgStrip');
  if(!strip) return;
  let raf;
  strip.addEventListener('scroll', ()=>{
    cancelAnimationFrame(raf);
    raf = requestAnimationFrame(()=>{
      const slides = [...strip.querySelectorAll('.img-strip-slide')];
      const center = strip.scrollLeft + strip.clientWidth/2;
      let bestIdx = 0, bestDist = Infinity;
      slides.forEach((s,i)=>{
        const c = s.offsetLeft + s.offsetWidth/2;
        const d = Math.abs(c - center);
        if(d < bestDist){ bestDist = d; bestIdx = i; }
      });
      if(bestIdx !== imgStripIdx) setActiveImgStrip(bestIdx);
    });
  }, {passive:true});
}

function setActiveImgStrip(idx){
  imgStripIdx = idx;
  document.querySelectorAll('#imgStripDots .yt-dot').forEach((d,i)=>{
    d.classList.toggle('is-active', i===idx);
  });
  updateImgStripNav();
}

function goImgStrip(idx){
  const strip = document.getElementById('imgStrip');
  if(!strip) return;
  const slides = strip.querySelectorAll('.img-strip-slide');
  if(!slides.length) return;
  idx = ((idx % slides.length) + slides.length) % slides.length; // wrap
  setActiveImgStrip(idx);
  const slide = slides[idx];
  strip.scrollTo({ left: slide.offsetLeft - strip.offsetLeft, behavior:'smooth' });
}

function slideImg(dir){
  const strip = document.getElementById('imgStrip');
  const first = strip && strip.querySelector('.img-strip-slide');
  const perView = first ? Math.max(1, Math.round(strip.clientWidth / (first.offsetWidth + 14))) : 1;
  let next = imgStripIdx + dir;
  // wrap when at the boundaries
  const max = stripImages.length - perView;
  if(next < 0) next = max;
  else if(next > max) next = 0;
  goImgStrip(next);
}

function updateImgStripNav(){
  // arrows always enabled (wrapping)
  const p = document.getElementById('imgPrev');
  const n = document.getElementById('imgNext');
  if(p) p.removeAttribute('disabled');
  if(n) n.removeAttribute('disabled');
}

function startImgAutoplay(){
  stopImgAutoplay();
  imgStripAuto = setInterval(()=>slideImg(1), 3800);
}
function stopImgAutoplay(){
  if(imgStripAuto){ clearInterval(imgStripAuto); imgStripAuto = null; }
}

renderImgStrip();

/* ═══════════════════════════════════════
   TESTIMONIALS CAROUSEL (4 / 2 / 1 per view, autoplay)
═══════════════════════════════════════ */
const testimonials = @json($data->testimonials ?? []);

let tmIdx = 0;
let tmAuto = null;

function renderTestimonials(){
  const track = document.getElementById('testimonialsTrack');
  if(!track) return;
  track.innerHTML = testimonials.map((t,i)=>`
    <div class="tm-shot" data-idx="${i}" onclick="openTmLB(${i})">
      <img src="${t.src}" alt="${t.cap || 'রিভিউ'}" loading="lazy">
    </div>`).join('');

  // dots = number of "pages"
  renderTmDots();
  attachTmScroll();
  startTmAutoplay();

  track.addEventListener('mouseenter', stopTmAutoplay);
  track.addEventListener('mouseleave', startTmAutoplay);
  track.addEventListener('touchstart', stopTmAutoplay, {passive:true});

  window.addEventListener('resize', ()=>{ renderTmDots(); updateTmNav(); });
}

// reuse the main image lightbox by injecting reviews into stripImages[] index-aware
function openTmLB(i){
  const offset = stripImages.length;
  // append testimonials to lightbox pool once (idempotent)
  if(!window.__tmsAppended){
    testimonials.forEach(t => stripImages.push({ src:t.src, cap:t.cap || '' }));
    window.__tmsAppended = true;
  }
  openLB(offset + i);
}

function tmPerView(){
  const wrap = document.querySelector('.testimonials-slider-wrap');
  if(!wrap) return 4;
  const cs = getComputedStyle(wrap);
  const v = parseInt(cs.getPropertyValue('--tm-per'));
  return Math.max(1, v || 4);
}

function renderTmDots(){
  const dots = document.getElementById('tmDots');
  if(!dots) return;
  const per = tmPerView();
  const pages = Math.max(1, testimonials.length - per + 1);
  dots.innerHTML = Array.from({length: pages}, (_,i)=>
    `<button class="yt-dot ${i===tmIdx?'is-active':''}" onclick="goTm(${i})" aria-label="স্লাইড ${i+1}"></button>`
  ).join('');
}

function attachTmScroll(){
  const track = document.getElementById('testimonialsTrack');
  if(!track) return;
  let raf;
  track.addEventListener('scroll', ()=>{
    cancelAnimationFrame(raf);
    raf = requestAnimationFrame(()=>{
      const slides = [...track.querySelectorAll('.tm-shot')];
      const center = track.scrollLeft + track.clientWidth/2;
      let bestIdx = 0, bestDist = Infinity;
      slides.forEach((s,i)=>{
        const c = s.offsetLeft + s.offsetWidth/2;
        const d = Math.abs(c - center);
        if(d < bestDist){ bestDist = d; bestIdx = i; }
      });
      if(bestIdx !== tmIdx){
        tmIdx = bestIdx;
        document.querySelectorAll('#tmDots .yt-dot').forEach((d,i)=>{
          d.classList.toggle('is-active', i===tmIdx);
        });
        updateTmNav();
      }
    });
  }, {passive:true});
}

function goTm(idx){
  const track = document.getElementById('testimonialsTrack');
  if(!track) return;
  const slides = track.querySelectorAll('.tm-shot');
  if(!slides.length) return;
  const max = Math.max(0, testimonials.length - tmPerView());
  idx = Math.max(0, Math.min(max, idx));
  tmIdx = idx;
  document.querySelectorAll('#tmDots .yt-dot').forEach((d,i)=>{
    d.classList.toggle('is-active', i===tmIdx);
  });
  updateTmNav();
  track.scrollTo({ left: slides[idx].offsetLeft - track.offsetLeft, behavior:'smooth' });
}

function slideTm(dir){
  const max = Math.max(0, testimonials.length - tmPerView());
  let next = tmIdx + dir;
  if(next < 0) next = max;        // wrap left
  else if(next > max) next = 0;   // wrap right
  goTm(next);
}

function updateTmNav(){
  // arrows are always active (wrapping)
  const p = document.getElementById('tmPrev');
  const n = document.getElementById('tmNext');
  if(p) p.removeAttribute('disabled');
  if(n) n.removeAttribute('disabled');
}

function startTmAutoplay(){
  stopTmAutoplay();
  tmAuto = setInterval(()=>slideTm(1), 4500);
}
function stopTmAutoplay(){
  if(tmAuto){ clearInterval(tmAuto); tmAuto = null; }
}

renderTestimonials();

/* ═══════════════════════════════════════
   IMAGE LIGHTBOX
═══════════════════════════════════════ */
let lbIdx = 0;
function openLB(idx){
  lbIdx = idx;
  paintLB();
  const o = document.getElementById('lbOverlay');
  o.classList.add('open');
  o.setAttribute('aria-hidden','false');
  document.body.style.overflow = 'hidden';
  stopImgAutoplay();
}
function closeLB(){
  const o = document.getElementById('lbOverlay');
  o.classList.remove('open');
  o.setAttribute('aria-hidden','true');
  document.body.style.overflow = '';
  startImgAutoplay();
}
function lbStep(dir){
  const n = stripImages.length;
  lbIdx = ((lbIdx + dir) % n + n) % n;
  paintLB();
}
function paintLB(){
  const m = stripImages[lbIdx];
  const img = document.getElementById('lbImg');
  img.style.opacity = '0';
  setTimeout(()=>{
    img.src = m.src;
    img.alt = m.cap || '';
    img.onload = ()=>{ img.style.opacity = '1'; };
    setTimeout(()=>{ img.style.opacity = '1'; }, 280);  // cached fallback
  }, 100);
  document.getElementById('lbCap').textContent = m.cap || '';
  document.getElementById('lbCount').textContent = toBn(lbIdx+1) + ' / ' + toBn(stripImages.length);
}
// keyboard nav
document.addEventListener('keydown', e=>{
  const o = document.getElementById('lbOverlay');
  if(!o || !o.classList.contains('open')) return;
  if(e.key === 'Escape')          closeLB();
  else if(e.key === 'ArrowLeft')  lbStep(-1);
  else if(e.key === 'ArrowRight') lbStep(1);
});
// swipe nav
(function(){
  const ov = document.getElementById('lbOverlay');
  if(!ov) return;
  let sx = 0;
  ov.addEventListener('touchstart', e=>{ sx = e.touches[0].clientX; }, {passive:true});
  ov.addEventListener('touchend',   e=>{
    const dx = e.changedTouches[0].clientX - sx;
    if(Math.abs(dx) > 40) lbStep(dx < 0 ? 1 : -1);
  });
})();

/* ══════════════════════════════════════
   PACKAGES
══════════════════════════════════════ */
// offers: per-product discount that activates only for that delivery method.
//   type: 'flat' = fixed ৳ off per unit
//   type: 'percent' = % off per unit
//   omit / null = no offer for that method.
const packages = @json($data->packages ?? []);

let selectedDelivery = 'home';

// returns the per-unit discount ৳ for an item under current delivery method
function unitDiscount(pkg, deliveryType){
  const o = pkg.offers && pkg.offers[deliveryType];
  if(!o) return 0;
  if(o.type === 'flat')    return o.value;
  if(o.type === 'percent') return Math.round(pkg.priceNum * o.value / 100);
  return 0;
}
function offerLabel(o){
  if(!o) return '';
  if(o.type === 'flat')    return '<span class="db-amt">৳' + toBn(o.value) + '</span><span class="db-word">ছাড়</span>';
  if(o.type === 'percent') return '<span class="db-amt">' + toBn(o.value) + '%</span><span class="db-word">ছাড়</span>';
  return '';
}

// Bangla digit helper
const BN_DIGIT = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
function toBn(n){ return String(n).replace(/\d/g, d=>BN_DIGIT[+d]); }
function bnPrice(n){ return '৳ ' + toBn(n.toLocaleString('en-US')); }

function renderPackages(){
  const list = document.getElementById('pkgList');
  list.innerHTML = packages
    .map((p,i) => ({p,i}))
    .filter(({p}) => p.product_id)
    .map(({p,i}) => `
    <div class="pkg-row">
      <div class="pkg-left">
        <div class="pkg-mango-icon">🥭</div>
        <div>
          <div class="pkg-name">${p.kg} কেজি ${p.label}${p.badge?`<span class="pkg-badge-inline">${p.badge}</span>`:''}</div>
          <div class="pkg-sub">${p.desc}</div>
        </div>
      </div>
      <div class="pkg-right">
        <div><div class="pkg-price-big">${p.price}</div><span class="pkg-kg">${p.kg} কেজি</span></div>
        <button class="add-cart-btn" id="cb${i}" onclick="addToCart(${i},this)">কার্টে যোগ করুন +</button>
      </div>
    </div>`).join('');
  const el=document.getElementById('pkgList');
  el.classList.remove('visible');
  setTimeout(()=>el.classList.add('visible'),60);
}

let cartItems=[], selectedPayment='';

function cartTotalQty(){ return cartItems.reduce((s,c)=>s+c.qty,0); }
function cartSubtotal(){ return cartItems.reduce((s,c)=>s+c.qty*packages[c.idx].priceNum,0); }
function cartTotalDiscount(){
  return cartItems.reduce((s,c)=>s + c.qty * unitDiscount(packages[c.idx], selectedDelivery), 0);
}
function cartTotalPrice(){ return Math.max(0, cartSubtotal() - cartTotalDiscount()); }

function updateCartCount(){
  document.getElementById('cartCount').textContent = toBn(cartTotalQty());
}

function addToCart(idx,btn){
  const existing = cartItems.find(c=>c.idx===idx);
  if(existing){ existing.qty += 1; }
  else { cartItems.push({idx, qty:1}); }
  updateCartCount();
  document.getElementById('cartBar').classList.add('show');
  sndCartAdd();
  renderCartSummary();
  btn.textContent='✓ যোগ হয়েছে'; btn.classList.add('added');
  setTimeout(()=>{btn.textContent='কার্টে যোগ করুন +';btn.classList.remove('added');},2000);

  // ── AddToCart tracking ────────────────────────────────────────────────────
  const _pkg  = packages[idx];
  const _qty  = cartItems.find(c=>c.idx===idx)?.qty ?? 1;
  const _val  = (_pkg.priceNum - unitDiscount(_pkg, selectedDelivery)) * _qty;
  const _atcId = lpEventId();
  window.dataLayer.push({
    event: 'AddToCart',
    event_id: _atcId,
    event_source_url: lpCurrentUrl,
    custom_data: {
      content_ids: [String(_pkg.product_id || '')],
      content_name: _pkg.label,
      value: _val,
      currency: 'BDT',
      quantity: _qty,
      content_type: 'product',
    },
  });
  lpCapiFetch({
    event_name: 'add_to_cart',

    event_id: _atcId,
    event_source_url: lpCurrentUrl,
    content_id: String(_pkg.product_id || ''),
    content_name: _pkg.label,
    value: _val,
    currency: 'BDT',
    quantity: _qty,
  });
}

/* ══════════════════════════════════════
   INLINE ORDER FORM — scroll + visibility
══════════════════════════════════════ */
function scrollToOrderForm(){
  document.getElementById('orderForm').scrollIntoView({behavior:'smooth',block:'start'});
}

function renderCartSummary(){
  const el=document.getElementById('cartSummary');
  if(!cartItems.length){
    el.innerHTML='<div class="cart-empty">এখনো কোনো আইটেম যোগ করা হয়নি।</div>';
    return;
  }
  const rows = cartItems.map((c,i)=>{
    const p = packages[c.idx];
    const line = p.priceNum * c.qty;
    return `
      <div class="cart-item">
        <div class="ci-info">
          <div class="ci-name">${p.kg} কেজি ${p.label}</div>
          <div class="ci-unit">${p.price} × ${toBn(c.qty)}</div>
        </div>
        <div class="ci-qty">
          <button type="button" class="qty-btn" onclick="changeQty(${i},-1)" ${c.qty<=1?'disabled':''} aria-label="কমান">−</button>
          <span class="qty-n">${toBn(c.qty)}</span>
          <button type="button" class="qty-btn" onclick="changeQty(${i},1)" aria-label="বাড়ান">+</button>
        </div>
        <div class="ci-line">${bnPrice(line)}
          <button type="button" class="ci-remove" onclick="removeCartItem(${i})" aria-label="বাদ দিন">✕</button>
        </div>
      </div>`;
  }).join('');
  const disc = cartTotalDiscount();
  const sub  = cartSubtotal();
  const discRow = disc > 0
    ? `<div class="cart-discount"><span>${selectedDelivery==='home'?'হোম ডেলিভারি':'পিকআপ পয়েন্ট'} ছাড়</span><span class="cart-discount-amt">− ${bnPrice(disc)}</span></div>`
    : '';
  const subRow = disc > 0
    ? `<div class="cart-discount" style="color:var(--text-muted);"><span>সাবটোটাল</span><span class="cart-discount-amt">${bnPrice(sub)}</span></div>`
    : '';
  el.innerHTML = rows + subRow + discRow + `
    <div class="cart-total">
      <span>মোট</span>
      <span class="cart-total-amt">${bnPrice(cartTotalPrice())}</span>
    </div>`;
  if(typeof updateDeliveryBadges === 'function') updateDeliveryBadges();
}

function changeQty(idx, delta){
  const item = cartItems[idx];
  if(!item) return;
  const next = item.qty + delta;
  if(next < 1) return;
  item.qty = next;
  updateCartCount();
  renderCartSummary();
  if(delta>0) sndCartAdd();
}

function removeCartItem(idx){
  cartItems.splice(idx,1);
  updateCartCount();
  if(!cartItems.length) document.getElementById('cartBar').classList.remove('show');
  renderCartSummary();
}

/* hide cart bar when order form is in view */
const orderFormSection = document.getElementById('orderForm');
const cartBarEl = document.getElementById('cartBar');
const formObserver = new IntersectionObserver(entries=>{
  entries.forEach(e=>{
    if(e.isIntersecting){
      cartBarEl.classList.remove('show');
    } else if(cartItems.length){
      cartBarEl.classList.add('show');
    }
  });
},{threshold:0.15});
formObserver.observe(orderFormSection);

function selectDelivery(type, fromUser){
  selectedDelivery = type;
  const r = document.querySelector('input[name="delivery"][value="'+type+'"]');
  if(r) r.checked = true;
  renderCartSummary();
  updateDeliveryBadges();
  if(fromUser){
    const disc = cartTotalDiscount();
    if(disc > 0) showDiscPop(disc, type);
  }
}

function showDiscPop(amount, type){
  document.getElementById('dpAmount').textContent = bnPrice(amount);
  document.getElementById('dpMethod').textContent = (type==='home'?'🏠 হোম ডেলিভারি':'📦 পিকআপ পয়েন্ট') + ' ছাড়';
  const ov = document.getElementById('discPopOverlay');
  ov.classList.add('open');
  spawnConfetti();
  if(typeof sndDiscount === 'function') sndDiscount();
}
function closeDiscPop(){
  document.getElementById('discPopOverlay').classList.remove('open');
}

function showSuccessPop(name, phone){
  document.getElementById('successMsg').innerHTML =
    `${name} ভাই/আপু, আপনার অর্ডার পাওয়া গেছে।<br>শীঘ্রই <strong>${phone}</strong> নম্বরে যোগাযোগ করা হবে।<br><br>২৪ ঘণ্টার মধ্যে ডেলিভারি।`;
  const ov = document.getElementById('successPopOverlay');
  ov.classList.add('open');
  // reuse confetti with success colors
  const host = document.getElementById('successConfetti');
  host.innerHTML = '';
  const colors = ['#F5A623','#FFD166','#1f8a5b','#3fbf85','#fff'];
  for(let i = 0; i < 22; i++){
    const s = document.createElement('span');
    s.style.left = (Math.random()*100) + '%';
    s.style.top  = (-10 - Math.random()*30) + 'px';
    s.style.background = colors[i % colors.length];
    s.style.animationDelay = (Math.random()*0.4) + 's';
    s.style.transform = `rotate(${Math.random()*360}deg)`;
    host.appendChild(s);
  }
}
function closeSuccessPop(){
  document.getElementById('successPopOverlay').classList.remove('open');
}
function spawnConfetti(){
  const host = document.getElementById('dpConfetti');
  host.innerHTML = '';
  const colors = ['#F5A623','#FFD166','#1f8a5b','#E2007A','#fff'];
  for(let i=0;i<22;i++){
    const s = document.createElement('span');
    s.style.left = (Math.random()*100) + '%';
    s.style.top  = (-10 - Math.random()*30) + 'px';
    s.style.background = colors[i % colors.length];
    s.style.animationDelay = (Math.random()*0.4) + 's';
    s.style.transform = `rotate(${Math.random()*360}deg)`;
    host.appendChild(s);
  }
}
// dismiss popups on Esc
document.addEventListener('keydown', e=>{
  if(e.key === 'Escape'){ closeDiscPop(); closeSuccessPop(); }
});

// surface the strongest available offer for each method as a small chip
function updateDeliveryBadges(){
  const labelFor = (type)=>{
    // find max ৳ discount across CART items for that type
    let best = 0, bestStr = '';
    cartItems.forEach(c=>{
      const o = packages[c.idx].offers && packages[c.idx].offers[type];
      if(!o) return;
      const d = unitDiscount(packages[c.idx], type);
      if(d > best){ best = d; bestStr = offerLabel(o); }
    });
    return bestStr;
  };
  const h = document.getElementById('badgeHome');
  const p = document.getElementById('badgePickup');
  if(h) h.innerHTML = labelFor('home');
  if(p) p.innerHTML = labelFor('pickup');
}

function selectPay(el,m){
  document.querySelectorAll('.pay-opt').forEach(o=>o.classList.remove('chosen','pay-bkash'));
  el.classList.add('chosen');
  if(m==='bkash') el.classList.add('pay-bkash');
  selectedPayment=m;
  document.getElementById('fPayment').value=m;
  const box=document.getElementById('bkashBox');
  if(m==='bkash'){ box.hidden=false; }
  else { box.hidden=true; const t=document.getElementById('fTrx'); if(t) t.value=''; }
}

function copyBkashNum(btn){
  const num=document.getElementById('bkashNum').textContent.replace(/-/g,'');
  if(navigator.clipboard){ navigator.clipboard.writeText(num).catch(()=>{}); }
  const old=btn.textContent;
  btn.textContent='✓ কপি হয়েছে';
  btn.classList.add('copied');
  setTimeout(()=>{ btn.textContent=old; btn.classList.remove('copied'); },1600);
}

function submitOrder(e){
  e.preventDefault();

  const name    = document.getElementById('fName').value.trim();
  const phone   = document.getElementById('fPhone').value.trim();
  const address = document.getElementById('fAddress').value.trim();
  const payment = document.getElementById('fPayment').value;

  if(!name)    { document.getElementById('fName').focus({ preventScroll: true }); return; }
  if(!phone)   { document.getElementById('fPhone').focus({ preventScroll: true }); return; }
  if(!address) { document.getElementById('fAddress').focus({ preventScroll: true }); return; }
  if(!payment) { alert('পেমেন্ট পদ্ধতি বেছে নিন।'); return; }
  if(payment==='bkash'){
    const trxEl=document.getElementById('fTrx');
    if(!trxEl.value.trim()){ alert('Transaction ID দিন।'); trxEl.focus({ preventScroll: true }); return; }
  }
  if(!cartItems.length){ alert('কার্টে কোনো আইটেম নেই।'); return; }

  // ── InitiateCheckout tracking ─────────────────────────────────────────────
  const _icId       = lpEventId();
  const _icContents = cartItems.map(c => ({
    id:         String(packages[c.idx].product_id || ''),
    quantity:   c.qty,
    item_price: packages[c.idx].priceNum - unitDiscount(packages[c.idx], selectedDelivery),
  }));
  const _icValue   = cartTotalPrice();
  const _icPayload = {
    event: 'begin_checkout',
    event_id: _icId,
    event_source_url: lpCurrentUrl,
    referrer_url: lpReferrer,
    custom_data: {
      currency: 'BDT',
      value: _icValue,
      contents: _icContents,
      num_items: _icContents.reduce((s, c) => s + c.quantity, 0),
    },
    user_data: { phone_number: phone, first_name: name, street: address },
  };
  window.dataLayer.push(_icPayload);
  lpCapiFetch({ event_name: 'initiate_checkout', payload: _icPayload });

  const btn=document.getElementById('submitBtn');
  btn.disabled=true; btn.textContent='অর্ডার পাঠানো হচ্ছে...';

  const items = cartItems.map(c => {
    const pkg  = packages[c.idx];
    const disc = unitDiscount(pkg, selectedDelivery);
    return {
      product_id:  pkg.product_id  || null,
      label:       pkg.label,
      kg:          pkg.kg,
      priceNum:    pkg.priceNum,
      discount:    disc,
      finalPrice:  pkg.priceNum - disc,
      qty:         c.qty,
    };
  });

  const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  fetch('{{ route("cart.landing.order") }}', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrfToken,
      'Accept': 'application/json',
    },
    body: JSON.stringify({
      name,
      phone,
      address,
      delivery_method: selectedDelivery,
      payment_method:  payment,
      trx_id:  document.getElementById('fTrx')?.value?.trim() || null,
      note:    document.getElementById('fNote')?.value?.trim() || null,
      items,
      landing_page: 'season_fresh_mango',
    }),
  })
  .then(r => r.json())
  .then(res => {
    if(res.status === 'success'){
      // ── Purchase tracking ───────────────────────────────────────────────────
      const _purId       = lpEventId();
      const _purContents = items.map(i => ({
        id:         String(i.product_id || ''),
        quantity:   i.qty,
        item_price: i.finalPrice,
      }));
      const _purValue = items.reduce((s, i) => s + i.finalPrice * i.qty, 0);
      window.dataLayer.push({
        event: 'purchase',
        event_id: _purId,
        event_source_url: lpCurrentUrl,
        ecommerce: {
          transaction_id: res.order_id,
          value: _purValue,
          currency: 'BDT',
          items: _purContents,
        },
      });
      lpCapiFetch({
        event_name: 'purchase',
    
        event_id: _purId,
        event_source_url: lpCurrentUrl,
        order_id: res.order_id,
        value: _purValue,
        currency: 'BDT',
        contents: _purContents,
        user_data: { phone_number: phone, first_name: name, street: address },
      });

      sndSuccess();
      showSuccessPop(name, phone);
      // Reset form fields
      document.getElementById('fName').value = '';
      document.getElementById('fPhone').value = '';
      document.getElementById('fAddress').value = '';
      document.getElementById('fNote').value = '';
      const trxEl = document.getElementById('fTrx');
      if(trxEl) trxEl.value = '';
      document.getElementById('fPayment').value = '';
      document.getElementById('bkashBox').hidden = true;
      document.querySelectorAll('.pay-opt').forEach(o => o.classList.remove('chosen','pay-bkash'));
      selectedPayment = '';
      // Reset delivery to default
      selectDelivery('home', false);
      // Clear cart
      cartItems = [];
      updateCartCount();
      renderCartSummary();
      document.getElementById('cartBar').classList.remove('show');
      btn.disabled = false;
      btn.textContent = 'অর্ডার নিশ্চিত করুন →';
    } else {
      btn.disabled=false;
      btn.textContent='অর্ডার নিশ্চিত করুন →';
      alert(res.message || 'কিছু একটা ভুল হয়েছে। আবার চেষ্টা করুন।');
    }
  })
  .catch(() => {
    btn.disabled=false;
    btn.textContent='অর্ডার নিশ্চিত করুন →';
    alert('সংযোগে সমস্যা। আবার চেষ্টা করুন।');
  });
}

/* ══════════════════════════════════════
   SCROLL REVEAL
══════════════════════════════════════ */
const observer=new IntersectionObserver(entries=>{
  entries.forEach(e=>{ if(e.isIntersecting){ e.target.classList.add('visible'); observer.unobserve(e.target); } });
},{threshold:.1});
document.querySelectorAll('.reveal,.reveal-stagger').forEach(el=>observer.observe(el));

/* ══════════════════════════════════════
   INIT
══════════════════════════════════════ */
/* ══════════════════════════════════════
   TRACKING — season_fresh_mango
══════════════════════════════════════ */
window.dataLayer = window.dataLayer || [];
const lpCsrf       = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
const lpCurrentUrl = window.location.href;
const lpReferrer   = document.referrer || '';

function lpEventId(){
  return Date.now() + '_' + Math.random().toString(36).substring(2, 9);
}

function lpCapiFetch(body){
  fetch('/fb-pixel-capi', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': lpCsrf },
    body: JSON.stringify(body),
  }).catch(() => {});
}

@if($lp_view_payload)
window.dataLayer.push(@json($lp_view_payload));
@endif

renderPackages();
startTyping();

/* ══════════════════════════════════════
   DEFAULT CART — driven by package.default_selected
══════════════════════════════════════ */
(function initDefaultCart(){
  packages.forEach(function(pkg, idx){
    const sel = pkg.default_selected;
    if (sel === true || sel === 'true') {
      cartItems.push({ idx, qty: parseInt(pkg.default_qty) || 1 });
    }
  });
  updateCartCount();
  renderCartSummary();
  updateDeliveryBadges();
})();
</script>
</body>
</html>
