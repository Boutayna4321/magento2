<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductQuestions\Model\Rest;

use AlpineCommerce\ProductQuestions\Api\Data\AnswerInterface;
use AlpineCommerce\ProductQuestions\Api\Data\QuestionInterface;
use AlpineCommerce\ProductQuestions\Api\Data\QuestionSearchResultsInterface;
use AlpineCommerce\ProductQuestions\Api\QuestionRepositoryInterface;
use AlpineCommerce\ProductQuestions\Api\QuestionRestInterface;
use AlpineCommerce\ProductQuestions\Api\AnswerRepositoryInterface;
use AlpineCommerce\ProductQuestions\Model\QuestionFactory;
use AlpineCommerce\ProductQuestions\Model\AnswerFactory;
use AlpineCommerce\ProductQuestions\Model\Status;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;

class QuestionRestService implements QuestionRestInterface
{
    public function __construct(
        private readonly QuestionRepositoryInterface $questionRepository,
        private readonly AnswerRepositoryInterface $answerRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly QuestionFactory $questionFactory,
        private readonly AnswerFactory $answerFactory,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly RequestInterface $request,
        private readonly UserContextInterface $userContext,
        private readonly \AlpineCommerce\ProductQuestions\Model\ResourceModel\Vote $voteResource,
        private readonly \AlpineCommerce\ProductQuestions\Api\Data\VoteInterfaceFactory $voteFactory
    ) {
    }

    public function getQuestions(int $productId, int $page = 1, int $pageSize = 20): QuestionSearchResultsInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(QuestionInterface::PRODUCT_ID, $productId, 'eq')
            ->addFilter(QuestionInterface::STATUS, Status::STATUS_APPROVED, 'eq')
            ->setPageSize($pageSize)
            ->setCurrentPage(max(1, $page))
            ->create();

        return $this->questionRepository->getList($searchCriteria);
    }

    public function getQuestion(int $questionId): QuestionInterface
    {
        $question = $this->questionRepository->getById($questionId);

        if ($question->getStatus() !== Status::STATUS_APPROVED) {
            throw new NoSuchEntityException(
                __('The product question with ID "%1" does not exist.', $questionId)
            );
        }

        return $question;
    }

    public function addQuestion(int $productId, string $question): QuestionInterface
    {
        $customerId = null;
        $customerName = null;
        $customerEmail = null;

        try {
            $currentCustomerId = $this->userContext->getUserId();
            if ($currentCustomerId !== null) {
                $customer = $this->customerRepository->getById((int) $currentCustomerId);
                $customerId = $customer->getId();
                $customerName = $customer->getFirstname() . ' ' . $customer->getLastname();
                $customerEmail = $customer->getEmail();
            }
        } catch (\Exception $e) {
            $customerId = null;
        }

        /** @var QuestionInterface $questionModel */
        $questionModel = $this->questionFactory->create();
        $questionModel->setProductId($productId);
        $questionModel->setCustomerId($customerId !== null ? (int) $customerId : null);
        $questionModel->setCustomerName($customerName);
        $questionModel->setCustomerEmail($customerEmail);
        $questionModel->setQuestion($question);
        $questionModel->setStatus(Status::STATUS_PENDING);
        $questionModel->setIsVerifiedPurchase(false);
        $questionModel->setHelpfulCount(0);

        return $this->questionRepository->save($questionModel);
    }

    public function editQuestion(int $questionId, string $question): QuestionInterface
    {
        $customerId = $this->getCurrentCustomerId();
        $existingQuestion = $this->questionRepository->getById($questionId);

        if ($existingQuestion->getCustomerId() === null || $existingQuestion->getCustomerId() !== $customerId) {
            throw new StateException(__('You can only edit your own questions.'));
        }

        if ($existingQuestion->getStatus() !== Status::STATUS_PENDING) {
            throw new StateException(__('You can only edit questions that are pending approval.'));
        }

        $existingQuestion->setQuestion($question);
        return $this->questionRepository->save($existingQuestion);
    }

    public function deleteQuestion(int $questionId): bool
    {
        $customerId = $this->getCurrentCustomerId();
        $existingQuestion = $this->questionRepository->getById($questionId);

        if ($existingQuestion->getCustomerId() === null || $existingQuestion->getCustomerId() !== $customerId) {
            throw new StateException(__('You can only delete your own questions.'));
        }

        if ($existingQuestion->getStatus() !== Status::STATUS_PENDING) {
            throw new StateException(__('You can only delete questions that are pending approval.'));
        }

        return $this->questionRepository->delete($existingQuestion);
    }

    public function voteHelpful(int $questionId, int $helpful): bool
    {
        $question = $this->questionRepository->getById($questionId);

        if ($question->getStatus() !== Status::STATUS_APPROVED) {
            throw new NoSuchEntityException(
                __('The product question with ID "%1" does not exist.', $questionId)
            );
        }

        $customerId = $this->getCurrentCustomerId();
        $ip = $this->request->getClientIp();

        /** @var \AlpineCommerce\ProductQuestions\Api\Data\VoteInterface $vote */
        $vote = $this->voteFactory->create();
        $vote->setQuestionId($questionId);
        $vote->setCustomerId($customerId);
        $vote->setIp($ip);
        $vote->setValue($helpful);

        try {
            $this->voteResource->save($vote);
        } catch (\Exception $e) {
            throw new StateException(__('Unable to record your vote.'), $e);
        }

        $count = $question->getHelpfulCount() + ($helpful ? 1 : 0);
        $question->setHelpfulCount($count);
        $this->questionRepository->save($question);

        return true;
    }

    public function answerQuestion(int $questionId, string $answer): AnswerInterface
    {
        $customerId = $this->getCurrentCustomerId();
        $question = $this->questionRepository->getById($questionId);

        if ($question->getStatus() !== Status::STATUS_APPROVED) {
            throw new NoSuchEntityException(
                __('The product question with ID "%1" does not exist.', $questionId)
            );
        }

        /** @var AnswerInterface $answerModel */
        $answerModel = $this->answerFactory->create();
        $answerModel->setQuestionId($questionId);
        $answerModel->setCustomerId($customerId);
        $answerModel->setAnswer($answer);
        $answerModel->setIsOfficial(false);

        return $this->answerRepository->save($answerModel);
    }

    private function getCurrentCustomerId(): ?int
    {
        return $this->userContext->getUserId();
    }
}
