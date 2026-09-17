# ecomx-fashion Theme — আর্কিটেকচার ডকুমেন্টেশন

এই ডকুমেন্টের উদ্দেশ্য: `ecomx-fashion` থিম কীভাবে তৈরি হয়েছে তা ব্যাখ্যা করা, যাতে ভবিষ্যতে এই একই প্যাটার্ন অনুসরণ করে **আরেকটা নতুন থিম** বানানো যায়। যেখানেই কোনো অংশ "hardcoded" বা "fragile" — অর্থাৎ দ্বিতীয় থিম বানালে ভাঙতে পারে — সেটা স্পষ্টভাবে চিহ্নিত করা হয়েছে।

---

## ১. High-level ধারণা: Engine vs Theme

এই সিস্টেমে দুটো আলাদা স্তর আছে, গুলিয়ে ফেলা যাবে না:

- **Engine** = রাউটিং লজিক + পেজ Livewire ক্লাসগুলোর সেট। একটা রুট ফাইল (`routes/frontend/{Engine}.php`)। বর্তমানে একটাই active engine থাকে (`config/frontend-engine.php` → `active_engine`)।
- **Theme** = একটা "skin" — views, CSS/SCSS palette, config, assets। একটা `theme.json` manifest দিয়ে ঘোষিত হয়, এবং manifest-এ বলা থাকে সে কোন engine ব্যবহার করে (`manifest.engine`)।

**গুরুত্বপূর্ণ:** একই engine-এ একাধিক theme (skin) থাকতে পারে — তারা Livewire page ক্লাস ও route শেয়ার করবে, শুধু view/CSS/config পাল্টাবে। কিন্তু ভিন্ন page structure বা URL দরকার হলে সেটা নতুন **engine**, নতুন theme নয়।

---

## ২. থিম রেজিস্ট্রেশন মেকানিজম

### Discovery
`App\Support\EcomxFashion\ThemeRegistry::all()` — `resources/*/theme.json` গ্লোব করে খুঁজে বের করে কোন কোন থিম ইনস্টল করা আছে। অর্থাৎ **`resources/` এর নিচে একটা নতুন ফোল্ডার + তার ভেতরে `theme.json` থাকলেই সেটা অটোমেটিক্যালি ডিটেক্ট হয়ে যাবে** — কোথাও ম্যানুয়ালি রেজিস্টার করার দরকার নেই।

### Active theme pointer
- `ThemeRegistry::active()` পড়ে `storage/app/theme.json` (`{"active": "<slug>"}`), না থাকলে fallback করে `config('ecomx-fashion.active_theme')`-এ, তাও না থাকলে প্রথম ইনস্টলড থিমে।
- লেখা হয় `flock()` + temp-file + `rename()` — atomic write প্যাটার্ন।
- **কোনো DB টেবিল নেই।** থিম সিলেকশন গ্লোবাল, per-domain/per-tenant না।
- **সরাসরি `ThemeRegistry::setActive()` কল করা উচিত না** — এর বদলে `App\FrontendEngine\ThemeManager::activate($slug)` ব্যবহার করতে হয়, কারণ সেটা আগে ভ্যালিডেশন চালায়।

### `ActiveTheme` হেল্পার (`app/Support/EcomxFashion/ActiveTheme.php`)
থিম-সম্পর্কিত পাথ বানানোর জন্য একমাত্র সঠিক জায়গা — slug হার্ডকোড না করে এটা ব্যবহার করা উচিত:

```php
ActiveTheme::slug()                     // 'ecomx-fashion'
ActiveTheme::publicPath($rel = '')      // public_path("{slug}/{rel}")
ActiveTheme::resourcePath($rel = '')    // resource_path("{slug}/{rel}")
ActiveTheme::livewireNamespace($suffix) // "{slug}.{suffix}"
```

**নিয়ম:** নতুন থিমের জন্য `config/{slug}.php` ফাইলের নাম অবশ্যই সেই থিমের slug-এর সাথে মিলতে হবে, কারণ প্রায় সব registry `config(ActiveTheme::slug() . '.pages')` প্যাটার্নে কনফিগ পড়ে।

