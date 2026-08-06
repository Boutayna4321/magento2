<?php
declare(strict_types=1);

namespace AlpineCommerce\Faq\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Faq extends AbstractDb
{
    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init('alphacommerce_faq', 'faq_id');
    }
}
