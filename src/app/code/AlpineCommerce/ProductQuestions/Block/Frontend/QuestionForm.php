<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductQuestions\Block\Frontend;

use Magento\Customer\Model\Session;
use Magento\Framework\View\Element\Template;

class QuestionForm extends Template
{
    public function __construct(
        Template\Context $context,
        private readonly Session $customerSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getProductId(): int
    {
        return (int) $this->getRequest()->getParam('id', 0);
    }

    public function isLoggedIn(): bool
    {
        return $this->customerSession->isLoggedIn();
    }
}