### Engine layer (`app/FrontendEngine/`)
- `EngineManager::engines()` — `routes/frontend/{Engine}.php` গ্লোব করে ইঞ্জিন খুঁজে বের করে।
- `EngineManager::loadActiveThemeRoute()` — active theme-এর manifest থেকে `manifest.engine` বের করে, সেই engine-এর route ফাইল রিকোয়ার করে। **শুধু pre-enumerated engine লিস্ট থেকেই লোড হয়, ইউজার-কন্ট্রোলড পাথ থেকে না** — এটা `routes/web.php`-এ একবার কল হয়।
- `EngineManager::validate($slug)` — একটা থিম activate করার আগে ৫ ধাপে ভ্যালিডেট করে:
  1. manifest পার্স হয় + দরকারি key আছে (`manifest`, `pages`, `layouts`, `routes`)
  2. composer/npm dependency constraint মিলে যায়
  3. manifest-এ ঘোষিত সব view ফাইল আসলেই ডিস্কে আছে
  4. manifest-এ ঘোষিত সব FQCN (Livewire page/layout/section ক্লাস) autoload-যোগ্য
  5. manifest-এ ঘোষিত সব route name আসলেই রেজিস্টার্ড (`Route::has()`)
- `ThemeManager::activate($slug)` — validate পাস করলে, এবং থিমের engine বর্তমান active engine-এর সাথে মিললে তবেই `ThemeRegistry::setActive()` কল করে, তারপর `Cache::tags(['frontend-engine'])` ফ্লাশ করে।

---

## ৩. Views — ডিরেক্টরি স্ট্রাকচার

দুটো view root:

```
resources/views/ecomx-fashion/
├── layouts/ecomx_fashion.blade.php     # মাস্টার লেআউট, data-pal অ্যাট্রিবিউট এখানেই বসে
├── partials/                            # header, footer, mega-menu, bottom-nav, auth-modal, support-modal
├── components/                          # flash-card, icon, product-card, review-slider, section-head, ux-img
├── livewire/                            # প্রতিটা top-level page কম্পোনেন্টের ভিউ (home, shop, category, product, ...)
└── livewire/sections/                   # হোমপেজ সেকশনগুলোর ভিউ (hero, marquee, categories, ...)
    └── skeletons/                       # #[Lazy] সেকশনের লোডিং স্কেলিটন

resources/views/livewire/ecomx-fashion/  # ⚠️ আলাদা root — "chrome" কম্পোনেন্ট (Livewire auto-discovery কনভেনশন মেনে)
├── auth-modal.blade.php
├── cart-manager.blade.php
├── edit-cart-item-variant.blade.php
├── search-modal.blade.php
└── wishlist-drawer.blade.php
```

**কেন দুই জায়গায়?** পেজ/সেকশন কম্পোনেন্টগুলো `render()`-এ explicit `view('ecomx-fashion.livewire.xxx')` রিটার্ন করে থিমের নিজস্ব ফোল্ডারে থাকে। কিন্তু "chrome" কম্পোনেন্ট (মোডাল/ড্রয়ার) Livewire-এর ডিফল্ট auto-discovery কনভেনশন মেনে চলে: `App\Livewire\EcomxFashion\Foo` → `resources/views/livewire/ecomx-fashion/foo.blade.php`।

### Anonymous Blade components — ⚠️ single-theme-only wiring
`app/Providers/AppServiceProvider.php`-এ:

```php
Blade::anonymousComponentPath(resource_path('views/ecomx-fashion/components'), null);
```

`null` prefix মানে `<x-ux-img>`, `<x-product-card>` ইত্যাদি বেয়ার ট্যাগে ব্যবহার হয় (কোনো `x-ecomx-fashion::` প্রিফিক্স নেই)। এটা **হার্ডকোড করা 'ecomx-fashion' স্ট্রিং, `ActiveTheme::slug()` থেকে আসে না**, এবং সব ইনস্টলড থিমের ওপর লুপও করে না। **দ্বিতীয় থিম বানালে:** এই লাইনে নিজের কম্পোনেন্ট পাথ যোগ করতে হবে — কিন্তু `null` প্রিফিক্স দুবার দিলে নাম কলিশনের ঝুঁকি আছে, তাই দ্বিতীয় থিমের জন্য একটা namespace prefix (যেমন `newtheme`) ব্যবহার করা উচিত।

---

