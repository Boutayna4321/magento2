<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductQuestions\Model;

use AlpineCommerce\ProductQuestions\Api\Data\QuestionInterface;
use AlpineCommerce\ProductQuestions\Api\Data\QuestionSearchResultsInterface;
use AlpineCommerce\ProductQuestions\Api\QuestionRepositoryInterface;
use AlpineCommerce\ProductQuestions\Model\ResourceModel\Question as QuestionResource;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class QuestionRepository implements QuestionRepositoryInterface
{
    public function __construct(
        private readonly QuestionFactory $questionFactory,
        private readonly QuestionResource $questionResource,
        private readonly CollectionProcessorInterface $collectionProcessor,
        private readonly \AlpineCommerce\ProductQuestions\Api\Data\QuestionSearchResultsInterfaceFactory $searchResultsFactory
    ) {
    }

    public function save(QuestionInterface $question): QuestionInterface
    {
        try {
            $this->questionResource->save($question);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save the product question.'), $e);
        }

        return $question;
    }

    public function getById(int $id): QuestionInterface
    {
        /** @var QuestionInterface $question */
        $question = $this->questionFactory->create();
        $this->questionResource->load($question, $id);

        if (!$question->getId()) {
            throw new NoSuchEntityException(__('Product question with ID "%1" does not exist.', $id));
        }

        return $question;
    }

    public function getList(SearchCriteriaInterface $searchCriteria): QuestionSearchResultsInterface
    {
        /** @var \AlpineCommerce\ProductQuestions\Model\ResourceModel\Question\Collection $collection */
        $collection = $this->questionFactory->create()->getCollection();
        $this->collectionProcessor->process($searchCriteria, $collection);

        /** @var QuestionSearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    public function delete(QuestionInterface $question): bool
    {
        try {
            $this->questionResource->delete($question);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__('Could not delete the product question.'), $e);
        }

        return true;
    }

    public function deleteById(int $id): bool
    {
        return $this->delete($this->getById($id));
    }
}
