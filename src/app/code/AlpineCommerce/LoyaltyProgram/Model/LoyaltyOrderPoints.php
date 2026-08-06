<?php
declare(strict_types=1);

namespace AlpineCommerce\LoyaltyProgram\Model;

use Magento\Framework\Model\AbstractModel;

class LoyaltyOrderPoints extends AbstractModel
{
    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(\AlpineCommerce\LoyaltyProgram\Model\ResourceModel\LoyaltyOrderPoints::class);
    }
}
