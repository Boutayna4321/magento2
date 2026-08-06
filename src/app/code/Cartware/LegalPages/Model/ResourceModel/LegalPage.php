<?php
declare(strict_types=1);

namespace Cartware\LegalPages\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class LegalPage extends AbstractDb
{
    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init('cartware_legal_page', 'page_id');
    }
}
