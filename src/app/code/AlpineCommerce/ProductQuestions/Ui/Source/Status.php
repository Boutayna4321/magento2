<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductQuestions\Ui\Source;

use AlpineCommerce\ProductQuestions\Model\Status as StatusModel;
use Magento\Framework\Data\OptionSourceInterface;

class Status implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return StatusModel::toOptionArray();
    }
}
