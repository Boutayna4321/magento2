<?php
declare(strict_types=1);

namespace AlpineCommerce\EuVat\Model\ResourceModel\VatValidation;

use AlpineCommerce\EuVat\Model\ResourceModel\VatValidation as VatValidationResource;
use AlpineCommerce\EuVat\Model\VatValidation as VatValidationModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct(): void
    {
        $this->_init(VatValidationModel::class, VatValidationResource::class);
    }
}
