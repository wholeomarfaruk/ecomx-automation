@php
    $tabs = [
        'dashboard' => 'Dashboard',
        'configuration' => 'Gateway Configuration',
        'templates' => 'Templates',
        'logs' => 'Message Logs',
        'settings' => 'Settings',
        'advanced' => 'Advanced',
    ];
    $currentRoute = Route::currentRouteName();
@endphp

<div
    x-data="{
        isDown: false,
        moved: false,
        startX: 0,
        scrollLeft: 0,
        onWheel(e) {
            if (e.deltaY === 0) return;
            e.preventDefault();
            this.$el.scrollLeft += e.deltaY;
        },
        onMouseDown(e) {
            this.isDown = true;
            this.moved = false;
            this.$el.classList.add('cursor-grabbing');
            this.startX = e.pageX - this.$el.offsetLeft;
            this.scrollLeft = this.$el.scrollLeft;
        },
        onMouseLeaveOrUp() {
            this.isDown = false;
            this.$el.classList.remove('cursor-grabbing');
        },
        onMouseMove(e) {
            if (!this.isDown) return;
            e.preventDefault();
            const x = e.pageX - this.$el.offsetLeft;
            const walk = x - this.startX;
            if (Math.abs(walk) > 5) this.moved = true;
            this.$el.scrollLeft = this.scrollLeft - walk;
        },
        onLinkClick(e) {
            if (this.moved) e.preventDefault();
        },
    }"
    @wheel="onWheel"
    @mousedown="onMouseDown"
    @mouseleave="onMouseLeaveOrUp"
    @mouseup="onMouseLeaveOrUp"
    @mousemove="onMouseMove"
    class="bg-white rounded-2xl shadow-sm border border-gray-200 p-2 flex gap-1 overflow-x-auto scrollbar-none mb-6 cursor-grab select-none">
    @foreach ($tabs as $tab => $label)
        @php
            $routeName = "admin.settings.advance.sms-configuration.{$tab}";
            $active = $currentRoute === $routeName;
        @endphp
        <a href="{{ route($routeName) }}" @click="onLinkClick"
            class="shrink-0 flex items-center gap-2 px-3 py-2.5 rounded-xl text-sm font-medium whitespace-nowrap transition-all
                {{ $active ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-800' }}">
            {{ $label }}
        </a>
    @endforeach
</div>
