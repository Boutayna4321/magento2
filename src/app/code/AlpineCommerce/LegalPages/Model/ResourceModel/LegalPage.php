<?php
declare(strict_types=1);

namespace AlpineCommerce\LegalPages\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class LegalPage extends AbstractDb
{
    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init('alphacommerce_legal_page', 'page_id');
    }
}
