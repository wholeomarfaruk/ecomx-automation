<!DOCTYPE html>
<html lang="bn">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('layouts.admin.partials.head-meta')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/sass/admin.scss', 'resources/css/admin.css', 'resources/js/admin.js'])
    @livewireStyles
    <style>
        /* Page base from the design files (<helmet><style>), kept below utility specificity. */
        :where(.acc-profiles) a { color: #4338ca; text-decoration: none; }
        :where(.acc-profiles) a:hover { color: #312e81; }
        :where(.acc-profiles) table { border-collapse: collapse; width: 100%; }
        :where(.acc-profiles) button, :where(.acc-profiles) input, :where(.acc-profiles) select, :where(.acc-profiles) textarea { font-family: inherit; }
        :where(.acc-profiles) button { cursor: pointer; }
        @media print {
            .acc-no-print { display: none !important; }
            .acc-profiles { background: #fff !important; }
        }
    </style>
    @stack('styles')
</head>

{{-- Accounts Panel › profile screens: design sidebar + ProfileTabs bar.
     The root wraps, so on narrow screens the sidebar stacks above the content. --}}
<body class="m-0 bg-[#f1f3f7] antialiased">
    <div class="acc-profiles flex min-h-screen flex-wrap items-stretch bg-[#f1f3f7] font-['Plus_Jakarta_Sans','Hind_Siliguri',sans-serif] text-[14px] text-[#111827]">
        <div class="acc-no-print flex max-w-full flex-[1_1_248px] bg-[#0f172a] lg:max-w-[248px]">
            <x-accounts.sidebar :active="$active ?? 'profiles'" />
        </div>

        <main class="flex min-w-0 flex-[999_1_560px] flex-col">
            <div class="acc-no-print">
                <x-accounts.profile-tabs :active="$tab ?? ($active ?? 'profiles')" />
            </div>

            {{ $slot }}
        </main>
    </div>

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('toast', (data) => {
                Toast.fire({ icon: data[0].type, title: data[0].message });
            });
        });
    </script>
    @livewireScripts
    @stack('scripts')
</body>

</html>