## ৪. রাউটিং

- `routes/web.php` শুধু `EngineManager::loadActiveThemeRoute()` কল করে — থিম-নির্দিষ্ট কোনো রাউট নিজে ধারণ করে না।
- `routes/frontend/ecomxFashion.php` — আসল স্টোরফ্রন্ট রাউট:

```php
Route::name('ecomx-fashion.')
    ->middleware([SetFrontendLocale::class, PreventPublicMaintenanceForStaff::class])
    ->group(function () {
        Route::get('/', Home::class)->name('home');
        Route::get('/shop', Shop::class)->name('shop');
        Route::get('/category/{slug?}', Category::class)->name('category');
        Route::get('/product/{slug?}', Product::class)->name('product');
        Route::get('/reviews', Reviews::class)->name('reviews');
        Route::get('/track', Track::class)->name('track')->middleware('block.scope:orders');
        Route::get('/checkout', Checkout::class)->name('checkout')->middleware('block.scope:checkout');
    });
```

- রাউট সাইট রুটে থাকে, **কোনো URL prefix নেই**। রাউট *নাম* (`ecomx-fashion.*`) স্থির থাকে যাতে `route('ecomx-fashion.home')` কল করা কোড অক্ষত থাকে — এই নামগুলো আসলে **engine**-এর, শুধু ঐ থিমের না।
- **নাম-কনভেনশনের গ্যাপ:** route ফাইলের নাম engine-এর নাম (`ecomxFashion`, camelCase), আর থিম slug (`ecomx-fashion`, kebab-case) — দুটো আলাদা identifier scheme, গুলিয়ে ফেলা সহজ।
- **দ্বিতীয় থিম (একই engine-এ skin):** নতুন route ফাইল লাগবে না, একই `routes/frontend/ecomxFashion.php` এবং একই route নাম ব্যবহার হবে।
- **নতুন engine (আলাদা page structure/URL):** নতুন `routes/frontend/{Engine}.php` লাগবে + `config/frontend-engine.php`-এ `active_engine` পাল্টাতে হবে + থিমের `theme.json`-এ `manifest.engine` সেট করতে হবে।

---

## ৫. Page / Section সিস্টেম (Page Builder)

সম্পূর্ণ **ফাইল-ব্যাকড JSON**, কোনো DB টেবিল নেই (categories বাদে)।

### স্তরগুলো

1. **Page metadata (static, code-level)** — `config/ecomx-fashion.php`:
   - `sections` — সেকশন key → Livewire ট্যাগ ম্যাপ, যেমন `'hero' => 'ecomx-fashion.sections.hero'`
   - `pages` — পেজ key → `['label','icon','route','sections' => [...]]`। শুধু `home`-এর সেকশন লিস্ট নন-empty; বাকি পেজ (shop/category/product/...) single-purpose, সেকশন-কম্পোজড না।
   - `App\Support\EcomxFashion\PageRegistry` এই কনফিগের read-only wrapper।

2. **Per-page section active/order state** — `App\Support\EcomxFashion\PageSectionRegistry`
   - Storage: `resources/{theme}/config/page-sections.json`
   - `{key, active, order}` লিস্ট প্রতি পেজে। `activeKeysForPage()` ফ্রন্টএন্ড কী রেন্ডার করবে সেই ক্রম রিটার্ন করে।

3. **Per-section field data** — `App\Support\EcomxFashion\PageSectionConfigRegistry`
   - Storage: `storage/app/public/frontend/{theme}/{page}.{section}.json` (public, symlinked)
   - প্রতি page+section জোড়ার জন্য আলাদা ফাইল, যাতে কনকারেন্ট সেভ race না করে।

4. **Field schema** — `App\Support\EcomxFashion\SectionSchema::fieldsFor($section)`
   - একটা বড় `match` — কোন সেকশনে কী কী ফিল্ড এডিটেবল (`text`, `media_list`, `text_list`, `category_list`, `category_select`, `category_multi_select`, `faq_list`, `stat_list`)।
   - **নতুন থিম এক্সটেন্ড করার প্রধান জায়গা** যদি নতুন সেকশন টাইপ/ফিল্ড দরকার হয়।

