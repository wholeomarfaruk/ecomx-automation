<section class="container section" aria-label="Featured" style="margin-top:16px"
    x-data="{
        bigSlides: {{ Illuminate\Support\Js::from($bigSlides) }},
        sideSlides: {{ Illuminate\Support\Js::from($sideSlides) }},
        defaultSideLink: '{{ route('ecomx-fashion.category') }}',
        big:0, side:0,
        bigTimer:null, sideTimer:null,
        dragX:0, dragging:false, draggedPastThreshold:false,
        startBigTimer(){ this.bigTimer=setInterval(()=>this.big=(this.big+1)%this.bigSlides.length,6000); },
        startSideTimer(){ this.sideTimer=setInterval(()=>this.side=(this.side+1)%this.sideSlides.length,7500); },
        init(){ this.startBigTimer(); this.startSideTimer(); },
        dragStart(e){ this.dragging=true; this.draggedPastThreshold=false; this.dragX=(e.touches?e.touches[0].clientX:e.clientX); },
        dragMove(e){
            if (!this.dragging) return;
            let x=(e.touches?e.touches[0].clientX:e.clientX);
            if (Math.abs(x-this.dragX)>10) this.draggedPastThreshold=true;
        },
        dragEnd(e, count, prop, restart){
            if (!this.dragging) return;
            this.dragging=false;
            let x=(e.changedTouches?e.changedTouches[0].clientX:e.clientX);
            let delta=x-this.dragX;
            if (Math.abs(delta)>40) {
                this[prop] = delta<0 ? (this[prop]+1)%count : (this[prop]-1+count)%count;
                clearInterval(this[restart==='big'?'bigTimer':'sideTimer']);
                restart==='big' ? this.startBigTimer() : this.startSideTimer();
            }
        },
    }">
    <div class="hero">
        <div class="hero__main slider-drag"
            @mousedown="dragStart($event)" @mousemove="dragMove($event)" @mouseup="dragEnd($event, bigSlides.length, 'big', 'big')" @mouseleave="dragging=false"
            @touchstart="dragStart($event)" @touchmove="dragMove($event)" @touchend="dragEnd($event, bigSlides.length, 'big', 'big')">
            <template x-for="(slide,i) in bigSlides" :key="'b'+i">
                <img :src="slide.url" alt="" x-show="big===i" x-transition.opacity.duration.1000ms
                     draggable="false"
                     @click="if (!draggedPastThreshold && slide.link) window.location.href = slide.link">
            </template>
            <div class="dots" style="left:clamp(20px,4vw,48px);top:24px">
                <template x-for="(slide,i) in bigSlides" :key="'bd'+i"><button class="dot" :class="big===i && 'is-active'" @click="big=i" :aria-label="'Slide '+(i+1)"></button></template>
            </div>
        </div>
        <div class="hero__side slider-drag"
            @mousedown="dragStart($event)" @mousemove="dragMove($event)" @mouseup="dragEnd($event, sideSlides.length, 'side', 'side')" @mouseleave="dragging=false"
            @touchstart="dragStart($event)" @touchmove="dragMove($event)" @touchend="dragEnd($event, sideSlides.length, 'side', 'side')">
            <template x-for="(slide,i) in sideSlides" :key="'s'+i">
                <a :href="slide.link || defaultSideLink" x-show="side===i" x-transition.opacity.duration.1000ms style="position:absolute;inset:0" @click="if (draggedPastThreshold) $event.preventDefault()">
                    <img :src="slide.url" alt="" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover" draggable="false">
                </a>
            </template>
            <div class="dots" style="left:0;right:0;bottom:16px;justify-content:center">
                <template x-for="(slide,i) in sideSlides" :key="'sd'+i"><button class="dot" :class="side===i && 'is-active'" @click.prevent="side=i" :aria-label="'Banner '+(i+1)"></button></template>
            </div>
        </div>
    </div>
</section>
