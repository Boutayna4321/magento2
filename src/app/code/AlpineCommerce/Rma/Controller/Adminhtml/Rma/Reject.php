<?php
/**
 * Copyright (c) AlpineCommerce. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace AlpineCommerce\Rma\Controller\Adminhtml\Rma;

use AlpineCommerce\Rma\Api\Data\RmaInterface;
use Magento\Framework\Controller\Result\Redirect;

class Reject extends AbstractRma
{
    public function execute(): Redirect
    {
        $rmaId = $this->loadRmaIdFromRequest();

        if (!$rmaId) {
            return $this->invalidIdRedirect();
        }

        return $this->changeStatus(
            $rmaId,
            RmaInterface::STATUS_REJECTED,
            __('Return request #%1 has been rejected.', $rmaId)
        );
    }
}
