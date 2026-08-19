@php $cols = [
    'women' => [['Clothing',['Dresses','Outerwear','Knitwear','Trousers','Skirts','Denim']],['Curated',['The Autumn Edit','Workwear Capsule','Evening','Minimal Essentials']],['Featured',['Bestsellers','Back in Stock','Last Chance']]],
    'men' => [['Clothing',['Shirts','Outerwear','Knitwear','Trousers','Denim']],['Curated',['Tailoring','Weekend','Essentials']],['Featured',['Bestsellers','New Arrivals']]],
    'acc' => [['Bags',['Totes','Crossbody','Clutches','Backpacks','Weekenders']],['Accessories',['Belts','Scarves','Sunglasses','Hats','Gloves']],['Featured',['New Arrivals','Bestsellers','The Leather Edit']]],
]; @endphp
<div class="mega__inner">
    <template x-for="col in ({{ json_encode($cols) }})[mega]" :key="col[0]">
        <div style="display:flex;flex-direction:column;gap:14px">
            <span class="kicker" x-text="col[0]"></span>
            <template x-for="link in col[1]" :key="link">
                <a :href="'{{ route('ecomx-fashion.category') }}'" x-text="link" style="font-size:13.5px;color:rgba(var(--pri-rgb),.72)"></a>
            </template>
        </div>
    </template>
    <a href="{{ route('ecomx-fashion.category') }}" style="position:relative;border-radius:14px;overflow:hidden;aspect-ratio:4/3">
        <img src="{{ config('ecomx-fashion.unsplash') }}photo-1445205170230-053b83016050?q=80&w=600&auto=format&fit=crop" alt="The Autumn Edit" style="width:100%;height:100%;object-fit:cover">
        <span style="position:absolute;left:14px;bottom:14px;background:rgba(var(--sec-rgb),.94);padding:8px 14px;border-radius:999px;font-size:12px">The Autumn Edit →</span>
    </a>
</div>
