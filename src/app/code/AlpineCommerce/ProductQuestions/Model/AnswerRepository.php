<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductQuestions\Model;

use AlpineCommerce\ProductQuestions\Api\Data\AnswerInterface;
use AlpineCommerce\ProductQuestions\Api\Data\AnswerSearchResultsInterface;
use AlpineCommerce\ProductQuestions\Api\AnswerRepositoryInterface;
use AlpineCommerce\ProductQuestions\Model\ResourceModel\Answer as AnswerResource;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class AnswerRepository implements AnswerRepositoryInterface
{
    public function __construct(
        private readonly AnswerFactory $answerFactory,
        private readonly AnswerResource $answerResource,
        private readonly CollectionProcessorInterface $collectionProcessor,
        private readonly \AlpineCommerce\ProductQuestions\Api\Data\AnswerSearchResultsInterfaceFactory $searchResultsFactory
    ) {
    }

    public function save(AnswerInterface $answer): AnswerInterface
    {
        try {
            $this->answerResource->save($answer);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save the answer.'), $e);
        }

        return $answer;
    }

    public function getById(int $id): AnswerInterface
    {
        /** @var AnswerInterface $answer */
        $answer = $this->answerFactory->create();
        $this->answerResource->load($answer, $id);

        if (!$answer->getId()) {
            throw new NoSuchEntityException(__('Answer with ID "%1" does not exist.', $id));
        }

        return $answer;
    }

    public function getList(SearchCriteriaInterface $searchCriteria): AnswerSearchResultsInterface
    {
        /** @var \AlpineCommerce\ProductQuestions\Model\ResourceModel\Answer\Collection $collection */
        $collection = $this->answerFactory->create()->getCollection();
        $this->collectionProcessor->process($searchCriteria, $collection);

        /** @var AnswerSearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    public function delete(AnswerInterface $answer): bool
    {
        try {
            $this->answerResource->delete($answer);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__('Could not delete the answer.'), $e);
        }

        return true;
    }

    public function deleteById(int $id): bool
    {
        return $this->delete($this->getById($id));
    }
}
