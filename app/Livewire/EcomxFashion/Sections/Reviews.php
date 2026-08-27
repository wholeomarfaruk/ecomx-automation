<?php

namespace App\Livewire\EcomxFashion\Sections;

use App\Models\ProductReview;
use App\Support\EcomxFashion\Catalog;
use App\Support\EcomxFashion\PageSectionConfigRegistry;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class Reviews extends Component
{
    protected const LIMIT = 12;
    protected const DEFAULT_HEADING = 'Loved by our customers';
    protected const DEFAULT_LINK_LABEL = 'Read all reviews →';
    protected const DEFAULT_KICKER = '★ 4.8 · 2,314 reviews';

    public array $reviews = [];
    public string $heading = self::DEFAULT_HEADING;
    public string $linkLabel = self::DEFAULT_LINK_LABEL;
    public string $kicker = self::DEFAULT_KICKER;

    public function mount(): void
    {
        $config = PageSectionConfigRegistry::find('home', 'reviews');
        $this->heading = $config['heading'] ?? '' ?: static::DEFAULT_HEADING;
        $this->linkLabel = $config['linkLabel'] ?? '' ?: static::DEFAULT_LINK_LABEL;

        $approved = ProductReview::approved()->with(['media', 'product', 'customer']);

        $count = (clone $approved)->count();
        $average = $count > 0 ? (clone $approved)->avg('rating') : null;

        $this->kicker = $average !== null
            ? '★ ' . number_format($average, 1) . ' · ' . number_format($count) . ' ' . ($count === 1 ? 'review' : 'reviews')
            : static::DEFAULT_KICKER;

        $mapped = $approved->latest()
            ->limit(static::LIMIT)
            ->get()
            ->map(fn (ProductReview $review) => $this->mapReview($review))
            ->all();

        $this->reviews = ! empty($mapped) ? $mapped : Catalog::reviews();
    }

    protected function mapReview(ProductReview $review): array
    {
        $media = $review->media->first();

        return [
            'id' => $review->id,
            'name' => $review->authorName(),
            'verified' => $review->is_verified_purchase,
            'rating' => $review->rating,
            'product' => $review->product->name ?? '',
            'video' => $media?->isVideo() ?? false,
            'img' => $media?->getUrl(),
            'avatar' => null,
            'text' => $review->comment,
        ];
    }

    public function placeholder()
    {
        return view('ecomx-fashion.livewire.sections.skeletons.reviews');
    }

    public function render()
    {
        return view('ecomx-fashion.livewire.sections.reviews', [
            'reviews' => $this->reviews,
        ]);
    }
}
