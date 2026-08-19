<?php

namespace App\Livewire\EcomxFashion\Sections;

use App\Models\Review;
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

        $approved = Review::approved()->with(['media', 'product']);

        $count = (clone $approved)->count();
        $average = $count > 0 ? (clone $approved)->avg('rating') : null;

        $this->kicker = $average !== null
            ? '★ ' . number_format($average, 1) . ' · ' . number_format($count) . ' ' . ($count === 1 ? 'review' : 'reviews')
            : static::DEFAULT_KICKER;

        $mapped = $approved->latest()
            ->limit(static::LIMIT)
            ->get()
            ->map(fn (Review $review) => $this->mapReview($review))
            ->all();

        $this->reviews = ! empty($mapped) ? $mapped : Catalog::reviews();
    }

    protected function mapReview(Review $review): array
    {
        return [
            'id' => $review->id,
            'name' => $review->name,
            'verified' => true,
            'rating' => $review->rating,
            'product' => $review->product->name ?? '',
            'video' => $review->media?->isVideo() ?? false,
            'img' => $review->media?->getUrl(),
            'avatar' => null,
            'text' => $review->body,
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
