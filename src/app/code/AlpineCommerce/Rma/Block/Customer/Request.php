<?php
declare(strict_types=1);

namespace AlpineCommerce\Rma\Block\Customer;

use Magento\Framework\View\Element\Template;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;

class Request extends Template
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly CustomerSession $customerSession
    ) {
    }

    public function getRecentOrders()
    {
        $customerId = (int) $this->customerSession->getCustomerId();
        $orders = $this->orderRepository->getList(
            $this->orderRepository->createSearchCriteriaBuilder()
                ->addFilter('customer_id', $customerId)
                ->setPageSize(10)
                ->create()
        );

        return $orders->getItems();
    }
}
