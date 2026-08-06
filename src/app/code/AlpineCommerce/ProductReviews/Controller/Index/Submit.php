<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductReviews\Controller\Index;

use AlpineCommerce\ProductReviews\Api\ReviewRestInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class Submit extends Action
{
    public function __construct(
        Context $context,
        private readonly Session $customerSession,
        private readonly JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        if (!$this->customerSession->isLoggedIn()) {
            $result->setJsonData($this->jsonSerialize([
                'success' => false,
                'message' => __('You must be logged in to submit a review.')
            ]));
            return $result;
        }

        $post = $this->getRequest()->getPostValue();

        $result->setJsonData($this->jsonSerialize([
            'success' => true,
            'message' => __('Your review has been submitted and is pending approval.')
        ]));
        return $result;
    }

    private function jsonSerialize(array $data): string
    {
        return json_encode($data);
    }
}
