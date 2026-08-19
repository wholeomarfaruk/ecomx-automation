<?php

namespace App\Support\EcomxFashion;

/**
 * Seldom Fashion — demo catalogue data.
 * Swap these arrays for Eloquent models when wiring a real backend.
 */
class Catalog
{
    public static function products(): array
    {
        return [
            ['id'=>1,'slug'=>'sculpted-wool-coat','name'=>'Sculpted Wool Coat','price'=>12900,'sale'=>null,'tag'=>'New','cat'=>'Outerwear','img'=>'photo-1539109136881-3be0616acf4b','colors'=>['#C8B49A','#111111','#6B6F63'],'stock'=>6],
            ['id'=>2,'slug'=>'silk-slip-dress','name'=>'Silk Slip Dress','price'=>7900,'sale'=>5530,'tag'=>'Flash Sale','cat'=>'Dresses','img'=>'photo-1515886657613-9f3515b0c78f','colors'=>['#3E4A3D','#111111'],'stock'=>4],
            ['id'=>3,'slug'=>'relaxed-linen-shirt','name'=>'Relaxed Linen Shirt','price'=>3950,'sale'=>2960,'tag'=>'Flash Sale','cat'=>'Shirts','img'=>'photo-1495385794356-15371f348c31','colors'=>['#F1EDE4','#9DB0C4'],'stock'=>9],
            ['id'=>4,'slug'=>'tailored-trouser','name'=>'Tailored Trouser','price'=>5400,'sale'=>null,'tag'=>'Bestseller','cat'=>'Trousers','img'=>'photo-1594633312681-425c7b97ccd1','colors'=>['#111111','#8B8578'],'stock'=>0],
            ['id'=>5,'slug'=>'cashmere-crew-knit','name'=>'Cashmere Crew Knit','price'=>6900,'sale'=>null,'tag'=>'','cat'=>'Knitwear','img'=>'photo-1487412720507-e7ab37603c6f','colors'=>['#DCCDB8','#111111','#7A5C43'],'stock'=>11],
            ['id'=>6,'slug'=>'leather-city-tote','name'=>'Leather City Tote','price'=>10500,'sale'=>8400,'tag'=>'New','cat'=>'Bags','img'=>'photo-1548036328-c9fa89d128fa','colors'=>['#5C4633','#111111'],'stock'=>3],
            ['id'=>7,'slug'=>'structured-blazer','name'=>'Structured Blazer','price'=>9200,'sale'=>null,'tag'=>'','cat'=>'Outerwear','img'=>'photo-1509631179647-0177331693ae','colors'=>['#F1EDE4','#111111'],'stock'=>7],
            ['id'=>8,'slug'=>'pleated-midi-skirt','name'=>'Pleated Midi Skirt','price'=>4900,'sale'=>2940,'tag'=>'Flash Sale','cat'=>'Skirts','img'=>'photo-1524504388940-b1c1722653e1','colors'=>['#C7A16A','#111111'],'stock'=>0],
            ['id'=>9,'slug'=>'raw-denim-jean','name'=>'Raw Denim Jean','price'=>4600,'sale'=>3220,'tag'=>'Flash Sale','cat'=>'Denim','img'=>'photo-1566174053879-31528523f8ae','colors'=>['#3B4A5C','#111111'],'stock'=>8],
            ['id'=>10,'slug'=>'sculpt-leather-heel','name'=>'Sculpt Leather Heel','price'=>6200,'sale'=>null,'tag'=>'Bestseller','cat'=>'Shoes','img'=>'photo-1543163521-1bf539c55dd2','colors'=>['#B08968','#111111'],'stock'=>5],
            ['id'=>11,'slug'=>'cotton-oxford-shirt','name'=>'Cotton Oxford Shirt','price'=>3100,'sale'=>2325,'tag'=>'Flash Sale','cat'=>'Shirts','img'=>'photo-1552374196-c4e7ffc6e126','colors'=>['#FFFFFF','#9DB0C4','#111111'],'stock'=>10],
            ['id'=>12,'slug'=>'wide-leg-trouser','name'=>'Wide-Leg Trouser','price'=>5600,'sale'=>null,'tag'=>'','cat'=>'Trousers','img'=>'photo-1529139574466-a303027c1d8b','colors'=>['#111111','#C8B49A'],'stock'=>12],
        ];
    }