5. **Page settings/SEO** — `PageSettingsRegistry` (published/draft) এবং `PageSeoRegistry` (meta_title/meta_description/og_image), একই ফাইল-ব্যাকড প্যাটার্ন।

সব registry-তেই একই idiom বারবার রিপিট হয়: protected `path()`, `read()`, `write()` মেথড, `flock()` + temp-file + `rename()`। **নতুন registry বানালে এই idiom অনুসরণ করা উচিত।**

### Admin UI (`routes/admin.php` → `App\Livewire\Admin\ThemeEngine\*`, `App\Livewire\Admin\Frontend\*`)

| Route | Component | কাজ |
|---|---|---|
| `/admin/frontend` | `Frontend\Pages` | `PageRegistry::all()` লিস্ট করে |
| `/admin/frontend/appearance` | `Frontend\Appearance` → `ThemeEngine\PaletteManager` | প্যালেট পিকার |
| `/admin/frontend/themes` | `Frontend\Themes` → `ThemeEngine\ThemeManager` | থিম activate/validate |
| `/admin/frontend/{page}` | `Frontend\PageShow` → `ThemeEngine\PageSectionManager` | সেকশন toggle/reorder, SEO ট্যাব, publish/draft |
| `/admin/frontend/{page}/{section}/edit` | `ThemeEngine\SectionConfigPage` | `SectionSchema`-ভিত্তিক ডাইনামিক ফর্ম, মিডিয়া পিকার |

⚠️ **রাউট রেজিস্ট্রেশন অর্ডার গুরুত্বপূর্ণ:** `{page}/{section}/edit` অবশ্যই `{page}` wildcard-এর আগে রেজিস্টার করতে হবে, এবং literal রাউট (`appearance`, `themes`, `menus`, `components`) দুটোরই আগে — নাহলে shadow হয়ে যাবে।

---

## ৬. Palette / Design Tokens

- `App\Support\EcomxFashion\PaletteRegistry` — active palette slug, storage `resources/{theme}/config/appearance.json`।
- Master palette লিস্ট: `config/ecomx-fashion.php` → `'palettes' => ['terracotta','rust','midnight','coral','peach','blush','sage','olive','wine','slate']`
- CSS: `resources/ecomx-fashion/scss/base/_palettes.scss` — `[data-pal="..."]` অ্যাট্রিবিউট সিলেক্টর দিয়ে CSS custom properties সংজ্ঞায়িত (`--pri`, `--sec`, `--ac`, ইত্যাদি)।
- **Wiring পয়েন্ট:** `resources/views/ecomx-fashion/layouts/ecomx_fashion.blade.php` লাইন ~২৭:
  ```blade
  <html data-pal="{{ \App\Support\EcomxFashion\PaletteRegistry::active() }}" x-data>
  ```
  এটাই একমাত্র জায়গা যেখানে সার্ভার-সাইড PHP state CSS-visible অ্যাট্রিবিউটে রূপান্তরিত হয়। JS/Alpine এটা override করে না — সাইট-ওয়াইড, ইউজার-ওভাররাইড-অযোগ্য সেটিং হিসেবে ডিজাইন করা।
- এই থিম Tailwind token না, **plain SCSS + CSS custom properties** ব্যবহার করে (যদিও প্রজেক্টের অ্যাডমিন/লিগ্যাসি সাইডে Tailwind আছে)।

### ⚠️ Bug/Inconsistency (দ্বিতীয় থিমে গুরুত্বপূর্ণ)
`PaletteRegistry::active()` এবং `setActive()` সরাসরি `config('ecomx-fashion.palettes')` কল করে — **`ActiveTheme::slug()` দিয়ে প্যারামিটারাইজড না**। এই একটা জায়গা দ্বিতীয় থিম activate করলে ভুল কনফিগ পড়বে। নতুন থিম বানানোর আগে এটা ফিক্স করে `config(ActiveTheme::slug() . '.palettes')` করে নেওয়া ভালো।

### `PaletteRegistry::swatches()`
হাত দিয়ে মেইনটেইন করা PHP array, `_palettes.scss`-এর সাথে **কোনো single source of truth ছাড়াই** সিঙ্কে রাখা হয়। নতুন থিমে ভিন্ন প্যালেট দিলে দুই জায়গায় ম্যানুয়ালি আপডেট করতে হবে।

