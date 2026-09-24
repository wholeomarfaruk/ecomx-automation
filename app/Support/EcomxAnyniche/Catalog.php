<?php

namespace App\Support\EcomxAnyniche;

use App\Models\Product;

/**
 * AnyNiche — demo catalogue data.
 * Swap these arrays for Eloquent models when wiring a real backend.
 */
class Catalog
{
    /**
     * Maps a real Product row to the {id,name,url,image,priceText,...} shape
     * the JTC-style product card (x-anyniche::jtc-product-card) expects —
     * this project's equivalent of the original JTC theme's
     * StorefrontData::decorateEloquentProduct(), adapted to this project's
     * Product schema (sale_price, not discount_price; featured_image
     * accessor, not getImageFullUrl()).
     */
    public static function decorateProduct(Product $product): array
    {
        // Cheapest option after sale price + per-unit offers (Product::cardPricing()).
        ['price' => $price, 'sale' => $salePrice] = $product->cardPricing();
        $isCompare = $salePrice !== null && $price > 0;
        $pct = $isCompare ? (int) round((1 - $salePrice / $price) * 100) : 0;
        $money = fn (float $n) => '৳' . number_format($n);

        return [
            'id' => $product->id,
            'name' => $product->name,
            'url' => route('ecomx-anyniche.product', $product->slug),
            'image' => $product->featured_image ?? asset('images/no-thumbnail.png'),
            'showNew' => false,
            'showDealPct' => $isCompare,
            'pctText' => '-' . $pct . '%',
            'priceIsCompare' => $isCompare,
            'priceText' => $money($isCompare ? $salePrice : $price),
            'compareText' => $isCompare ? $money($price) : '',
            'is_wished' => $product->isWishedBy(request()->attributes->get('device')),
        ];
    }

    public static function products(): array
    {
        // 'demo' => true: placeholder ids that can collide with real product ids,
        // so cards must not look them up (e.g. OfferService offer badges).
        return array_map(fn (array $p) => $p + ['demo' => true], [
            ['id'=>1,'slug'=>'wireless-headphones','name'=>'Wireless Headphones','price'=>4900,'sale'=>null,'tag'=>'New','cat'=>'Electronics','img'=>'photo-1505740420928-5e560c06d30e','colors'=>['#111111','#C8B49A','#6B6F63'],'stock'=>6],
            ['id'=>2,'slug'=>'stainless-water-bottle','name'=>'Stainless Water Bottle','price'=>1200,'sale'=>840,'tag'=>'Flash Sale','cat'=>'Home & Living','img'=>'photo-1602143407151-7111542de6e8','colors'=>['#3E4A3D','#111111'],'stock'=>4],
            ['id'=>3,'slug'=>'canvas-backpack','name'=>'Canvas Backpack','price'=>2950,'sale'=>2210,'tag'=>'Flash Sale','cat'=>'Bags','img'=>'photo-1553062407-98eeb64c6a62','colors'=>['#F1EDE4','#9DB0C4'],'stock'=>9],
            ['id'=>4,'slug'=>'ceramic-mug-set','name'=>'Ceramic Mug Set','price'=>1500,'sale'=>null,'tag'=>'Bestseller','cat'=>'Home & Living','img'=>'photo-1514228742587-6b1558fcca3d','colors'=>['#111111','#8B8578'],'stock'=>0],
            ['id'=>5,'slug'=>'desk-lamp','name'=>'Desk Lamp','price'=>2200,'sale'=>null,'tag'=>'','cat'=>'Electronics','img'=>'photo-1507473885765-e6ed057f782c','colors'=>['#DCCDB8','#111111','#7A5C43'],'stock'=>11],
            ['id'=>6,'slug'=>'leather-wallet','name'=>'Leather Wallet','price'=>1800,'sale'=>1440,'tag'=>'New','cat'=>'Accessories','img'=>'photo-1627123424574-724758594e93','colors'=>['#5C4633','#111111'],'stock'=>3],
            ['id'=>7,'slug'=>'bluetooth-speaker','name'=>'Bluetooth Speaker','price'=>3200,'sale'=>null,'tag'=>'','cat'=>'Electronics','img'=>'photo-1608043152269-423dbba4e7e1','colors'=>['#F1EDE4','#111111'],'stock'=>7],
            ['id'=>8,'slug'=>'yoga-mat','name'=>'Yoga Mat','price'=>1400,'sale'=>980,'tag'=>'Flash Sale','cat'=>'Sports & Fitness','img'=>'photo-1592432678016-e910b452f9a2','colors'=>['#C7A16A','#111111'],'stock'=>0],
            ['id'=>9,'slug'=>'stainless-cookware-set','name'=>'Stainless Cookware Set','price'=>6500,'sale'=>5200,'tag'=>'Flash Sale','cat'=>'Home & Living','img'=>'photo-1584990347449-a5d9f800a783','colors'=>['#3B4A5C','#111111'],'stock'=>8],
            ['id'=>10,'slug'=>'running-shoes','name'=>'Running Shoes','price'=>4200,'sale'=>null,'tag'=>'Bestseller','cat'=>'Sports & Fitness','img'=>'photo-1542291026-7eec264c27ff','colors'=>['#B08968','#111111'],'stock'=>5],
            ['id'=>11,'slug'=>'phone-stand','name'=>'Phone Stand','price'=>650,'sale'=>490,'tag'=>'Flash Sale','cat'=>'Electronics','img'=>'photo-1512499617640-c74ae3a79d37','colors'=>['#FFFFFF','#9DB0C4','#111111'],'stock'=>10],
            ['id'=>12,'slug'=>'travel-duffel-bag','name'=>'Travel Duffel Bag','price'=>3600,'sale'=>null,'tag'=>'','cat'=>'Bags','img'=>'photo-1553062407-98eeb64c6a62','colors'=>['#111111','#C8B49A'],'stock'=>12],
        ]);
    }

