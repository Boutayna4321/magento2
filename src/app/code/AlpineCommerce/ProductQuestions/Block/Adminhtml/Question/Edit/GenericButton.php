<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductQuestions\Block\Adminhtml\Question\Edit;

use AlpineCommerce\ProductQuestions\Api\QuestionRepositoryInterface;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Exception\NoSuchEntityException;

class GenericButton
{
    protected $context;

    protected $questionRepository;

    public function __construct(
        Context $context,
        QuestionRepositoryInterface $questionRepository
    ) {
        $this->context = $context;
        $this->questionRepository = $questionRepository;
    }

    public function getQuestionId()
    {
        try {
            return $this->questionRepository->getById(
                (int) $this->context->getRequest()->getParam('question_id')
            )->getId();
        } catch (NoSuchEntityException $e) {
        }

        return null;
    }

    public function getUrl($route = '', $params = [])
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}