---

## ৭. Catalog.php — Demo Data (Real DB না)

`App\Support\EcomxFashion\Catalog` স্পষ্টভাবে ডকুমেন্টেড ডেমো/প্লেসহোল্ডার ডেটা:

```php
/**
 * Seldom Fashion — demo catalogue data.
 * Swap these arrays for Eloquent models when wiring a real backend.
 */
class Catalog
```

সব মেথড static, হার্ডকোডেড PHP array রিটার্ন করে (`products()`, `flashSale()`, `reviews()`, `categories()`, `styles()`, `instagram()`, `faqs()`)। অ্যাডমিন কনফিগার করা রিয়েল ডেটা না থাকলে ফলব্যাক হিসেবে ব্যবহার হয়।

**নতুন থিমে সিদ্ধান্ত নিতে হবে:** নিজের একটা `Catalog`-এর মতো ক্লাস লিখবে, নাকি সরাসরি real Eloquent models (`App\Models\Product`, `App\Models\Category`) কোয়েরি করবে — দ্বিতীয়টাই recommended, কারণ ডেমো ডেটা প্যাটার্ন কপি করা প্রোডাকশন-রেডি না।

---

## ৮. Assets / Build

`vite.config.js`-এর `input` array-এ থিম-নির্দিষ্ট এন্ট্রি সরাসরি যোগ করা:

```js
input: [
  'resources/sass/app.scss', 'resources/sass/admin.scss',
  'resources/css/app.css', 'resources/css/admin.css',
  'resources/js/admin.js', 'resources/js/app.js',
  'resources/ecomx-fashion/scss/app.scss',
  'resources/ecomx-fashion/js/app.js',
]
```

**⚠️ হাত দিয়ে মেইনটেইন করা, কোনো auto-discovery নেই।** `theme.json`-এর নিজস্ব `vite` ব্লক (build directory mapping, input list) আছে কিন্তু `vite.config.js` সেটা ডায়নামিক্যালি পড়ে না — এটা একটা gap। **দ্বিতীয় থিম বানালে `vite.config.js`-এ ম্যানুয়ালি দুটো লাইন যোগ করতে হবে:**

```js
'resources/{new-theme-slug}/scss/app.scss',
'resources/{new-theme-slug}/js/app.js',
```

ডিরেক্টরি লেআউট: `resources/ecomx-fashion/{config,js,scss}` — `scss` এর ভেতরে `base/`, `components/`, `pages/`।

---

## ৯. Database

কোনো theme/page/section/palette টেবিল নেই। শুধুমাত্র বাস্তব DB টেবিল যেটা এই সাবসিস্টেম ছোঁয় তা হলো `categories` (category picker field type-এর জন্য, `App\Models\Category` দিয়ে)।

সব state ফাইলে থাকে:
- `storage/app/theme.json` — গ্লোবাল active theme pointer
- `resources/{theme}/config/{page-sections,page-settings,page-seo,appearance}.json`
- `storage/app/public/frontend/{theme}/{page}.{section}.json`

---

## ১০. `theme.json` Manifest — সম্পূর্ণ শেপ

দ্বিতীয় থিম বানানোর সময় এই একই শেপ কপি করে পূরণ করতে হবে (`resources/ecomx-fashion/theme.json` রেফারেন্স):

```json
{
  "manifest": { "id", "name", "schemaVersion", "version", "author", "license", "description", "engine" },
  "preview": { "thumbnail", "cover" },
  "assets": { "sass|js|fonts|images|icons|videos|audio|downloads": { "version", "from", "to" } },
  "components": { "<name>": { "version", "view": { "from", "to" } } },
  "sections": { "<key>": { "version", "component", "type", "source", "multiple" } },
  "pages": { "<key>": { "version", "route", "view", "livewire" } },
  "layouts": { "<key>": { "version", "view", "livewire?" } },
  "routes": { "file": "routes/frontend/{Engine}.php" },
  "sectionComponents": { "version", "namespace", "registry": ["FQCN", ...] },
  "themeSettings": { "defaultPalette", "palettesFile", "paletteAttribute", "paletteStore" },
  "dependencies": { "php", "composer": {...}, "npm": {...}, "modules": [], "addons": [] },
  "vite": { "buildDirectory": { "from", "to" }, "input": [...] },
  "config": { "theme": { "version", "from", "to" } }
}
```

