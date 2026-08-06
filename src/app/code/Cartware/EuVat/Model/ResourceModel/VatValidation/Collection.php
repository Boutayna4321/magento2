<?php
declare(strict_types=1);

namespace Cartware\EuVat\Model\ResourceModel\VatValidation;

use Cartware\EuVat\Model\ResourceModel\VatValidation as VatValidationResource;
use Cartware\EuVat\Model\VatValidation as VatValidationModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(VatValidationModel::class, VatValidationResource::class);
    }
}
