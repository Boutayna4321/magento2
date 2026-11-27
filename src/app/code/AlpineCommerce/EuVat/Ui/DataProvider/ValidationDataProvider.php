<?php
declare(strict_types=1);

namespace AlpineCommerce\EuVat\Ui\DataProvider;

use AlpineCommerce\EuVat\Model\ResourceModel\VatValidation\CollectionFactory;
use Magento\Ui\DataProvider\AbstractDataProvider;

class ValidationDataProvider extends AbstractDataProvider
{
    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        private readonly CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $this->collectionFactory->create();
    }
}
