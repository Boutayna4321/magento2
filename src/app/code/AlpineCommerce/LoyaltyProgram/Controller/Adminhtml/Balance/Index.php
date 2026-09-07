<?php
declare(strict_types=1);

namespace AlpineCommerce\LoyaltyProgram\Controller\Adminhtml\Balance;

use AlpineCommerce\LoyaltyProgram\Model\ResourceModel\LoyaltyBalance\GridCollectionFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    public const ADMIN_RESOURCE = 'AlpineCommerce_LoyaltyProgram::balance';

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu(static::ADMIN_RESOURCE);
        $resultPage->getConfig()->getTitle()->prepend(__('Loyalty Balances'));

        return $resultPage;
    }
}
