<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductLabels\Controller\Adminhtml\Label;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\LayoutFactory;
use Magento\Framework\App\Request\DataPersistorInterface;

class ProductGrid extends Action
{
    public function __construct(
        Action\Context $context,
        private readonly LayoutFactory $resultLayoutFactory,
        private readonly DataPersistorInterface $dataPersistor
    ) {
        parent::__construct($context);
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $resultLayout = $this->resultLayoutFactory->create();
        $resultLayout->getMessagesBlock()->addMessages([]);
        return $resultLayout;
    }

    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed("AlpineCommerce_ProductLabels::labels_save");
    }
}
