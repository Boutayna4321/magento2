<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductQuestions\Ui\Source;

use AlpineCommerce\ProductQuestions\Model\Status;

class QuestionStatus
{
    public function toOptionArray(): array
    {
        return Status::toOptionArray();
    }
}
