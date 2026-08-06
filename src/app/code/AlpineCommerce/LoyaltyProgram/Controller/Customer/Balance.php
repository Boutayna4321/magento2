<?php
declare(strict_types=1);

namespace AlpineCommerce\LoyaltyProgram\Controller\Customer;

use Magento\Customer\Model\Session;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\PageFactory;

class Balance implements HttpGetActionInterface
{
    /**
     * @param Session $customerSession
     * @param CustomerUrl $customerUrl
     * @param RedirectFactory $redirectFactory
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        private readonly Session $customerSession,
        private readonly CustomerUrl $customerUrl,
        private readonly RedirectFactory $redirectFactory,
        private readonly PageFactory $resultPageFactory
    ) {
    }

    /**
     * @return ResponseInterface|ResultInterface
     * @throws LocalizedException
     */
    public function execute(): ResponseInterface|ResultInterface
    {
        if (!$this->customerSession->isLoggedIn()) {
            return $this->redirectFactory->create()->setUrl($this->customerUrl->getLoginUrl());
        }

        return $this->resultPageFactory->create();
    }
}
