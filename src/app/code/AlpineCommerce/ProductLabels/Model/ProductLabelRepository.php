<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductLabels\Model;

use AlpineCommerce\ProductLabels\Api\Data\ProductLabelInterface;
use AlpineCommerce\ProductLabels\Api\Data\ProductLabelSearchResultsInterface;
use AlpineCommerce\ProductLabels\Api\Data\ProductLabelSearchResultsInterfaceFactory;
use AlpineCommerce\ProductLabels\Api\ProductLabelRepositoryInterface;
use AlpineCommerce\ProductLabels\Model\ResourceModel\ProductLabel as ResourceModel;
use AlpineCommerce\ProductLabels\Model\ResourceModel\ProductLabel\Collection as ProductLabelCollection;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class ProductLabelRepository implements ProductLabelRepositoryInterface
{
    private const PRODUCT_TABLE = 'alphacommerce_product_label_product';

    public function __construct(
        private readonly ResourceModel $resourceModel,
        private readonly ProductLabelFactory $labelFactory,
        private readonly CollectionProcessorInterface $collectionProcessor,
        private readonly ProductLabelSearchResultsInterfaceFactory $searchResultsFactory,
        private readonly TimezoneInterface $timezone
    ) {
    }

    public function save(ProductLabelInterface $label): ProductLabelInterface
    {
        try {
            $this->resourceModel->save($label);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(
                __('Could not save product label: %1', $e->getMessage()),
                $e
            );
        }

        return $label;
    }

    public function getById(int $entityId): ProductLabelInterface
    {
        /** @var ProductLabelInterface $label */
        $label = $this->labelFactory->create();
        $this->resourceModel->load($label, $entityId);

        if (!$label->getEntityId()) {
            throw new NoSuchEntityException(
                __('Product label with ID "%1" does not exist.', $entityId)
            );
        }

        return $label;
    }

    public function getList(SearchCriteriaInterface $searchCriteria): ProductLabelSearchResultsInterface
    {
        /** @var ProductLabelCollection $collection */
        $collection = $this->labelFactory->create()->getCollection();
        $this->collectionProcessor->process($searchCriteria, $collection);

        /** @var ProductLabelSearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    public function delete(ProductLabelInterface $label): bool
    {
        try {
            $this->resourceModel->delete($label);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(
                __('Could not delete product label: %1', $e->getMessage()),
                $e
            );
        }

        return true;
    }

    public function deleteById(int $entityId): bool
    {
        return $this->delete($this->getById($entityId));
    }

    public function getLabelsByProductId(int $productId): array
    {
        $connection = $this->resourceModel->getConnection();
        $now = $this->timezone->date()->format('Y-m-d H:i:s');

        $select = $connection->select()
            ->from(['pl' => $this->resourceModel->getMainTable()])
            ->joinInner(
                ['plp' => $this->resourceModel->getTable(self::PRODUCT_TABLE)],
                'pl.entity_id = plp.label_id',
                []
            )
            ->where('plp.product_id = ?', $productId)
            ->where('pl.is_active = ?', 1)
            ->where('pl.start_date IS NULL OR pl.start_date <= ?', $now)
            ->where('pl.end_date IS NULL OR pl.end_date >= ?', $now)
            ->order('pl.priority DESC');

        return $connection->fetchAll($select);
    }

    public function assignLabelsToProduct(int $productId, array $labelIds): bool
    {
        $connection = $this->resourceModel->getConnection();
        $tableName = $this->resourceModel->getTable(self::PRODUCT_TABLE);

        $connection->delete($tableName, ['product_id = ?' => $productId]);

        foreach ($labelIds as $labelId) {
            $connection->insert($tableName, [
                'label_id' => (int) $labelId,
                'product_id' => $productId,
            ]);
        }

        return true;
    }

    public function getProductIdsByLabel(int $labelId): array
    {
        $connection = $this->resourceModel->getConnection();

        $select = $connection->select()
            ->from($this->resourceModel->getTable(self::PRODUCT_TABLE), ['product_id'])
            ->where('label_id = ?', $labelId);

        return array_map('intval', $connection->fetchCol($select));
    }

    public function assignProductsToLabel(int $labelId, array $productIds): bool
    {
        $connection = $this->resourceModel->getConnection();
        $tableName = $this->resourceModel->getTable(self::PRODUCT_TABLE);

        $connection->delete($tableName, ['label_id = ?' => $labelId]);

        foreach ($productIds as $productId) {
            $connection->insert($tableName, [
                'label_id' => $labelId,
                'product_id' => (int) $productId,
            ]);
        }

        return true;
    }
}
