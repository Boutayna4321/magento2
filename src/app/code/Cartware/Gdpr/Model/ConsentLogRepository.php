<?php
declare(strict_types=1);

namespace Cartware\Gdpr\Model;

use Cartware\Gdpr\Api\ConsentLogRepositoryInterface;
use Cartware\Gdpr\Api\Data\ConsentLogInterface;
use Cartware\Gdpr\Model\ResourceModel\ConsentLog as ConsentLogResource;
use Magento\Framework\Api\Search\SearchResultFactory;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class ConsentLogRepository implements ConsentLogRepositoryInterface
{
    public function __construct(
        private readonly ConsentLogFactory $consentLogFactory,
        private readonly ConsentLogResource $consentLogResource,
        private readonly CollectionProcessorInterface $collectionProcessor,
        private readonly SearchResultFactory $searchResultFactory
    ) {
    }

    /**
     * @param ConsentLogInterface $consentLog
     * @return ConsentLogInterface
     */
    public function save(ConsentLogInterface $consentLog): ConsentLogInterface
    {
        try {
            $this->consentLogResource->save($consentLog);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save the consent log entry.'), $e);
        }

        return $consentLog;
    }

    /**
     * @param int $id
     * @return ConsentLogInterface
     */
    public function getById(int $id): ConsentLogInterface
    {
        /** @var ConsentLogInterface $consentLog */
        $consentLog = $this->consentLogFactory->create();
        $this->consentLogResource->load($consentLog, $id);

        if (!$consentLog->getId()) {
            throw new NoSuchEntityException(__('Consent log entry with ID "%1" does not exist.', $id));
        }

        return $consentLog;
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return \Magento\Framework\Api\SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        /** @var \Cartware\Gdpr\Model\ResourceModel\ConsentLog\Collection $collection */
        $collection = $this->consentLogFactory->create()->getCollection();
        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->searchResultFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }
}
