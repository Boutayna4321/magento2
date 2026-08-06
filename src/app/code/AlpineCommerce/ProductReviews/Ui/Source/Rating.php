<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductReviews\Ui\Source;

class Rating
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 5, 'label' => __('5 Stars')],
            ['value' => 4, 'label' => __('4 Stars')],
            ['value' => 3, 'label' => __('3 Stars')],
            ['value' => 2, 'label' => __('2 Stars')],
            ['value' => 1, 'label' => __('1 Star')],
        ];
    }
}
