<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductLabels\Model;

use AlpineCommerce\ProductLabels\Api\Data\ProductLabelInterface;
use AlpineCommerce\ProductLabels\Api\Data\ProductLabelSearchResultsInterface;
use AlpineCommerce\ProductLabels\Api\ProductLabelRepositoryInterface;
use AlpineCommerce\ProductLabels\Model\ResourceModel\ProductLabel as ResourceModel;
use AlpineCommerce\ProductLabels\Model\ResourceModel\ProductLabel\Collection as ProductLabelCollection;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class ProductLabelRepository implements ProductLabelRepositoryInterface
{
    public function __construct(
        private readonly ResourceModel $resourceModel,
        private readonly ProductLabelFactory $labelFactory,
        private readonly TimezoneInterface $timezone
    ) {}

    public function save(ProductLabelInterface $label): ProductLabelInterface
    {
        try {
            $this->resourceModel->save($label);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__("Could not save product label: %1", $e->getMessage()));
        }
        return $label;
    }

    public function getById(int $entityId): ProductLabelInterface
    {
        $label = $this->labelFactory->create();
        $this->resourceModel->load($label, $entityId);
        if (!$label->getEntityId()) {
            throw new NoSuchEntityException(__("Product label with ID %1 does not exist", $entityId));
        }
        return $label;
    }

    public function getList(SearchCriteriaInterface $searchCriteria): ProductLabelSearchResultsInterface
    {
        $searchResults = \AlpineCommerce\ProductLabels\Api\Result\ProductLabelSearchResults::create();
        
        $collection = $this->labelFactory->create()->getCollection();
        
        foreach ($searchCriteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                $collection->addFieldToFilter($filter->getField(), $filter->getCondition());
            }
        }
        
        $searchResults->setTotalCount($collection->getSize());
        $searchResults->setItems($collection->getItems());
        
        return $searchResults;
    }

    public function delete(ProductLabelInterface $label): bool
    {
        try {
            $this->resourceModel->delete($label);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__("Could not delete product label: %1", $e->getMessage()));
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
        $select = $connection->select()
            ->from(["pl" => $this->resourceModel->getMainTable()])
            ->join(
                ["plp" => $this->resourceModel->getTable("alphacommerce_product_label_product")],
                "pl.entity_id = plp.label_id",
                []
            )
            ->where("plp.product_id = ?", $productId)
            ->where("pl.is_active = ?", 1)
            ->where("pl.start_date IS NULL OR pl.start_date <= ?", $this->timezone->date()->format("Y-m-d H:i:s"))
            ->where("pl.end_date IS NULL OR pl.end_date >= ?", $this->timezone->date()->format("Y-m-d H:i:s"))
            ->order("pl.priority DESC");
        
        return $connection->fetchAll($select);
    }

    public function assignLabelsToProduct(int $productId, array $labelIds): bool
    {
        $connection = $this->resourceModel->getConnection();
        $tableName = $this->resourceModel->getTable("alphacommerce_product_label_product");
        
        $connection->delete($tableName, ["product_id = ?" => $productId]);
        
        foreach ($labelIds as $labelId) {
            $connection->insert($tableName, [
                "label_id" => $labelId,
                "product_id" => $productId
            ]);
        }
        
        return true;
    }
}
