<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductReviews\Ui\Source;

use AlpineCommerce\ProductReviews\Model\Status as StatusModel;
use Magento\Framework\Data\OptionSourceInterface;

class Status implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return StatusModel::toOptionArray();
    }
}
