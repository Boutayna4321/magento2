<?php
declare(strict_types=1);

namespace AlpineCommerce\Gdpr\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ConsentLog extends AbstractDb
{
    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init('alphacommerce_gdpr_consent_log', 'entity_id');
    }
}
