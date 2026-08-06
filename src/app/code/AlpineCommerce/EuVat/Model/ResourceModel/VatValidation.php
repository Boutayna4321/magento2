<?php
declare(strict_types=1);

namespace AlpineCommerce\EuVat\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class VatValidation extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('alphacommerce_euvat_validation', 'entity_id');
    }
}