**নোট:** `theme.json`-এ ঘোষিত সব view path, FQCN, এবং route name অবশ্যই বাস্তবে থাকতে হবে — `EngineManager::validate()` এগুলো চেক করে, activate করার সময় মিসম্যাচ থাকলে fail করবে।

---

## ১১. নতুন থিম/ইঞ্জিন সেটআপ — ধাপে ধাপে (Step-by-Step)

নিচে ধাপগুলো ক্রম মেনে লেখা, যাতে প্রতিটা ধাপ আগেরটার ওপর নির্ভর করে বিল্ড করা যায়। `{slug}` = নতুন থিমের kebab-case নাম (যেমন `ecomx-anyecommerce`), `{Engine}` = একই জিনিসের camelCase engine নাম (যেমন `ecomxAnyecommerce`), `{Namespace}` = PHP নেমস্পেসের PascalCase রূপ (যেমন `EcomxAnyecommerce`)।

### A) একই engine-এ নতুন Skin (শুধু ভিউ/CSS/প্যালেট আলাদা, Livewire ক্লাস ও route শেয়ার্ড)

**ধাপ ১ — ডিরেক্টরি তৈরি**
```
resources/{slug}/
resources/{slug}/config/
resources/{slug}/scss/{base,components,pages}/
resources/{slug}/js/
resources/views/{slug}/{layouts,partials,components,livewire,livewire/sections,livewire/sections/skeletons}/
resources/views/livewire/{slug}/
```

**ধাপ ২ — `theme.json` তৈরি**
`resources/{slug}/theme.json` লেখো, সেকশন ১০-এর শেপ অনুসরণ করে। `manifest.engine` বর্তমান active engine-এর নামই রাখো (skin, তাই engine বদলাচ্ছে না)।

**ধাপ ৩ — Config ফাইল তৈরি**
`config/{slug}.php` — `config/ecomx-fashion.php`-এর শেপ হুবহু কপি করো (`active_theme`, `brand`, `domain`, `phone`, `palettes`, `trust`, `sections`, `pages`)। `active_theme` মান অবশ্যই এই `{slug}`-এর সাথে মিলতে হবে।

**ধাপ ৪ — Layout তৈরি**
`resources/views/{slug}/layouts/{slug_snake}.blade.php` — বিদ্যমান `ecomx_fashion.blade.php` কপি করে `data-pal` অ্যাট্রিবিউট, `@vite([...])` এন্ট্রি, এবং `@include`/`@livewire` কলগুলোর ভেতরের path prefix নতুন `{slug}`-এ পাল্টাও।

**ধাপ ৫ — Partials ও Components কপি**
`partials/` (header, footer, mega-menu, bottom-nav, auth-modal, support-modal) এবং `components/` (flash-card, icon, product-card, review-slider, section-head, ux-img) কপি করে নতুন ডিজাইন/কপি বসাও।

**ধাপ ৬ — Palette সংজ্ঞায়িত করা**
`resources/{slug}/scss/base/_palettes.scss` — `[data-pal="..."]` সিলেক্টর দিয়ে কমপক্ষে একটা placeholder প্যালেট (CSS custom properties: `--pri`, `--sec`, `--ac` ইত্যাদি) লেখো।

**ধাপ ৭ — `PaletteRegistry` ফিক্স করা (একবারই, প্রথম দ্বিতীয় থিম বানানোর সময়)**
`app/Support/EcomxFashion/PaletteRegistry.php`-এর `active()` ও `setActive()` মেথডে `config('ecomx-fashion.palettes')` কল দুটোকে `config(ActiveTheme::slug() . '.palettes')`-এ বদলাও (⚠️ সেকশন ৬-এর bug নোট দ্রষ্টব্য — এটা না করলে নতুন থিম activate করলে ভুল প্যালেট কনফিগ পড়বে)। তারপর `swatches()` মেথডে নতুন থিমের প্যালেট এন্ট্রি যোগ করো — `_palettes.scss`-এর সাথে হাতে সিঙ্ক রাখতে হবে।

