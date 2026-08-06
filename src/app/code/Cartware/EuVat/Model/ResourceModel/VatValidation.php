<?php
declare(strict_types=1);

namespace Cartware\EuVat\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class VatValidation extends AbstractDb
{
    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init('cartware_vat_validation_log', 'entity_id');
    }
}