    public static function flashSale(): array
    {
        return [
            ['name'=>'Silk Slip Dress','off'=>30,'price'=>7900,'sale'=>5530,'img'=>'photo-1515886657613-9f3515b0c78f','colors'=>['#3E4A3D','#111111']],
            ['name'=>'Pleated Midi Skirt','off'=>40,'price'=>4900,'sale'=>2940,'img'=>'photo-1524504388940-b1c1722653e1','colors'=>['#C7A16A','#111111']],
            ['name'=>'Relaxed Linen Shirt','off'=>25,'price'=>3950,'sale'=>2960,'img'=>'photo-1495385794356-15371f348c31','colors'=>['#F1EDE4','#9DB0C4']],
            ['name'=>'Raw Denim Jean','off'=>30,'price'=>4600,'sale'=>3220,'img'=>'photo-1566174053879-31528523f8ae','colors'=>['#3B4A5C','#111111']],
            ['name'=>'Sculpt Leather Heel','off'=>35,'price'=>6200,'sale'=>4030,'img'=>'photo-1543163521-1bf539c55dd2','colors'=>['#B08968','#111111']],
            ['name'=>'Cotton Oxford Shirt','off'=>25,'price'=>3100,'sale'=>2325,'img'=>'photo-1552374196-c4e7ffc6e126','colors'=>['#FFFFFF','#9DB0C4','#111111']],
            ['name'=>'Leather City Tote','off'=>20,'price'=>10500,'sale'=>8400,'img'=>'photo-1548036328-c9fa89d128fa','colors'=>['#5C4633','#111111']],
            ['name'=>'Wide-Leg Trouser','off'=>30,'price'=>5600,'sale'=>3920,'img'=>'photo-1529139574466-a303027c1d8b','colors'=>['#111111','#C8B49A']],
        ];
    }

    public static function reviews(): array
    {
        return [
            ['id'=>1,'name'=>'Nusrat Jahan','handle'=>'@nusrat.j','avatar'=>'photo-1494790108377-be9c29b29330','rating'=>5,'date'=>'Jul 12, 2026','verified'=>true,'helpful'=>48,'product'=>'Sculpted Wool Coat','video'=>false,'img'=>'photo-1483985988355-763728e1935b','text'=>'The tailoring is unreal for this price point. Shoulders sit perfectly, and the camel shade goes with everything I own.'],
            ['id'=>2,'name'=>'Tasnim Chowdhury','handle'=>'@tasnim.c','avatar'=>'photo-1438761681033-6461ffad8d80','rating'=>5,'date'=>'Jul 8, 2026','verified'=>true,'helpful'=>36,'product'=>'Silk Slip Dress','video'=>true,'img'=>'photo-1515372039744-b8f02a3ae446','text'=>'Wore it to a wedding in Sylhet — the silk drapes beautifully and did not wrinkle once. Full look in the video.'],
            ['id'=>3,'name'=>'Farhan Ahmed','handle'=>'@farhan.a','avatar'=>'photo-1500648767791-00dcc994a43e','rating'=>4,'date'=>'Jun 30, 2026','verified'=>true,'helpful'=>21,'product'=>'Cotton Oxford Shirt','video'=>false,'img'=>'photo-1552374196-c4e7ffc6e126','text'=>'Crisp, structured, washes well. Size up if you are between sizes — I am wearing the M here.'],
            ['id'=>4,'name'=>'Ayesha Karim','handle'=>'@ayesha.wears','avatar'=>'photo-1517841905240-472988babdf9','rating'=>5,'date'=>'Jun 24, 2026','verified'=>true,'helpful'=>63,'product'=>'Structured Blazer','video'=>true,'img'=>'photo-1509631179647-0177331693ae','text'=>'Third piece from Seldom Fashion and the consistency is what keeps me coming back. Office-to-dinner perfect.'],
            ['id'=>5,'name'=>'Sadia Islam','handle'=>'@sadia.i','avatar'=>'photo-1524250502761-1ac6f2e30d43','rating'=>5,'date'=>'Jun 18, 2026','verified'=>false,'helpful'=>12,'product'=>'Pleated Midi Skirt','video'=>false,'img'=>'photo-1524504388940-b1c1722653e1','text'=>'The pleats hold their shape all day. Paired with the cashmere crew it is my entire autumn uniform.'],
            ['id'=>6,'name'=>'Tanvir Hasan','handle'=>'@t.hasan','avatar'=>'photo-1506794778202-cad84cf45f1d','rating'=>4,'date'=>'Jun 11, 2026','verified'=>true,'helpful'=>17,'product'=>'Raw Denim Jean','video'=>false,'img'=>'photo-1566174053879-31528523f8ae','text'=>'Heavyweight denim that breaks in beautifully. Runs slightly long — I had mine hemmed.'],
            ['id'=>7,'name'=>'Mehnaz Rahman','handle'=>'@mehnazr','avatar'=>'photo-1534528741775-53994a69daeb','rating'=>5,'date'=>'Jun 3, 2026','verified'=>true,'helpful'=>29,'product'=>'Leather City Tote','video'=>false,'img'=>'photo-1548036328-c9fa89d128fa','text'=>'Fits a 13-inch laptop, smells like a proper leather workshop, and the hardware feels substantial. Zero regrets.'],
            ['id'=>8,'name'=>'Noor Fatima','handle'=>'@noorf','avatar'=>'photo-1487412720507-e7ab37603c6f','rating'=>5,'date'=>'May 27, 2026','verified'=>true,'helpful'=>41,'product'=>'Cashmere Crew Knit','video'=>true,'img'=>'photo-1539109136881-3be0616acf4b','text'=>'Softest knit I own. No pilling after two months of weekly wear — see how it moves in the clip.'],
        ];
    }