    /** products() demo data, mapped to the jtc-product-card shape (used when a category-row/browse-all section has no real products yet). */
    public static function jtcProducts(): array
    {
        return array_map(function (array $p) {
            $isCompare = ! empty($p['sale']);

            return [
                'id' => $p['id'],
                'name' => $p['name'],
                'url' => '#',
                'demo' => true,
                'image' => config('ecomx-anyniche.unsplash') . $p['img'] . '?q=80&w=700&auto=format&fit=crop',
                'showNew' => $p['tag'] === 'New',
                'showDealPct' => $isCompare,
                'pctText' => $isCompare ? '-' . (int) round((1 - $p['sale'] / $p['price']) * 100) . '%' : '',
                'priceIsCompare' => $isCompare,
                'priceText' => '৳' . number_format($isCompare ? $p['sale'] : $p['price']),
                'compareText' => $isCompare ? '৳' . number_format($p['price']) : '',
                'is_wished' => false,
            ];
        }, static::products());
    }

    public static function flashSale(): array
    {
        return [
            ['name'=>'Stainless Water Bottle','off'=>30,'price'=>1200,'sale'=>840,'img'=>'photo-1602143407151-7111542de6e8','colors'=>['#3E4A3D','#111111']],
            ['name'=>'Yoga Mat','off'=>30,'price'=>1400,'sale'=>980,'img'=>'photo-1592432678016-e910b452f9a2','colors'=>['#C7A16A','#111111']],
            ['name'=>'Canvas Backpack','off'=>25,'price'=>2950,'sale'=>2210,'img'=>'photo-1553062407-98eeb64c6a62','colors'=>['#F1EDE4','#9DB0C4']],
            ['name'=>'Stainless Cookware Set','off'=>20,'price'=>6500,'sale'=>5200,'img'=>'photo-1584990347449-a5d9f800a783','colors'=>['#3B4A5C','#111111']],
            ['name'=>'Leather Wallet','off'=>20,'price'=>1800,'sale'=>1440,'img'=>'photo-1627123424574-724758594e93','colors'=>['#5C4633','#111111']],
            ['name'=>'Phone Stand','off'=>25,'price'=>650,'sale'=>490,'img'=>'photo-1512499617640-c74ae3a79d37','colors'=>['#FFFFFF','#9DB0C4','#111111']],
        ];
    }

