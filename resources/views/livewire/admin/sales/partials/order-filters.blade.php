{{-- Shared status/payment/source/date filters — used by both the Orders and Ordered Products tabs. Expects $statuses, $paymentStatuses, $sources in scope. --}}
<select wire:model.live="filterStatus"
    class="text-sm rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
    <option value="">All Statuses</option>
    @foreach($statuses as $status)
        <option value="{{ $status->value }}">{{ $status->label() }}</option>
    @endforeach
</select>
<select wire:model.live="filterPaymentStatus"
    class="text-sm rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
    <option value="">All Payment Statuses</option>
    @foreach($paymentStatuses as $status)
        <option value="{{ $status->value }}">{{ $status->label() }}</option>
    @endforeach
</select>
<select wire:model.live="filterSource"
    class="text-sm rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
    <option value="">All Sources</option>
    @foreach($sources as $source)
        <option value="{{ $source->value }}">{{ $source->label() }}</option>
    @endforeach
</select>
<div class="relative">
    <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
    </svg>
    <input wire:model.live="dateFrom" type="text" placeholder="From" readonly
        class="flatpickr-only-date rounded-lg border border-gray-300 bg-white pl-8 pr-3 py-2 text-sm text-gray-700 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition w-32 cursor-pointer">
</div>
<span class="text-gray-400 text-sm">–</span>
<div class="relative">
    <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
    </svg>
    <input wire:model.live="dateTo" type="text" placeholder="To" readonly
        class="flatpickr-only-date rounded-lg border border-gray-300 bg-white pl-8 pr-3 py-2 text-sm text-gray-700 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition w-32 cursor-pointer">
</div>
