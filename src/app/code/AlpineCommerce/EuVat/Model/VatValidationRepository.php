<?php
declare(strict_types=1);

namespace AlpineCommerce\EuVat\Model;

use AlpineCommerce\EuVat\Api\VatValidationRepositoryInterface;
use AlpineCommerce\EuVat\Api\Data\VatValidationInterface;
use AlpineCommerce\EuVat\Model\ResourceModel\VatValidation as VatValidationResource;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class VatValidationRepository implements VatValidationRepositoryInterface
{
    public function __construct(
        private readonly VatValidationFactory $vatValidationFactory,
        private readonly VatValidationResource $vatValidationResource,
        private readonly CollectionProcessorInterface $collectionProcessor,
        private readonly \AlpineCommerce\EuVat\Api\Data\VatValidationSearchResultsInterfaceFactory $searchResultsFactory
    ) {
    }

    public function save(VatValidationInterface $vatValidation): VatValidationInterface
    {
        try {
            $this->vatValidationResource->save($vatValidation);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save the VAT validation.'), $e);
        }

        return $vatValidation;
    }

    public function getById(int $id): VatValidationInterface
    {
        $vatValidation = $this->vatValidationFactory->create();
        $this->vatValidationResource->load($vatValidation, $id);

        if (!$vatValidation->getEntityId()) {
            throw new NoSuchEntityException(__('VAT validation with ID "%1" does not exist.', $id));
        }

        return $vatValidation;
    }

    public function getList(SearchCriteriaInterface $searchCriteria): \AlpineCommerce\EuVat\Api\Data\VatValidationSearchResultsInterface
    {
        $collection = $this->vatValidationFactory->create()->getCollection();
        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    public function delete(VatValidationInterface $vatValidation): bool
    {
        try {
            $this->vatValidationResource->delete($vatValidation);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__('Could not delete the VAT validation.'), $e);
        }

        return true;
    }
}