    public static function reviews(): array
    {
        return [
            ['id'=>1,'name'=>'Sample Customer 1','handle'=>'@customer1','avatar'=>'photo-1494790108377-be9c29b29330','rating'=>5,'date'=>'Jul 12, 2026','verified'=>true,'helpful'=>48,'product'=>'Wireless Headphones','video'=>false,'img'=>'photo-1505740420928-5e560c06d30e','text'=>'Placeholder review text — replace with real customer feedback for this product.'],
            ['id'=>2,'name'=>'Sample Customer 2','handle'=>'@customer2','avatar'=>'photo-1438761681033-6461ffad8d80','rating'=>5,'date'=>'Jul 8, 2026','verified'=>true,'helpful'=>36,'product'=>'Canvas Backpack','video'=>true,'img'=>'photo-1553062407-98eeb64c6a62','text'=>'Placeholder review text — replace with real customer feedback for this product.'],
            ['id'=>3,'name'=>'Sample Customer 3','handle'=>'@customer3','avatar'=>'photo-1500648767791-00dcc994a43e','rating'=>4,'date'=>'Jun 30, 2026','verified'=>true,'helpful'=>21,'product'=>'Bluetooth Speaker','video'=>false,'img'=>'photo-1608043152269-423dbba4e7e1','text'=>'Placeholder review text — replace with real customer feedback for this product.'],
            ['id'=>4,'name'=>'Sample Customer 4','handle'=>'@customer4','avatar'=>'photo-1517841905240-472988babdf9','rating'=>5,'date'=>'Jun 24, 2026','verified'=>true,'helpful'=>63,'product'=>'Running Shoes','video'=>true,'img'=>'photo-1542291026-7eec264c27ff','text'=>'Placeholder review text — replace with real customer feedback for this product.'],
            ['id'=>5,'name'=>'Sample Customer 5','handle'=>'@customer5','avatar'=>'photo-1524250502761-1ac6f2e30d43','rating'=>5,'date'=>'Jun 18, 2026','verified'=>false,'helpful'=>12,'product'=>'Stainless Cookware Set','video'=>false,'img'=>'photo-1584990347449-a5d9f800a783','text'=>'Placeholder review text — replace with real customer feedback for this product.'],
            ['id'=>6,'name'=>'Sample Customer 6','handle'=>'@customer6','avatar'=>'photo-1506794778202-cad84cf45f1d','rating'=>4,'date'=>'Jun 11, 2026','verified'=>true,'helpful'=>17,'product'=>'Yoga Mat','video'=>false,'img'=>'photo-1592432678016-e910b452f9a2','text'=>'Placeholder review text — replace with real customer feedback for this product.'],
            ['id'=>7,'name'=>'Sample Customer 7','handle'=>'@customer7','avatar'=>'photo-1534528741775-53994a69daeb','rating'=>5,'date'=>'Jun 3, 2026','verified'=>true,'helpful'=>29,'product'=>'Travel Duffel Bag','video'=>false,'img'=>'photo-1553062407-98eeb64c6a62','text'=>'Placeholder review text — replace with real customer feedback for this product.'],
            ['id'=>8,'name'=>'Sample Customer 8','handle'=>'@customer8','avatar'=>'photo-1487412720507-e7ab37603c6f','rating'=>5,'date'=>'May 27, 2026','verified'=>true,'helpful'=>41,'product'=>'Desk Lamp','video'=>true,'img'=>'photo-1507473885765-e6ed057f782c','text'=>'Placeholder review text — replace with real customer feedback for this product.'],
        ];
    }

    public static function categories(): array
    {
        return [
            ['name'=>'Electronics','count'=>'128 items','img'=>'photo-1505740420928-5e560c06d30e','slug'=>'electronics'],
            ['name'=>'Home & Living','count'=>'86 items','img'=>'photo-1602143407151-7111542de6e8','slug'=>'home-living'],
            ['name'=>'Accessories','count'=>'42 items','img'=>'photo-1627123424574-724758594e93','slug'=>'accessories'],
        ];
    }

    public static function styles(): array
    {
        return [
            ['name'=>'Essentials','sub'=>'Everyday staples','img'=>'photo-1434389677669-e08b4cac3105','span'=>2],
            ['name'=>'Home Office','sub'=>'Work from anywhere','img'=>'photo-1507003211169-0a1dd7228f2d','span'=>1],
            ['name'=>'Outdoors','sub'=>'Built for the trail','img'=>'photo-1495385794356-15371f348c31','span'=>1],
            ['name'=>'Tech','sub'=>'Latest gadgets','img'=>'photo-1515372039744-b8f02a3ae446','span'=>2],
            ['name'=>'Fitness','sub'=>'Move more','img'=>'photo-1592432678016-e910b452f9a2','span'=>1],
            ['name'=>'The Edit','sub'=>"Staff picks",'img'=>'photo-1469334031218-e382a71b716b','span'=>1],
        ];
    }

    public static function instagram(): array
    {
        return [
            ['img'=>'photo-1505740420928-5e560c06d30e','likes'=>'2.1k'],
            ['img'=>'photo-1602143407151-7111542de6e8','likes'=>'1.8k'],
            ['img'=>'photo-1553062407-98eeb64c6a62','likes'=>'3.4k'],
            ['img'=>'photo-1560243563-062bfc001d68','likes'=>'986'],
            ['img'=>'photo-1534528741775-53994a69daeb','likes'=>'2.7k'],
            ['img'=>'photo-1542291026-7eec264c27ff','likes'=>'1.2k'],
        ];
    }

    public static function faqs(): array
    {
        return [
            ['q'=>'How long does delivery take?','a'=>'Placeholder answer — describe your delivery timelines and any free-shipping thresholds here.'],
            ['q'=>'Which payment methods do you accept?','a'=>'Placeholder answer — list the payment gateways and cash-on-delivery availability for your store.'],
            ['q'=>'What is your return & exchange policy?','a'=>'Placeholder answer — describe your return window and condition requirements.'],
            ['q'=>'How do I find the right size or fit?','a'=>'Placeholder answer — link to a size/spec guide relevant to your catalogue.'],
            ['q'=>'Where are your products from?','a'=>'Placeholder answer — describe sourcing, manufacturing, or quality assurance.'],
            ['q'=>'Is it safe to pay online?','a'=>'Placeholder answer — describe your payment security measures (SSL, gateway certifications, etc.).'],
        ];
    }
}
