<?php
declare(strict_types=1);

namespace AlpineCommerce\Rma\Controller\Adminhtml\Rma;

use AlpineCommerce\Rma\Api\Data\RmaInterface;
use Magento\Framework\Controller\Result\Redirect;

class Close extends AbstractRma
{
    public function execute(): Redirect
    {
        $rmaId = $this->loadRmaIdFromRequest();

        if (!$rmaId) {
            return $this->invalidIdRedirect();
        }

        if ($redirect = $this->requirePost()) {
            return $redirect;
        }

        return $this->changeStatus(
            $rmaId,
            RmaInterface::STATUS_CLOSED,
            __('Return request #%1 has been closed.', $rmaId),
            __('Unable to close the return request.')
        );
    }
}