**ধাপ ৮ — Vite এন্ট্রি রেজিস্টার করা**
`vite.config.js`-এর `input` array-এ যোগ করো:
```js
'resources/{slug}/scss/app.scss',
'resources/{slug}/js/app.js',
```

**ধাপ ৯ — Anonymous Blade component path রেজিস্টার করা**
`app/Providers/AppServiceProvider.php` → `boot()`-এ নতুন লাইন যোগ করো, **namespace prefix সহ** (নাম-কলিশন এড়াতে, `ecomx-fashion`-এর মতো `null` প্রিফিক্স না দিয়ে):
```php
Blade::anonymousComponentPath(resource_path('views/{slug}/components'), '{short-prefix}');
```
এর ফলে এই থিমের ভিউগুলোতে `<x-{short-prefix}::ux-img>` আকারে কম্পোনেন্ট ব্যবহার করতে হবে।

**ধাপ ১০ — Section field schema এক্সটেন্ড করা (যদি দরকার হয়)**
নতুন সেকশন টাইপ/ফিল্ড দরকার হলে `App\Support\EcomxFashion\SectionSchema::fieldsFor()`-এ নতুন `case` যোগ করো। বিদ্যমান সেকশন কম্পোনেন্ট রিইউজ করলে এই ধাপ স্কিপ করা যায়।

**ধাপ ১১ — Demo/placeholder ডেটা**
প্রয়োজনে নিজস্ব ডেমো-ডেটা ক্লাস লেখো (`Catalog`-এর প্যাটার্নে), অথবা সরাসরি real Eloquent models (`Product`, `Category`) কোয়েরি করো — শেষেরটা recommended।

**ধাপ ১২ — Validate ও Activate**
অ্যাডমিন → Frontend → Themes পেজে গিয়ে নতুন থিম তালিকায় দেখা যাচ্ছে কিনা যাচাই করো (এটা অটো-ডিটেক্ট হয়, `resources/*/theme.json` গ্লোব করার কারণে)। "Validate" চালিয়ে `EngineManager::validate()`-এর ৫ ধাপ (manifest, dependencies, files, classes, routes) পাস করছে কিনা দেখো, তারপর "Activate" করো।

---

### B) সম্পূর্ণ নতুন Engine (ভিন্ন URL structure/page flow/Livewire ক্লাস)

উপরের A ধাপ ১–১২ সবগুলোই করতে হবে (নতুন থিমের নিজস্ব views/config/assets/manifest), শুধু "একই engine" ধরে না নিয়ে অতিরিক্ত নিচের ধাপগুলো যোগ হবে। ক্রম অনুযায়ী:

**ধাপ ১৩ — Livewire পেজ ক্লাস তৈরি**
`app/Livewire/{Namespace}/` এ নিজস্ব পেজ ক্লাস লেখো: `Home`, `Shop`, `Category`, `Product`, `Reviews`, `Track`, `Checkout` (+ `Sections/` সাবফোল্ডারে সেকশন কম্পোনেন্ট, + chrome কম্পোনেন্ট: `CartManager`, `WishlistDrawer`, `SearchModal`, `AuthModal`, `EditCartItemVariant`)। প্রতিটাতে `#[Layout('{slug}.layouts.{slug_snake}')]` অ্যাট্রিবিউট এবং `render()`-এ `view('{slug}.livewire.xxx')` রিটার্ন করতে হবে (ধাপ ৪-এর layout এর সাথে মিলিয়ে)।

**ধাপ ১৪ — Route ফাইল তৈরি**
`routes/frontend/{Engine}.php` লেখো — `routes/frontend/ecomxFashion.php`-এর প্যাটার্নে, কিন্তু:
```php
Route::name('{slug}.')
    ->middleware([...])
    ->group(function () {
        Route::get('/', \App\Livewire\{Namespace}\Home::class)->name('home');
        // ... বাকি রুট
    });
```
এই ফাইল বসানো মাত্রই `EngineManager::engines()` এটাকে অটো-ডিটেক্ট করবে (গ্লোব-ভিত্তিক, ম্যানুয়াল রেজিস্ট্রেশন লাগে না)।

**ধাপ ১৫ — `theme.json`-এ engine পয়েন্ট করা**
ধাপ ২-এ বানানো `theme.json`-এর `manifest.engine` ফিল্ডে এই নতুন `{Engine}` নামটা বসাও, এবং `pages`/`layouts`/`sectionComponents.registry`-তে সব FQCN/route নাম ধাপ ১৩-১৪-এর নতুন ক্লাস/রুটের সাথে মিলিয়ে নাও।