    public static function categories(): array
    {
        return [
            ['name'=>'Women','count'=>'128 pieces','img'=>'photo-1539109136881-3be0616acf4b','slug'=>'women'],
            ['name'=>'Men','count'=>'86 pieces','img'=>'photo-1552374196-c4e7ffc6e126','slug'=>'men'],
            ['name'=>'Accessories','count'=>'42 pieces','img'=>'photo-1548036328-c9fa89d128fa','slug'=>'accessories'],
        ];
    }

    public static function styles(): array
    {
        return [
            ['name'=>'Minimal','sub'=>'Clean lines, quiet colour','img'=>'photo-1434389677669-e08b4cac3105','span'=>2],
            ['name'=>'Tailored','sub'=>'Sharp, considered','img'=>'photo-1507003211169-0a1dd7228f2d','span'=>1],
            ['name'=>'Weekend','sub'=>'Ease, off-duty','img'=>'photo-1495385794356-15371f348c31','span'=>1],
            ['name'=>'Evening','sub'=>'Silk after seven','img'=>'photo-1515372039744-b8f02a3ae446','span'=>2],
            ['name'=>'Denim','sub'=>'Raw & rigid','img'=>'photo-1566174053879-31528523f8ae','span'=>1],
            ['name'=>'The Edit','sub'=>"Stylists' picks",'img'=>'photo-1469334031218-e382a71b716b','span'=>1],
        ];
    }

    public static function instagram(): array
    {
        return [
            ['img'=>'photo-1529139574466-a303027c1d8b','likes'=>'2.1k'],
            ['img'=>'photo-1524504388940-b1c1722653e1','likes'=>'1.8k'],
            ['img'=>'photo-1487412720507-e7ab37603c6f','likes'=>'3.4k'],
            ['img'=>'photo-1560243563-062bfc001d68','likes'=>'986'],
            ['img'=>'photo-1534528741775-53994a69daeb','likes'=>'2.7k'],
            ['img'=>'photo-1543163521-1bf539c55dd2','likes'=>'1.2k'],
        ];
    }

    public static function faqs(): array
    {
        return [
            ['q'=>'How long does delivery take in Bangladesh?','a'=>'Inside Dhaka we deliver in 24–48 hours. Nationwide delivery takes 3–5 working days. Orders over ৳5,000 ship free.'],
            ['q'=>'Which payment methods do you accept?','a'=>'bKash, Nagad, Visa, Mastercard and Cash on Delivery. COD is available everywhere in Bangladesh with no extra charge.'],
            ['q'=>'What is your return & exchange policy?','a'=>'30 days, free of charge. Items must be unworn with tags attached. Request a return from your account or call us.'],
            ['q'=>'How do I find my size?','a'=>'Every product page has a detailed size guide with measurements in cm. Our fits run true — size down for a closer fit.'],
            ['q'=>'Are your pieces really made in Bangladesh?','a'=>'Yes — designed and finished in our Dhaka atelier, using Italian wool, silk and premium cottons.'],
            ['q'=>'Is it safe to pay online?','a'=>'Completely. Payments run through bKash and Nagad official gateways with SSL encryption. Or choose Cash on Delivery.'],
        ];
    }
}
