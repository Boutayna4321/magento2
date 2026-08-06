<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductReviews\Ui\Source;

use AlpineCommerce\ProductReviews\Model\Status;

class StatusSource
{
    public function toOptionArray(): array
    {
        return Status::toOptionArray();
    }
}
