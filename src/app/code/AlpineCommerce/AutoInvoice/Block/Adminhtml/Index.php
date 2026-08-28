<?php
declare(strict_types=1);

namespace AlpineCommerce\AutoInvoice\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Grid\CollectionFactory as OrderGridCollectionFactory;
use Magento\Store\Model\ScopeInterface;

class Index extends Template
{
    public function __construct(
        Template\Context $context,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly OrderCollectionFactory $orderCollectionFactory,
        private readonly OrderGridCollectionFactory $orderGridCollectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function isModuleEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            'autoinvoice/general/enabled',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getModuleStatus(): string
    {
        return $this->isModuleEnabled() ? (string) __('Enabled') : (string) __('Disabled');
    }

    public function getModuleStatusClass(): string
    {
        return $this->isModuleEnabled() ? 'enabled' : 'disabled';
    }

    public function getRecentOrders(int $limit = 10): array
    {
        $collection = $this->orderGridCollectionFactory->create();
        $collection->setPageSize($limit)
            ->setCurPage(1)
            ->setOrder('entity_id', 'DESC');

        return $collection->getItems();
    }

    public function getPaymentMethods(): string
    {
        return (string) $this->scopeConfig->getValue(
            'autoinvoice/general/payment_methods',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getInvoiceUrl(): string
    {
        return $this->getUrl('sales/invoice/index');
    }

    public function getOrderUrl(): string
    {
        return $this->getUrl('sales/order/index');
    }
}
