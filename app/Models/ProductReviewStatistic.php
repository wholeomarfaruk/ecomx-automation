<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductReviewStatistic extends Model
{
    protected $table = 'product_review_statistics';

    protected $fillable = [
        'product_id',
        'total_reviews', 'average_rating',
        'rating_1_count', 'rating_2_count', 'rating_3_count', 'rating_4_count', 'rating_5_count',
        'verified_reviews_count', 'reviews_with_media_count',
    ];

    protected function casts(): array
    {
        return [
            'average_rating' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public static function recalculateFor(int $productId): self
    {
        $approved = ProductReview::query()->forProduct($productId)->approved();

        $counts = (clone $approved)->selectRaw('rating, count(*) as c')->groupBy('rating')->pluck('c', 'rating');

        $total = $counts->sum();
        $average = $total > 0
            ? $counts->reduce(fn ($carry, $c, $rating) => $carry + ($rating * $c), 0) / $total
            : 0;

        return static::updateOrCreate(
            ['product_id' => $productId],
            [
                'total_reviews' => $total,
                'average_rating' => round($average, 2),
                'rating_1_count' => $counts->get(1, 0),
                'rating_2_count' => $counts->get(2, 0),
                'rating_3_count' => $counts->get(3, 0),
                'rating_4_count' => $counts->get(4, 0),
                'rating_5_count' => $counts->get(5, 0),
                'verified_reviews_count' => (clone $approved)->where('is_verified_purchase', true)->count(),
                'reviews_with_media_count' => (clone $approved)->whereHas('media')->count(),
            ]
        );
    }
}