**ধাপ ১৬ — Engine activate করা (ঐচ্ছিক, প্রোডাকশনে সতর্কতার সাথে)**
`config/frontend-engine.php` → `active_engine` মান `{Engine}`-এ পরিবর্তন করো — এটা করলেই এই engine-এর route লাইভ হয়ে যাবে এবং আগের engine-এর route নিষ্ক্রিয় হবে (একসাথে শুধু একটাই active engine থাকতে পারে)। এই ধাপ শুধু তখনই করো যখন নতুন engine পুরোপুরি প্রস্তুত ও টেস্ট করা হয়ে গেছে — নাহলে ধাপ ১২-এর "Activate" শুধু থিম activate করবে, engine-স্তরে সুইচ হবে না।

---

## ১২. Key File Index

```
app/Support/EcomxFashion/
  ActiveTheme.php, ThemeRegistry.php, PageRegistry.php, PageSectionRegistry.php,
  PageSectionConfigRegistry.php, SectionSchema.php, PageSettingsRegistry.php,
  PageSeoRegistry.php, PaletteRegistry.php, Catalog.php

app/FrontendEngine/
  EngineManager.php, ThemeManager.php, Engine.php

app/Livewire/Admin/ThemeEngine/
  ThemeManager.php, PageSectionManager.php, SectionConfigPage.php, PaletteManager.php

app/Livewire/Admin/Frontend/
  Pages.php, PageShow.php, Themes.php, Appearance.php, Menus.php, Components.php

app/Livewire/EcomxFashion/**/*.php
  Home, Shop, Category, Product, Reviews, Track, Checkout, CartManager, WishlistDrawer,
  SearchModal, AuthModal, ProductGalleryBuyBox, ProductRelatedCarousel, ProductReviewsSlider,
  EditCartItemVariant, Sections/{Hero,Marquee,Categories,FlashSale,PromoStrip,Trending,ShopByStyle,Reviews,Instagram,WhyFaq}

config/ecomx-fashion.php, config/frontend-engine.php

routes/web.php (engine loader), routes/frontend/ecomxFashion.php (engine routes), routes/admin.php

resources/ecomx-fashion/
  theme.json, config/*.json (runtime state), scss/base/_palettes.scss, js/app.js

resources/views/ecomx-fashion/**
resources/views/livewire/ecomx-fashion/**

app/Providers/AppServiceProvider.php (anonymous component path, ~line 53)
storage/app/theme.json (active theme pointer, runtime-generated)
```

---

## ১৩. জানা সীমাবদ্ধতা (Known Gaps)

দ্বিতীয় থিম বানানোর আগে এই জিনিসগুলো মাথায় রাখা বা ফিক্স করে নেওয়া দরকার:

1. `PaletteRegistry` সরাসরি `'ecomx-fashion'` স্ট্রিং হার্ডকোড করে, `ActiveTheme::slug()` ব্যবহার করে না — দ্বিতীয় থিমে ভুল প্যালেট কনফিগ পড়বে।
2. `AppServiceProvider`-এর anonymous component path হার্ডকোড, `ActiveTheme::slug()`-নির্ভর না, এবং একাধিক থিমের ওপর লুপ করে না।
3. `vite.config.js` ম্যানুয়ালি মেইনটেইন করতে হয়, `theme.json`-এর `vite` ব্লক থেকে অটো-জেনারেট হয় না।
4. `PaletteRegistry::swatches()` এবং `_palettes.scss` — দুই জায়গায় হাতে সিঙ্ক রাখতে হয়, single source of truth নেই।
5. কোনো `Theme` ইন্টারফেস/কন্ট্রাক্ট ক্লাস নেই — "থিম হওয়া" সম্পূর্ণ duck-typed, শুধু `theme.json` শেপ + কনভেনশন মেনে।
6. Route নাম (`ecomx-fashion.*`) engine-এর, থিমের না — থিম switching মূলত "skin" সোয়াপ, পুরো Livewire ক্লাস/রাউট বদলায় না যদি না engine বদলায়।
