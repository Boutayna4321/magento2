<?php
declare(strict_types=1);

namespace AlpineCommerce\Rma\Model;

use Magento\Framework\Model\AbstractModel;

class Rma extends AbstractModel
{
    protected function _construct(): void
    {
        $this->_init(\AlpineCommerce\Rma\Model\ResourceModel\Rma::class);
    }
}
