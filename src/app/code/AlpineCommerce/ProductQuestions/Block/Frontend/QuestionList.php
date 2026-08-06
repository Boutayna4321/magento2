<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductQuestions\Block\Frontend;

use AlpineCommerce\ProductQuestions\Api\Data\AnswerInterface;
use AlpineCommerce\ProductQuestions\Api\Data\QuestionInterface;
use AlpineCommerce\ProductQuestions\Api\QuestionRepositoryInterface;
use AlpineCommerce\ProductQuestions\Api\AnswerRepositoryInterface;
use AlpineCommerce\ProductQuestions\Model\Status;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\View\Element\Template;

class QuestionList extends Template
{
    public function __construct(
        Template\Context $context,
        private readonly QuestionRepositoryInterface $questionRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly SortOrderBuilder $sortOrderBuilder,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly Session $customerSession,
        private readonly AnswerRepositoryInterface $answerRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getProductId(): int
    {
        return (int) $this->getRequest()->getParam('id', 0);
    }

    public function getProductName(): string
    {
        try {
            $product = $this->productRepository->getById($this->getProductId());
            return $product->getName();
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * @return QuestionInterface[]
     */
    public function getQuestions(): array
    {
        $sortOrder = $this->sortOrderBuilder
            ->setField('created_at')
            ->setDirection(SortOrder::SORT_DESC)
            ->create();

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(QuestionInterface::PRODUCT_ID, $this->getProductId(), 'eq')
            ->addFilter(QuestionInterface::STATUS, Status::STATUS_APPROVED, 'eq')
            ->setSortOrders([$sortOrder])
            ->create();

        return $this->questionRepository->getList($searchCriteria)->getItems();
    }

    public function getStatusLabel(int $status): string
    {
        return Status::getLabel($status);
    }

    public function isLoggedIn(): bool
    {
        return $this->customerSession->isLoggedIn();
    }

    /**
     * @return AnswerInterface[]
     */
    public function getAnswersForQuestion(int $questionId): array
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(AnswerInterface::QUESTION_ID, $questionId)
            ->create();

        return $this->answerRepository->getList($searchCriteria)->getItems();
    }
}
