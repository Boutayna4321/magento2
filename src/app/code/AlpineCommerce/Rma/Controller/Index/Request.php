<?php
declare(strict_types=1);

namespace AlpineCommerce\Rma\Controller\Index;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use AlpineCommerce\Rma\Api\Data\RmaInterface;
use AlpineCommerce\Rma\Model\ResourceModel\Rma\CollectionFactory as RmaCollectionFactory;
use AlpineCommerce\Rma\Model\RmaFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class Request extends Action
{
    public function __construct(
        private readonly Context $context,
        private readonly CustomerSession $customerSession,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly RmaFactory $rmaFactory,
        private readonly RmaCollectionFactory $rmaCollectionFactory,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly TimezoneInterface $timezone
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('rma/request');

        if (!$this->customerSession->isLoggedIn()) {
            $this->messageManager->addErrorMessage(__('You must be logged in to request a return.'));
            return $resultRedirect;
        }

        $orderId = (int) $this->getRequest()->getParam('order_id');
        if (!$orderId) {
            $this->messageManager->addErrorMessage(__('Invalid order.'));
            return $resultRedirect;
        }

        try {
            $order = $this->orderRepository->get($orderId);
            if ((int) $order->getCustomerId() !== (int) $this->customerSession->getCustomerId()) {
                $this->messageManager->addErrorMessage(__('You do not have permission to return this order.'));
                return $resultRedirect;
            }

            $storeId = (int) $order->getStoreId();
            if (!$this->isModuleEnabled($storeId)) {
                $this->messageManager->addErrorMessage(__('RMA is not enabled.'));
                return $resultRedirect;
            }

            $returnAllowedUntil = $order->getData('rma_allowed_until');
            if ($returnAllowedUntil && $this->timezone->date()->format('Y-m-d H:i:s') > $returnAllowedUntil) {
                $this->messageManager->addErrorMessage(__('Return period has expired for this order.'));
                return $resultRedirect;
            }

            $existing = $this->rmaCollectionFactory->create()
                ->addFieldToFilter('order_id', $orderId)
                ->addFieldToFilter('customer_id', (int) $this->customerSession->getCustomerId())
                ->addFieldToFilter('status', ['neq' => RmaInterface::STATUS_CLOSED])
                ->setPageSize(1)
                ->getFirstItem();
            if ($existing && $existing->getId()) {
                $this->messageManager->addErrorMessage(__('A return request is already in progress for this order.'));
                return $resultRedirect;
            }

            $rma = $this->rmaFactory->create();
            $rma->setOrderId($orderId);
            $rma->setCustomerId((int) $this->customerSession->getCustomerId());
            $rma->setStatus(RmaInterface::STATUS_PENDING);
            $rma->setCreatedAt($this->timezone->date()->format('Y-m-d H:i:s'));
            $rma->save();

            $this->messageManager->addSuccessMessage(__('Return request submitted successfully. RMA ID: %1', $rma->getRmaId()));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Error submitting return request: %1', $e->getMessage()));
        }

        return $resultRedirect;
    }

    private function isModuleEnabled(int $storeId): bool
    {
        return $this->scopeConfig->isSetFlag(
            'rma/general/enabled',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
