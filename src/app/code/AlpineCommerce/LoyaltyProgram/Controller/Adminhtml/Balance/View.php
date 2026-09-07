<?php
declare(strict_types=1);

namespace AlpineCommerce\LoyaltyProgram\Controller\Adminhtml\Balance;

use AlpineCommerce\LoyaltyProgram\Api\LoyaltyBalanceRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;

class View extends Action
{
    public const ADMIN_RESOURCE = 'AlpineCommerce_LoyaltyProgram::balance';

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param RedirectFactory $redirectFactory
     * @param LoyaltyBalanceRepositoryInterface $balanceRepository
     */
    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly RedirectFactory $redirectFactory,
        private readonly LoyaltyBalanceRepositoryInterface $balanceRepository
    ) {
        parent::__construct($context);
    }

    /**
     * @return ResultInterface
     */
    public function execute()
    {
        $customerId = (int) $this->getRequest()->getParam('customer_id');

        if ($customerId <= 0) {
            $this->messageManager->addErrorMessage(__('Please select a customer to view.'));
            return $this->redirectFactory->create()->setPath('loyalty/balance/index');
        }

        try {
            $this->balanceRepository->getByCustomerId($customerId);
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This customer does not exist.'));
            return $this->redirectFactory->create()->setPath('loyalty/balance/index');
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu(static::ADMIN_RESOURCE);
        $resultPage->getConfig()->getTitle()->prepend(__('Loyalty Balances'));
        $resultPage->getConfig()->getTitle()->prepend(__('Customer Balance View'));

        $resultPage->addBreadcrumb(__('Loyalty Balances'), __('Loyalty Balances'));
        $resultPage->addBreadcrumb(__('Customer Balance'), __('Customer Balance'));

        return $resultPage;
    }
}
