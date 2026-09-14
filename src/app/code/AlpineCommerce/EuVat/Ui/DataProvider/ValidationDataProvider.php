<?php
declare(strict_types=1);

namespace AlpineCommerce\EuVat\Ui\DataProvider;

use AlpineCommerce\EuVat\Model\ResourceModel\VatValidation\Collection;
use AlpineCommerce\EuVat\Model\ResourceModel\VatValidation\CollectionFactory;
use Magento\Ui\DataProvider\AbstractDataProvider;

class ValidationDataProvider extends AbstractDataProvider
{
    private array $loadedData = [];

    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        private readonly CollectionFactory $collectionFactory,
        ?array $meta = null,
        ?array $data = null
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $this->collectionFactory->create();
    }

    public function getData(): array
    {
        $items = $this->collection->getItems();
        foreach ($items as $validation) {
            $this->loadedData[$validation->getEntityId()] = $validation->getData();
        }

        return $this->loadedData;
    }
}
